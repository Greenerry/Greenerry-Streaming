<?php
require_once '../includes/config.php';

$playlistOk = '';
$playlistErr = '';
if (is_user_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $playlistErr = verify_csrf_request() ?? '';
    $playlistAction = (string)($_POST['playlist_action'] ?? '');
    $uid = current_user_id();

    if (!$playlistErr && $playlistAction === 'create_playlist') {
        $name = trim((string)($_POST['playlist_name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 140) {
            $playlistErr = current_lang() === 'en' ? 'Choose a playlist name.' : 'Escolhe um nome para a playlist.';
        } else {
            $cover = '';
            if (!empty($_FILES['playlist_cover']) && ($_FILES['playlist_cover']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $imageErr = validate_uploaded_image($_FILES['playlist_cover']);
                if ($imageErr) {
                    $playlistErr = $imageErr;
                } else {
                    [$cover, $saveErr] = save_uploaded_file($_FILES['playlist_cover'], 'img', 'playlist_' . $uid, ['jpg', 'jpeg', 'png', 'webp'], GREENERRY_MAX_IMAGE_BYTES);
                    if ($saveErr) {
                        $playlistErr = $saveErr;
                    }
                }
            }

            if (!$playlistErr) {
                db_prepared($conn, "INSERT INTO playlist (idCliente, nome, capa) VALUES (?, ?, ?)", 'iss', [$uid, $name, $cover ?: null]);
                $playlistOk = current_lang() === 'en' ? 'Playlist created.' : 'Playlist criada.';
            }
        }
    } elseif (!$playlistErr && $playlistAction === 'update_playlist') {
        $playlistId = (int)($_POST['playlist_id'] ?? 0);
        $name = trim((string)($_POST['playlist_name'] ?? ''));
        $removeCover = !empty($_POST['remove_playlist_cover']);
        $playlist = $playlistId > 0
            ? db_one_prepared($conn, "SELECT idPlaylist, capa FROM playlist WHERE idPlaylist = ? AND idCliente = ? LIMIT 1", 'ii', [$playlistId, $uid])
            : null;

        if (!$playlist || $name === '' || mb_strlen($name) > 140) {
            $playlistErr = current_lang() === 'en' ? 'Choose a playlist name.' : 'Escolhe um nome para a playlist.';
        } else {
            $oldCover = (string)($playlist['capa'] ?? '');
            $cover = $removeCover ? '' : $oldCover;

            if (!empty($_FILES['playlist_cover']) && ($_FILES['playlist_cover']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $imageErr = validate_uploaded_image($_FILES['playlist_cover']);
                if ($imageErr) {
                    $playlistErr = $imageErr;
                } else {
                    [$savedCover, $saveErr] = save_uploaded_file($_FILES['playlist_cover'], 'img', 'playlist_' . $uid, ['jpg', 'jpeg', 'png', 'webp'], GREENERRY_MAX_IMAGE_BYTES);
                    if ($saveErr) {
                        $playlistErr = $saveErr;
                    } else {
                        $cover = $savedCover;
                    }
                }
            }

            if (!$playlistErr) {
                db_prepared($conn, "UPDATE playlist SET nome = ?, capa = ? WHERE idPlaylist = ? AND idCliente = ?", 'ssii', [$name, $cover ?: null, $playlistId, $uid]);
                if ($oldCover !== '' && $oldCover !== $cover) {
                    delete_orphan_asset_file($conn, 'img', $oldCover);
                }
                $playlistOk = current_lang() === 'en' ? 'Playlist updated.' : 'Playlist atualizada.';
            }
        }
    } elseif (!$playlistErr && $playlistAction === 'remove_playlist_track') {
        $playlistId = (int)($_POST['playlist_id'] ?? 0);
        $trackId = (int)($_POST['track_id'] ?? 0);
        db_prepared(
            $conn,
            "DELETE pf
             FROM playlist_faixa pf
             JOIN playlist p ON p.idPlaylist = pf.idPlaylist
             WHERE pf.idPlaylist = ?
               AND pf.idFaixa = ?
               AND p.idCliente = ?",
            'iii',
            [$playlistId, $trackId, $uid]
        );
    } elseif (!$playlistErr && $playlistAction === 'delete_playlist') {
        $playlistId = (int)($_POST['playlist_id'] ?? 0);
        db_prepared(
            $conn,
            "DELETE FROM playlist WHERE idPlaylist = ? AND idCliente = ?",
            'ii',
            [$playlistId, $uid]
        );
        $playlistOk = current_lang() === 'en' ? 'Playlist deleted.' : 'Playlist apagada.';
    }
}

$playlists = [];
$playlistDetails = [];
if (is_user_logged_in()) {
    $playlists = db_all_prepared(
        $conn,
        "SELECT p.*, COUNT(pf.idPlaylistFaixa) AS total_faixas
         FROM playlist p
         LEFT JOIN playlist_faixa pf ON pf.idPlaylist = p.idPlaylist
         WHERE p.idCliente = ?
         GROUP BY p.idPlaylist
         ORDER BY p.atualizado_em DESC",
        'i',
        [current_user_id()]
    );

    foreach ($playlists as $playlist) {
        $playlistDetails[(int)$playlist['idPlaylist']] = db_all_prepared(
            $conn,
            "SELECT f.idFaixa, f.titulo, COALESCE(g.nome, f.genero) AS genero, f.ficheiro_audio, r.titulo AS album_titulo, r.capa, c.nome AS artista_nome, c.idCliente AS artistId, c.foto AS artist_foto, pf.criado_em
             FROM playlist_faixa pf
             JOIN faixa f ON f.idFaixa = pf.idFaixa
             LEFT JOIN genero g ON g.idGenero = f.idGenero
             JOIN release_musical r ON r.idRelease = f.idRelease
             JOIN cliente c ON c.idCliente = r.idCliente
             WHERE pf.idPlaylist = ?
             ORDER BY pf.ordem ASC, pf.criado_em ASC",
            'i',
            [(int)$playlist['idPlaylist']]
        );
    }
}

include '../includes/header.php';
?>

<section class="content-shell library-shell">
  <div class="wrap">
    <div class="library-spotify-head">
      <h2 data-t="library_your_library">A tua biblioteca</h2>
      <?php if (is_user_logged_in()): ?>
        <details class="library-create-menu">
          <summary><span>+</span><strong data-t="playlist_create">Criar</strong></summary>
          <form method="post" class="library-create-popover" enctype="multipart/form-data">
            <?= csrf_input() ?>
            <input type="hidden" name="playlist_action" value="create_playlist">
            <label class="playlist-cover-field">
              <input type="file" name="playlist_cover" accept=".jpg,.jpeg,.png,.webp">
              <span>+</span>
              <strong data-t="playlist_cover">Capa</strong>
            </label>
            <button type="button" class="playlist-cover-clear" data-clear-playlist-cover hidden data-t="remove">Remover</button>
            <input type="text" name="playlist_name" class="finput" maxlength="140" data-tp="playlist_create_placeholder" placeholder="Nova playlist">
            <button type="submit" class="btn btn-dark btn-sm" data-t="playlist_create">Criar</button>
          </form>
        </details>
      <?php endif; ?>
    </div>

    <div class="library-toolbar">
      <nav class="library-switch" aria-label="Library filters">
        <button type="button" class="on" data-library-tab="playlists" data-t="library_playlists_title">Playlists</button>
        <button type="button" data-library-tab="tracks" data-t="library_liked_songs">Músicas curtidas</button>
        <button type="button" data-library-tab="artists" data-t="library_favourite_artists_title">Artistas favoritos</button>
      </nav>
      <label class="library-main-search spotify-search-field">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-tp="library_search_placeholder" placeholder="Procurar na biblioteca">
      </label>
    </div>

    <?php if ($playlistOk): ?><div class="alert alert-ok"><?= h($playlistOk) ?></div><?php endif; ?>
    <?php if ($playlistErr): ?><div class="alert alert-err"><?= h($playlistErr) ?></div><?php endif; ?>

    <section class="library-section" id="library-playlists-panel" data-library-panel="playlists">
      <?php if (!is_user_logged_in()): ?>
        <div class="card surface-card surface-card--soft"><div class="card-body"><p data-t="library_login_playlists">Faz login para criares playlists.</p></div></div>
      <?php else: ?>
        <div class="library-spotify-grid">
          <button type="button" class="library-tile library-liked-tile" data-library-tab="tracks">
            <span class="library-liked-cover">♥</span>
            <strong data-t="library_liked_songs">Músicas curtidas</strong>
            <small data-t="library_liked_songs_meta">Playlist · Greenerry</small>
          </button>

          <?php foreach ($playlists as $playlist): ?>
            <?php
            $playlistTracks = $playlistDetails[(int)$playlist['idPlaylist']] ?? [];
            $firstCover = $playlist['capa'] ?: ($playlistTracks[0]['capa'] ?? '');
            ?>
            <button type="button" class="library-tile playlist-card" data-library-open-playlist="<?= (int)$playlist['idPlaylist'] ?>">
              <div class="library-tile-cover">
                <?php if ($firstCover): ?>
                  <img src="<?= h(asset_url('img', $firstCover)) ?>" alt="">
                <?php else: ?>
                  <span>♪</span>
                <?php endif; ?>
              </div>
              <strong><?= h($playlist['nome']) ?></strong>
              <small><?= h(count_label((int)$playlist['total_faixas'], 'track')) ?> · Greenerry</small>
            </button>
          <?php endforeach; ?>

          <?php if (!$playlists): ?>
            <a href="music.php" class="library-tile library-empty-tile">
              <span class="library-tile-cover"><span>+</span></span>
              <strong data-t="library_empty_playlists">Ainda não tens playlists.</strong>
              <small data-t="library_discover_music">Descobrir música</small>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php foreach ($playlists as $playlist): ?>
      <?php
      $playlistTracks = $playlistDetails[(int)$playlist['idPlaylist']] ?? [];
      $firstCover = $playlist['capa'] ?: ($playlistTracks[0]['capa'] ?? '');
      $playlistPlayerTracks = array_map(static function (array $track): array {
          return [
              'id' => (int)$track['idFaixa'],
              'title' => (string)$track['titulo'],
              'artist' => (string)$track['artista_nome'],
              'cover' => asset_url('img', (string)$track['capa']),
              'audio' => asset_url('audio', (string)$track['ficheiro_audio']),
              'artistId' => (int)$track['artistId'],
              'artistFoto' => asset_url('img', (string)$track['artist_foto']),
          ];
      }, $playlistTracks);
      $playlistPlayerJson = h(json_encode($playlistPlayerTracks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
      ?>
      <section class="library-section is-hidden library-playlist-view" data-library-panel="playlist-<?= (int)$playlist['idPlaylist'] ?>" data-playlist-id="<?= (int)$playlist['idPlaylist'] ?>" data-playlist-tracks="<?= $playlistPlayerJson ?>">
        <button type="button" class="library-back-btn" data-library-tab="playlists">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
          <span data-t="library_playlists_title">Playlists</span>
        </button>
        <div class="library-detail-hero">
          <div class="library-detail-cover">
            <?php if ($firstCover): ?>
              <img src="<?= h(asset_url('img', $firstCover)) ?>" alt="">
            <?php else: ?>
              <span>♪</span>
            <?php endif; ?>
          </div>
          <div>
            <span class="slabel">Playlist</span>
            <h1><?= h($playlist['nome']) ?></h1>
            <p><strong>Greenerry</strong> · <?= h(count_label((int)$playlist['total_faixas'], 'track')) ?></p>
          </div>
          <div class="library-playlist-hero-actions">
            <details class="library-edit-menu">
              <summary class="btn btn-ghost btn-sm"><?= h(current_lang() === 'en' ? 'Edit' : 'Editar') ?></summary>
              <form method="post" class="library-edit-popover" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <input type="hidden" name="playlist_action" value="update_playlist">
                <input type="hidden" name="playlist_id" value="<?= (int)$playlist['idPlaylist'] ?>">
                <label class="flabel" for="playlist-name-<?= (int)$playlist['idPlaylist'] ?>"><?= h(current_lang() === 'en' ? 'Name' : 'Nome') ?></label>
                <input id="playlist-name-<?= (int)$playlist['idPlaylist'] ?>" type="text" name="playlist_name" class="finput" maxlength="140" value="<?= h($playlist['nome']) ?>" required>
                <label class="playlist-cover-field <?= $playlist['capa'] ? 'has-preview' : '' ?>" <?= $playlist['capa'] ? 'style="background-image:url(' . h(asset_url('img', (string)$playlist['capa'])) . ')"' : '' ?>>
                  <input type="file" name="playlist_cover" accept=".jpg,.jpeg,.png,.webp">
                  <span>+</span>
                  <strong data-t="playlist_cover">Capa</strong>
                </label>
                <?php if (!empty($playlist['capa'])): ?>
                  <label class="library-edit-check">
                    <input type="checkbox" name="remove_playlist_cover" value="1">
                    <span><?= h(current_lang() === 'en' ? 'Remove custom cover' : 'Remover capa personalizada') ?></span>
                  </label>
                <?php endif; ?>
                <button type="submit" class="btn btn-dark btn-sm"><?= h(current_lang() === 'en' ? 'Save changes' : 'Guardar alterações') ?></button>
              </form>
            </details>
            <form method="post" class="library-delete-form library-delete-form--hero" onsubmit="return confirm('<?= h(current_lang() === 'en' ? 'Delete this playlist?' : 'Apagar esta playlist?') ?>')">
              <?= csrf_input() ?>
              <input type="hidden" name="playlist_action" value="delete_playlist">
              <input type="hidden" name="playlist_id" value="<?= (int)$playlist['idPlaylist'] ?>">
              <button type="submit" class="cart-remove-btn" data-t="delete">Delete</button>
            </form>
          </div>
        </div>
        <div class="library-detail-actions">
          <?php if ($playlistTracks): ?>
            <button type="button" class="library-play-btn" onclick="playLibraryPlaylist(<?= (int)$playlist['idPlaylist'] ?>, 0)">
              <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </button>
          <?php endif; ?>
        </div>
        <?php if ($playlistTracks): ?>
          <div class="library-track-list">
            <div class="library-track-head">
              <span>#</span><span>Title</span><span>Album</span><span>Date added</span><span></span>
            </div>
            <?php foreach ($playlistTracks as $index => $track): ?>
              <?php
              $cover = asset_url('img', $track['capa']);
              $audio = asset_url('audio', $track['ficheiro_audio']);
              $artistFoto = asset_url('img', $track['artist_foto']);
              ?>
              <div class="library-track-row" data-playlist-track-row data-track-id="<?= (int)$track['idFaixa'] ?>" data-playlist-index="<?= (int)$index ?>">
                <span class="library-track-index"><?= $index + 1 ?></span>
                <button type="button" class="library-track-main" onclick="playLibraryPlaylist(<?= (int)$playlist['idPlaylist'] ?>, <?= (int)$index ?>)">
                  <span class="library-track-cover"><?php if ($cover): ?><img src="<?= h($cover) ?>" alt=""><?php endif; ?></span>
                  <span><strong><?= h($track['titulo']) ?></strong><small><?= h($track['artista_nome']) ?><?= $track['genero'] ? ' · ' . h($track['genero']) : '' ?></small></span>
                </button>
                <span><?= h($track['album_titulo'] ?? '') ?></span>
                <span><?= h(date('d/m/Y', strtotime($track['criado_em']))) ?></span>
                <form method="post" data-playlist-remove-form data-playlist-id="<?= (int)$playlist['idPlaylist'] ?>" data-track-id="<?= (int)$track['idFaixa'] ?>">
                  <?= csrf_input() ?>
                  <input type="hidden" name="playlist_action" value="remove_playlist_track">
                  <input type="hidden" name="playlist_id" value="<?= (int)$playlist['idPlaylist'] ?>">
                  <input type="hidden" name="track_id" value="<?= (int)$track['idFaixa'] ?>">
                  <button type="submit" class="cart-remove-btn" data-t="remove">Remover</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="cart-empty-state">
            <div class="cart-empty-icon">Music</div>
            <h3 data-t="playlist_empty">Ainda nao ha musicas nesta playlist.</h3>
            <a href="music.php" class="btn btn-ghost btn-sm" data-t="library_discover_music">Descobrir música</a>
          </div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <section class="library-section is-hidden" id="library-tracks-panel" data-library-panel="tracks">
      <div class="library-detail-actions library-detail-actions--split">
        <button type="button" class="library-play-btn" onclick="playCurrentFavCollection(0)">
          <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        </button>
        <label class="spotify-search-field library-page-search">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" id="favs-search" placeholder="Procurar músicas curtidas" data-tp="library_favourites_search_placeholder">
        </label>
      </div>
      <div id="favs-empty" class="cart-empty-state is-hidden">
        <div class="cart-empty-icon">♥</div>
        <h3 data-t="library_empty_favourites">Ainda não tens músicas curtidas.</h3>
        <a href="music.php" class="btn btn-ghost btn-sm" data-t="library_discover_music">Descobrir música</a>
      </div>
      <div id="favs-search-empty" class="cart-empty-state is-hidden">
        <div class="cart-empty-icon">Search</div>
        <h3 data-t="library_no_favourite_results">Nenhuma música encontrada.</h3>
      </div>
      <div class="library-track-list" id="favs-grid"></div>
      <nav class="pager" id="favs-pager" aria-label="Pagination"></nav>
    </section>

    <section class="library-section is-hidden" id="library-artists-panel" data-library-panel="artists">
      <div class="library-panel-head">
        <div class="page-intro">
          <span class="slabel" data-t="library_artists_label">Artistas</span>
          <h2 data-t="library_favourite_artists_title">Artistas favoritos</h2>
        </div>
        <?php if (is_user_logged_in()): ?>
          <label class="spotify-search-field library-page-search">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" id="following-search" placeholder="Procurar artistas favoritos" data-tp="library_following_search_placeholder">
          </label>
        <?php endif; ?>
      </div>

      <?php if (!is_user_logged_in()): ?>
        <div class="card surface-card surface-card--soft"><div class="card-body"><p data-t="library_login_artists">Faz login para guardares artistas.</p></div></div>
      <?php else: ?>
        <div id="following-empty" class="card surface-card surface-card--soft is-hidden"><div class="card-body"><p data-t="library_no_following">Ainda não segues artistas.</p></div></div>
        <div id="following-search-empty" class="card surface-card surface-card--soft is-hidden"><div class="card-body"><p data-t="library_no_following_results">Nenhum artista seguido encontrado.</p></div></div>
        <div class="artist-grid-panels" id="following-grid"></div>
        <nav class="pager" id="following-pager" aria-label="Pagination"></nav>
      <?php endif; ?>
    </section>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

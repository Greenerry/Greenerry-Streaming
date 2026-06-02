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
            db_prepared($conn, "INSERT INTO playlist (idCliente, nome) VALUES (?, ?)", 'is', [$uid, $name]);
            $playlistOk = current_lang() === 'en' ? 'Playlist created.' : 'Playlist criada.';
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
        $playlistOk = current_lang() === 'en' ? 'Song removed from playlist.' : 'Música removida da playlist.';
    }
}

$playlists = [];
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
          <form method="post" class="library-create-popover">
            <?= csrf_input() ?>
            <input type="hidden" name="playlist_action" value="create_playlist">
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
      <label class="library-main-search">
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
            $playlistTracks = db_all_prepared(
                $conn,
                "SELECT f.idFaixa, f.titulo, f.genero, f.ficheiro_audio, r.capa, c.nome AS artista_nome, c.idCliente AS artistId, c.foto AS artist_foto
                 FROM playlist_faixa pf
                 JOIN faixa f ON f.idFaixa = pf.idFaixa
                 JOIN release_musical r ON r.idRelease = f.idRelease
                 JOIN cliente c ON c.idCliente = r.idCliente
                 WHERE pf.idPlaylist = ?
                 ORDER BY pf.ordem ASC, pf.criado_em ASC",
                'i',
                [(int)$playlist['idPlaylist']]
            );
            $firstCover = $playlistTracks[0]['capa'] ?? '';
            ?>
            <article class="library-tile playlist-card">
              <div class="library-tile-cover">
                <?php if ($firstCover): ?>
                  <img src="<?= h(asset_url('img', $firstCover)) ?>" alt="">
                <?php else: ?>
                  <span>♪</span>
                <?php endif; ?>
              </div>
              <strong><?= h($playlist['nome']) ?></strong>
              <small><?= h(count_label((int)$playlist['total_faixas'], 'track')) ?> · Greenerry</small>
              <?php if ($playlistTracks): ?>
                <details class="playlist-track-details">
                  <summary data-t="playlist_view_tracks">Ver músicas</summary>
                  <div class="simple-list simple-list--tight">
                    <?php foreach ($playlistTracks as $track): ?>
                      <?php
                      $cover = asset_url('img', $track['capa']);
                      $audio = asset_url('audio', $track['ficheiro_audio']);
                      $artistFoto = asset_url('img', $track['artist_foto']);
                      ?>
                      <div class="simple-list-item playlist-track-row">
                        <button type="button" class="playlist-track-main" onclick="playTrack('<?= h(addslashes($track['titulo'])) ?>','<?= h(addslashes($track['artista_nome'])) ?>','<?= h($cover) ?>','<?= h($audio) ?>',<?= (int)$track['artistId'] ?>,'<?= h($artistFoto) ?>',<?= (int)$track['idFaixa'] ?>)">
                          <span><strong><?= h($track['titulo']) ?></strong><small><?= h($track['artista_nome']) ?><?= $track['genero'] ? ' · ' . h($track['genero']) : '' ?></small></span>
                        </button>
                        <form method="post">
                          <?= csrf_input() ?>
                          <input type="hidden" name="playlist_action" value="remove_playlist_track">
                          <input type="hidden" name="playlist_id" value="<?= (int)$playlist['idPlaylist'] ?>">
                          <input type="hidden" name="track_id" value="<?= (int)$track['idFaixa'] ?>">
                          <button type="submit" class="btn btn-ghost btn-sm" data-t="remove">Remover</button>
                        </form>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </details>
              <?php endif; ?>
            </article>
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

    <section class="library-section is-hidden" id="library-tracks-panel" data-library-panel="tracks">
      <div class="library-panel-head">
        <div class="page-intro">
          <span class="slabel" data-t="library_tracks_label">Faixas</span>
          <h2 data-t="library_liked_songs">Músicas curtidas</h2>
        </div>
        <div class="catalog-filter catalog-filter--single library-search library-search--solo">
          <input type="search" id="favs-search" class="finput" placeholder="Procurar músicas curtidas" data-tp="library_favourites_search_placeholder">
        </div>
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
      <div class="grid stg" id="favs-grid"></div>
      <nav class="pager" id="favs-pager" aria-label="Pagination"></nav>
    </section>

    <section class="library-section is-hidden" id="library-artists-panel" data-library-panel="artists">
      <div class="library-panel-head">
        <div class="page-intro">
          <span class="slabel" data-t="library_artists_label">Artistas</span>
          <h2 data-t="library_favourite_artists_title">Artistas favoritos</h2>
        </div>
        <?php if (is_user_logged_in()): ?>
          <div class="catalog-filter catalog-filter--single library-search library-search--solo">
            <input type="search" id="following-search" class="finput" placeholder="Procurar artistas favoritos" data-tp="library_following_search_placeholder">
          </div>
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

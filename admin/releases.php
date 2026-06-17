<?php
// Admin page purpose: Lets administrators review music releases.
// Keep this admin file simple: check access, load data, then render the view.
require_once '../includes/config.php';
require_admin_permission('releases');

$adminId = current_admin_id();
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = verify_csrf_request() ?? '';
    $releaseId = (int)($_POST['release_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $reasonSafe = db_escape($conn, $reason);

    if ($feedback === '' && $releaseId > 0 && $action === 'guardar') {
        $title = trim((string)($_POST['titulo'] ?? ''));
        $type = (string)($_POST['tipo'] ?? 'Single');
        $releaseDate = (string)($_POST['data_lancamento'] ?? '');
        $description = trim((string)($_POST['descricao'] ?? ''));
        $state = (string)($_POST['estado'] ?? 'pendente');
        $allowedReleaseStates = ['pendente', 'aprovado', 'rejeitado', 'inativo'];
        if ($title === '' || !in_array($type, ['Single', 'EP', 'Album'], true) || !in_array($state, $allowedReleaseStates, true)) {
            $feedback = tr('error.api_invalid_request');
        } else {
            $titleSafe = db_escape($conn, $title);
            $typeSafe = db_escape($conn, $type);
            $dateSafe = db_escape($conn, $releaseDate);
            $descriptionSafe = db_escape($conn, $description);
            $stateSafe = db_escape($conn, $state);
            $active = $state === 'aprovado' ? 1 : 0;
            $trackStateMap = [
                'pendente' => 'pendente',
                'aprovado' => 'aprovada',
                'rejeitado' => 'rejeitada',
                'inativo' => 'inativa',
            ];
            $trackStateSafe = db_escape($conn, $trackStateMap[$state] ?? 'pendente');
            $releaseMedia = db_one($conn, "SELECT capa FROM release_musical WHERE idRelease = {$releaseId} LIMIT 1");
            $cover = (string)($releaseMedia['capa'] ?? '');
            if (isset($_FILES['capa']) && ($_FILES['capa']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $coverError = validate_uploaded_image($_FILES['capa']);
                if ($coverError) {
                    $feedback = $coverError;
                } else {
                    [$savedCover, $saveErr] = save_uploaded_file($_FILES['capa'], 'img', 'release_admin_' . $releaseId, ['jpg', 'jpeg', 'png', 'webp'], GREENERRY_MAX_IMAGE_BYTES);
                    if ($saveErr) {
                        $feedback = $saveErr;
                    } else {
                        $cover = $savedCover;
                    }
                }
            }
            $trackTitles = $_POST['track_title'] ?? [];
            $trackGenres = $_POST['track_genre'] ?? [];
            $trackFiles = $_FILES['track_audio'] ?? null;
            $releaseGenreId = null;
            if ($feedback === '' && is_array($trackTitles)) {
                foreach ($trackTitles as $trackIdRaw => $trackTitleRaw) {
                    $trackId = (int)$trackIdRaw;
                    $trackTitle = trim((string)$trackTitleRaw);
                    $trackGenre = mb_substr(trim((string)($trackGenres[$trackIdRaw] ?? '')), 0, 80);
                    $trackGenreId = greenerry_resolve_genre_id($conn, $trackGenre);
                    if ($releaseGenreId === null && $trackGenreId !== null) {
                        $releaseGenreId = $trackGenreId;
                    }
                    if ($trackId <= 0 || $trackTitle === '') continue;
                    $audioSql = '';
                    if ($trackFiles && !empty($trackFiles['name'][$trackIdRaw])) {
                        $file = [
                            'name' => $trackFiles['name'][$trackIdRaw],
                            'type' => $trackFiles['type'][$trackIdRaw] ?? '',
                            'tmp_name' => $trackFiles['tmp_name'][$trackIdRaw] ?? '',
                            'error' => $trackFiles['error'][$trackIdRaw] ?? UPLOAD_ERR_NO_FILE,
                            'size' => $trackFiles['size'][$trackIdRaw] ?? 0,
                        ];
                        $audioError = validate_uploaded_audio($file);
                        if ($audioError) {
                            $feedback = $audioError;
                            break;
                        }
                        [$audioName, $saveErr] = save_uploaded_file($file, 'audio', 'track_admin_' . $releaseId . '_' . $trackId, ['mp3', 'wav', 'ogg', 'flac', 'm4a'], GREENERRY_MAX_AUDIO_BYTES);
                        if ($saveErr) {
                            $feedback = $saveErr;
                            break;
                        }
                        $audioSql = ", ficheiro_audio = '" . db_escape($conn, $audioName) . "'";
                    }
                    mysqli_query(
                        $conn,
                        "UPDATE faixa
                         SET titulo = '" . db_escape($conn, $trackTitle) . "',
                             genero = " . ($trackGenre !== '' ? "'" . db_escape($conn, $trackGenre) . "'" : "NULL") . ",
                             idGenero = " . ($trackGenreId !== null ? (int)$trackGenreId : "NULL") . "{$audioSql}
                         WHERE idFaixa = {$trackId} AND idRelease = {$releaseId}"
                    );
                }
            }
        }

        if ($feedback === '') {
            mysqli_query(
                $conn,
                "UPDATE release_musical
                 SET titulo = '{$titleSafe}',
                     tipo = '{$typeSafe}',
                     data_lancamento = " . ($dateSafe !== '' ? "'{$dateSafe}'" : "NULL") . ",
                     descricao = '{$descriptionSafe}',
                     capa = '" . db_escape($conn, $cover) . "',
                     idGenero = " . ($releaseGenreId !== null ? (int)$releaseGenreId : "NULL") . ",
                     estado = '{$stateSafe}',
                     ativo = {$active}
                 WHERE idRelease = {$releaseId}"
            );
            mysqli_query($conn, "UPDATE faixa SET estado = '{$trackStateSafe}', ativo = {$active} WHERE idRelease = {$releaseId}");
            $feedback = tr('success.release_updated');
        }
    }

    if ($feedback === '' && $releaseId > 0 && in_array($action, ['aprovar', 'rejeitar', 'inativar', 'reativar'], true)) {
        $releaseActionRow = db_one($conn, "SELECT estado FROM release_musical WHERE idRelease = {$releaseId} LIMIT 1");
        $currentReleaseState = (string)($releaseActionRow['estado'] ?? '');

        if ($action === 'aprovar') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'aprovado', motivo_rejeicao = NULL, idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 1 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'aprovada', ativo = 1 WHERE idRelease = {$releaseId}");
            send_release_review_email($conn, $releaseId, $action);
            notify_release_review($conn, $releaseId, $action);
            $feedback = tr('success.release_approved');
        } elseif ($action === 'rejeitar') {
            $tracksToDelete = db_all($conn, "SELECT ficheiro_audio FROM faixa WHERE idRelease = {$releaseId}");
            $audioFilesToDelete = array_map(static fn($track) => (string)($track['ficheiro_audio'] ?? ''), $tracksToDelete);

            mysqli_query($conn, "UPDATE release_musical SET estado = 'rejeitado', motivo_rejeicao = '{$reasonSafe}', idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 0 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'rejeitada', ativo = 0, ficheiro_audio = '' WHERE idRelease = {$releaseId}");
            delete_orphan_asset_files($conn, 'audio', $audioFilesToDelete);
            cleanup_unused_uploaded_assets($conn);
            send_release_review_email($conn, $releaseId, $action, $reason);
            notify_release_review($conn, $releaseId, $action, $reason);
            $feedback = tr('success.release_rejected');
        } elseif ($action === 'inativar') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'inativo', ativo = 0, bloqueado_admin = 1 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'inativa', ativo = 0 WHERE idRelease = {$releaseId}");
            notify_release_review($conn, $releaseId, $action);
            $feedback = tr('success.release_deactivated');
        } elseif ($action === 'reativar' && $currentReleaseState !== 'rejeitado') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'aprovado', ativo = 1, bloqueado_admin = 0 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'aprovada', ativo = 1 WHERE idRelease = {$releaseId}");
            notify_release_review($conn, $releaseId, $action);
            $feedback = tr('success.release_reactivated');
        }
    }
}

$adminReleasesPerPage = 50;
$adminReleasesPage = max(1, (int)($_GET['page'] ?? 1));
$totalAdminReleases = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM release_musical")['total'] ?? 0);
$adminReleasesTotalPages = max(1, (int)ceil($totalAdminReleases / $adminReleasesPerPage));
$adminReleasesPage = min($adminReleasesPage, $adminReleasesTotalPages);
$adminReleasesOffset = ($adminReleasesPage - 1) * $adminReleasesPerPage;

$pendingPerPage = 6;
$pendingPage = max(1, (int)($_GET['pending_page'] ?? 1));
$totalPending = (int)(db_one($conn, "SELECT COUNT(*) AS total FROM release_musical WHERE estado = 'pendente'")['total'] ?? 0);
$pendingTotalPages = max(1, (int)ceil($totalPending / $pendingPerPage));
$pendingPage = min($pendingPage, $pendingTotalPages);
$pendingOffset = ($pendingPage - 1) * $pendingPerPage;

$pending = db_all(
    $conn,
    "SELECT r.*, c.nome AS artista,
            COUNT(f.idFaixa) AS total_faixas
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     LEFT JOIN faixa f ON f.idRelease = r.idRelease
     WHERE r.estado = 'pendente'
     GROUP BY r.idRelease
     ORDER BY r.criado_em DESC
     LIMIT {$pendingPerPage} OFFSET {$pendingOffset}"
);

$allReleases = db_all(
    $conn,
    "SELECT r.*, c.nome AS artista,
            COUNT(f.idFaixa) AS total_faixas
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     LEFT JOIN faixa f ON f.idRelease = r.idRelease
     GROUP BY r.idRelease
     ORDER BY r.criado_em DESC
     LIMIT {$adminReleasesPerPage} OFFSET {$adminReleasesOffset}"
);

$releaseTracks = [];
$combinedReleases = array_merge($allReleases, $pending);
if ($combinedReleases) {
    $releaseIds = implode(',', array_unique(array_map(static fn($release) => (int)$release['idRelease'], $combinedReleases)));
    $trackRows = db_all(
        $conn,
        "SELECT idFaixa, idRelease, numero_faixa, titulo, genero, ficheiro_audio
         FROM faixa
         WHERE idRelease IN ({$releaseIds})
         ORDER BY idRelease, numero_faixa"
    );
    foreach ($trackRows as $track) {
        $releaseTracks[(int)$track['idRelease']][] = $track;
    }
}

$releaseStats = [
    'pendentes' => 0,
    'aprovados' => 0,
    'rejeitados' => 0,
    'inativos' => 0,
];

foreach (db_all($conn, "SELECT estado, COUNT(*) AS total FROM release_musical GROUP BY estado") as $release) {
    $state = (string)($release['estado'] ?? '');
    if ($state === 'pendente') {
        $releaseStats['pendentes'] = (int)$release['total'];
    } elseif ($state === 'aprovado') {
        $releaseStats['aprovados'] = (int)$release['total'];
    } elseif ($state === 'rejeitado') {
        $releaseStats['rejeitados'] = (int)$release['total'];
    } elseif ($state === 'inativo') {
        $releaseStats['inativos'] = (int)$release['total'];
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="releases_kicker">Music review</span>
    <h2 data-admin-t="releases_title">Lançamentos</h2>
    <p data-admin-t="releases_intro">Aprova releases, ouve faixas e gere estados musicais.</p>
  </div>
  <div class="stats-grid admin-top-stats">
    <button type="button" class="stat stat-button" data-admin-stat-filter="releases-search" data-filter-value="pendente"><div class="stat-val"><?= (int)$releaseStats['pendentes'] ?></div><div class="stat-lbl" data-admin-t="state_pending">Pendentes</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="releases-search" data-filter-value="aprovado"><div class="stat-val"><?= (int)$releaseStats['aprovados'] ?></div><div class="stat-lbl" data-admin-t="state_approved">Aprovados</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="releases-search" data-filter-value="rejeitado"><div class="stat-val"><?= (int)$releaseStats['rejeitados'] ?></div><div class="stat-lbl" data-admin-t="state_rejected">Rejeitados</div></button>
    <button type="button" class="stat stat-button" data-admin-stat-filter="releases-search" data-filter-value="inativo"><div class="stat-val"><?= (int)$releaseStats['inativos'] ?></div><div class="stat-lbl" data-admin-t="state_inactive">Inativos</div></button>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>

<div id="releases-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="releases_pending">Lançamentos pendentes</h4>
    <span class="badge badge-red"><?= (int)$totalPending ?></span>
  </div>

  <?php if (!$pending): ?>
    <p data-admin-t="releases_empty_pending">Sem lançamentos pendentes.</p>
  <?php else: ?>
    <div class="admin-card-list">
      <?php foreach ($pending as $release): ?>
        <article class="admin-review-card admin-review-card--release" data-review-type="release" data-review-id="<?= (int)$release['idRelease'] ?>" data-admin-state="<?= h($release['estado']) ?>">
          <div class="admin-review-main">
            <div class="admin-review-meta">
              <span class="badge badge-light"><?= h($release['tipo']) ?></span>
              <strong class="admin-release-title"><?= h($release['titulo']) ?></strong>
              
              <div class="admin-review-meta-grid">
                <div class="admin-review-meta-item">
                  <span data-admin-t="label_artist">Artista</span>
                  <strong><?= h($release['artista']) ?></strong>
                </div>
                <div class="admin-review-meta-item">
                  <span data-admin-t="profile_table_type">Tipo</span>
                  <strong><?= h($release['tipo']) ?></strong>
                </div>
                <div class="admin-review-meta-item">
                  <span data-admin-t="label_tracks">Faixas</span>
                  <strong><?= (int)$release['total_faixas'] ?></strong>
                </div>
                <?php if (!empty($release['data_lancamento'])): ?>
                  <div class="admin-review-meta-item">
                    <span data-admin-t="label_release_date">Lançamento</span>
                    <strong><?= date('d/m/Y', strtotime($release['data_lancamento'])) ?></strong>
                  </div>
                <?php endif; ?>
              </div>

              <?php if (!empty($release['descricao'])): ?>
                <p class="admin-release-description"><?= h($release['descricao']) ?></p>
              <?php endif; ?>
              <?php if (!empty($releaseTracks[(int)$release['idRelease']])): ?>
                <div class="admin-audio-list">
                  <?php foreach ($releaseTracks[(int)$release['idRelease']] as $track): ?>
                    <details class="admin-audio-item">
                      <summary>
                        <span><?= (int)$track['numero_faixa'] ?>. <?= h($track['titulo']) ?></span>
                        <span data-admin-t="releases_listen">Ouvir</span>
                      </summary>
                      <?php if (!empty($track['ficheiro_audio'])): ?>
                        <div class="admin-mini-player" data-audio-src="<?= h(asset_url('audio', $track['ficheiro_audio'])) ?>">
                          <button type="button" class="admin-mini-play" aria-label="Play">></button>
                          <span class="admin-mini-time">0:00</span>
                          <div class="admin-mini-track"><div></div></div>
                        </div>
                      <?php endif; ?>
                    </details>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            <?php if (!empty($release['capa'])): ?>
              <img src="../assets/img/<?= h($release['capa']) ?>" alt="" class="admin-review-image">
            <?php endif; ?>
          </div>

          <form method="post" class="admin-review-actions">
            <?= csrf_input() ?>
            <input type="hidden" name="release_id" value="<?= (int)$release['idRelease'] ?>">
            <textarea name="reason" class="finput" placeholder="Motivo de rejeição (recomendado se recusares o lançamento)." data-admin-tp="releases_reason_placeholder"></textarea>
            <div class="admin-action-buttons">
              <button type="submit" name="action" value="aprovar" class="btn btn-dark btn-sm" data-admin-t="btn_approve">Aprovar</button>
              <button type="submit" name="action" value="rejeitar" class="btn btn-danger btn-sm" data-confirm="Rejeitar este lançamento?" data-admin-t="btn_reject">Rejeitar</button>
            </div>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
    
    <?php if ($pendingTotalPages > 1): ?>
      <?php $otherPageParam = isset($_GET['page']) ? '&page=' . (int)$_GET['page'] : ''; ?>
      <nav class="pager" aria-label="Pending Pagination">
        <?= $pendingPage > 1 ? '<a class="btn btn-ghost btn-sm" href="releases.php?pending_page=' . (int)($pendingPage - 1) . $otherPageParam . '#releases-search" data-admin-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_previous">Anterior</span>' ?>
        <span class="pager-status" data-admin-page-status data-page-current="<?= (int)$pendingPage ?>" data-page-total="<?= (int)$pendingTotalPages ?>">Página <?= (int)$pendingPage ?> de <?= (int)$pendingTotalPages ?></span>
        <?= $pendingPage < $pendingTotalPages ? '<a class="btn btn-ghost btn-sm" href="releases.php?pending_page=' . (int)($pendingPage + 1) . $otherPageParam . '#releases-search" data-admin-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_next">Seguinte</span>' ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="releases_all">Todos os lançamentos</h4>
    <div class="admin-card-head-tools">
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="releases-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
      </label>
      <span class="badge badge-light"><?= (int)$totalAdminReleases ?></span>
    </div>
  </div>

  <?php if (!$allReleases): ?>
    <p data-admin-t="releases_empty_all">Ainda não existem lançamentos registados.</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th data-admin-t="products_image">Capa</th>
            <th data-admin-t="label_title">Titulo</th>
            <th data-admin-t="label_artist">Artista</th>
            <th data-admin-t="profile_table_type">Tipo</th>
            <th data-admin-t="label_tracks">Faixas</th>
            <th class="col-audio" data-admin-t="releases_audio">Audio</th>
            <th data-admin-t="categories_state">Estado</th>
            <th data-admin-t="orders_action">Acao</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allReleases as $release): ?>
            <tr data-review-type="release" data-review-id="<?= (int)$release['idRelease'] ?>" data-admin-state="<?= h($release['estado']) ?>">
              <td>#<?= (int)$release['idRelease'] ?></td>
              <td class="col-audio">
                <div class="admin-table-thumb">
                  <?php if (!empty($release['capa'])): ?>
                    <img src="../assets/img/<?= h($release['capa']) ?>" alt="">
                  <?php else: ?>
                    <span data-admin-t="products_no_image">Sem imagem</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <strong><?= h($release['titulo']) ?></strong>
                <?php if (!empty($release['motivo_rejeicao'])): ?>
                  <br><span class="color-text3"><?= h($release['motivo_rejeicao']) ?></span>
                <?php endif; ?>
              </td>
              <td><?= h($release['artista']) ?></td>
              <td><?= h($release['tipo']) ?></td>
              <td><?= (int)$release['total_faixas'] ?></td>
              <td class="admin-audio-cell">
                <?php if (!empty($releaseTracks[(int)$release['idRelease']])): ?>
                  <div class="admin-audio-item admin-audio-item--table">
                    <button type="button" class="admin-audio-toggle" aria-expanded="false">
                      <span data-admin-t="releases_tracks">Faixas</span>
                      <span data-admin-t="releases_listen">Ouvir</span>
                    </button>
                    <div class="admin-audio-menu" hidden>
                      <?php foreach ($releaseTracks[(int)$release['idRelease']] as $track): ?>
                        <div class="admin-audio-row">
                          <strong><?= (int)$track['numero_faixa'] ?>. <?= h($track['titulo']) ?></strong>
                          <?php if (!empty($track['ficheiro_audio'])): ?>
                            <div class="admin-mini-player" data-audio-src="<?= h(asset_url('audio', $track['ficheiro_audio'])) ?>">
                              <button type="button" class="admin-mini-play" aria-label="Play">></button>
                              <span class="admin-mini-time">0:00</span>
                              <div class="admin-mini-track"><div></div></div>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php else: ?>
                  <span class="color-text3" data-admin-t="releases_no_tracks">Sem faixas</span>
                <?php endif; ?>
              </td>
              <td><span class="badge <?= h(state_badge_class($release['estado'])) ?>"><?= h(order_status_label($release['estado'])) ?></span></td>
              <td>
                <div class="admin-row-actions">
                <details class="admin-inline-editor">
                  <summary class="btn btn-ghost btn-sm" data-admin-t="btn_edit">Editar</summary>
                  <form method="post" class="admin-inline-edit-form" enctype="multipart/form-data" data-confirm="Inativar este lancamento? As faixas deixam de aparecer no site publico ate ser reativado." data-confirm-if-state="inativo">
                    <?= csrf_input() ?>
                    <input type="hidden" name="release_id" value="<?= (int)$release['idRelease'] ?>">
                    <label><span data-admin-t="label_title">Titulo</span><input name="titulo" class="finput" value="<?= h($release['titulo']) ?>" required></label>
                    <div class="admin-inline-edit-pair">
                      <label><span data-admin-t="profile_table_type">Tipo</span><select name="tipo" class="finput">
                        <?php foreach (['Single', 'EP', 'Album'] as $type): ?>
                          <option value="<?= h($type) ?>" <?= $type === (string)$release['tipo'] ? 'selected' : '' ?>><?= h(release_type_label($type)) ?></option>
                        <?php endforeach; ?>
                      </select></label>
                      <label><span data-admin-t="label_release_date">Lancamento</span><input type="date" name="data_lancamento" class="finput" value="<?= h((string)($release['data_lancamento'] ?? '')) ?>"></label>
                    </div>
                    <label><span data-admin-t="label_description">Descricao</span><textarea name="descricao" class="finput"><?= h($release['descricao'] ?? '') ?></textarea></label>
                    <label><span data-admin-t="categories_state">Estado</span><select name="estado" class="finput">
                      <?php foreach (['pendente', 'aprovado', 'rejeitado', 'inativo'] as $state): ?>
                        <option value="<?= h($state) ?>" <?= $state === (string)$release['estado'] ? 'selected' : '' ?>><?= h(order_status_label($state)) ?></option>
                      <?php endforeach; ?>
                    </select></label>
                    <div>
                      <span class="admin-modal-label" data-admin-t="products_image">Capa</span>
                      <div class="admin-inline-media-grid">
                        <?php if (!empty($release['capa'])): ?>
                          <span class="admin-inline-media-item"><img src="../assets/img/<?= h($release['capa']) ?>" alt=""></span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <label><span data-admin-t="btn_replace_cover">Substituir capa</span><input type="file" name="capa" class="finput" accept=".jpg,.jpeg,.png,.webp"></label>
                    <?php if (!empty($releaseTracks[(int)$release['idRelease']])): ?>
                      <div class="admin-track-edit-list">
                        <span class="admin-modal-label" data-admin-t="releases_tracks">Faixas</span>
                        <?php foreach ($releaseTracks[(int)$release['idRelease']] as $track): ?>
                          <div class="admin-track-edit-row">
                            <label><span data-admin-t="label_title">Titulo</span><input name="track_title[<?= (int)$track['idFaixa'] ?>]" class="finput" value="<?= h($track['titulo']) ?>"></label>
                            <label><span data-admin-t="label_genre">Genero</span><input name="track_genre[<?= (int)$track['idFaixa'] ?>]" class="finput" value="<?= h($track['genero'] ?? '') ?>"></label>
                            <label><span data-admin-t="btn_replace_audio">Substituir audio</span><input type="file" name="track_audio[<?= (int)$track['idFaixa'] ?>]" class="finput" accept=".mp3,.wav,.ogg,.flac,.m4a,.mp4"></label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                    <div class="admin-action-buttons">
                      <button type="submit" name="action" value="guardar" class="btn btn-dark btn-sm" data-admin-t="btn_save_changes">Guardar alteracoes</button>
                    </div>
                  </form>
                </details>
                <form method="post">
                  <?= csrf_input() ?>
                  <input type="hidden" name="release_id" value="<?= (int)$release['idRelease'] ?>">
                  <?php if ($release['estado'] === 'aprovado' && (int)$release['ativo'] === 1): ?>
                    <button type="submit" name="action" value="inativar" class="btn btn-ghost btn-sm" data-confirm="Inativar este lancamento?" data-admin-t="btn_deactivate">Inativar</button>
                  <?php elseif ($release['estado'] !== 'pendente' && $release['estado'] !== 'rejeitado'): ?>
                    <button type="submit" name="action" value="reativar" class="btn btn-ghost btn-sm" data-admin-t="btn_reactivate">Reativar</button>
                  <?php elseif ($release['estado'] === 'rejeitado'): ?>
                    <span class="color-text3" data-admin-t="state_rejected">Rejeitado</span>
                  <?php else: ?>
                    <span class="color-text3" data-admin-t="state_in_review">Em revisao</span>
                  <?php endif; ?>
                </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($adminReleasesTotalPages > 1): ?>
      <?php $otherPendingPageParam = isset($_GET['pending_page']) ? '&pending_page=' . (int)$_GET['pending_page'] : ''; ?>
      <nav class="pager" aria-label="Pagination">
        <?= $adminReleasesPage > 1 ? '<a class="btn btn-ghost btn-sm" href="releases.php?page=' . (int)($adminReleasesPage - 1) . $otherPendingPageParam . '" data-admin-t="pagination_previous">Anterior</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_previous">Anterior</span>' ?>
        <span class="pager-status" data-admin-page-status data-page-current="<?= (int)$adminReleasesPage ?>" data-page-total="<?= (int)$adminReleasesTotalPages ?>">Página <?= (int)$adminReleasesPage ?> de <?= (int)$adminReleasesTotalPages ?></span>
        <?= $adminReleasesPage < $adminReleasesTotalPages ? '<a class="btn btn-ghost btn-sm" href="releases.php?page=' . (int)($adminReleasesPage + 1) . $otherPendingPageParam . '" data-admin-t="pagination_next">Seguinte</a>' : '<span class="btn btn-ghost btn-sm is-disabled" data-admin-t="pagination_next">Seguinte</span>' ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
</div>

<script>
(() => {
  let activeAudioItem = null;
  let activeAudio = null;
  let activePlayer = null;

  function positionAudioMenu(item) {
    const menu = item?._floatingMenu;
    if (!menu || menu.hidden) return;
    const rect = item.getBoundingClientRect();
    const gap = 8;
    const menuWidth = Math.min(340, window.innerWidth - 32);
    const left = Math.min(Math.max(16, rect.left), window.innerWidth - menuWidth - 16);
    menu.style.width = `${menuWidth}px`;
    menu.style.left = `${left}px`;
    menu.style.top = `${rect.bottom + gap}px`;
  }

  function closeAudioMenu(item) {
    if (!item) return;
    const menu = item._floatingMenu;
    const button = item.querySelector('.admin-audio-toggle');
    if (menu) {
      menu.hidden = true;
      menu.classList.remove('is-floating');
      menu.removeAttribute('style');
      if (menu.parentElement !== item) item.appendChild(menu);
    }
    button?.setAttribute('aria-expanded', 'false');
    item.classList.remove('is-open');
    if (activeAudioItem === item) activeAudioItem = null;
  }

  document.addEventListener('click', (event) => {
    if (!activeAudioItem) return;
    const menu = activeAudioItem._floatingMenu;
    if (activeAudioItem.contains(event.target) || menu?.contains(event.target)) return;
    closeAudioMenu(activeAudioItem);
  });

  window.addEventListener('scroll', () => positionAudioMenu(activeAudioItem), true);
  window.addEventListener('resize', () => positionAudioMenu(activeAudioItem));

  function formatTime(seconds) {
    if (!Number.isFinite(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
    return `${mins}:${secs}`;
  }

  function resetPlayer(player) {
    if (!player) return;
    player.classList.remove('is-playing');
    const button = player.querySelector('.admin-mini-play');
    const fill = player.querySelector('.admin-mini-track div');
    if (button) button.textContent = '>';
    if (fill) fill.style.width = '0%';
  }

  function initReleaseControls(root = document) {
    root.querySelectorAll('.admin-audio-item--table').forEach((item) => {
      if (item.dataset.releaseAudioBound === '1') return;
      item.dataset.releaseAudioBound = '1';
      const button = item.querySelector('.admin-audio-toggle');
      const menu = item.querySelector('.admin-audio-menu');
      if (!menu) return;
      item._floatingMenu = menu;

      button?.addEventListener('click', (event) => {
        event.stopPropagation();
        if (activeAudioItem === item && !menu.hidden) {
          closeAudioMenu(item);
          return;
        }

        if (activeAudioItem && activeAudioItem !== item) closeAudioMenu(activeAudioItem);

        activeAudioItem = item;
        item.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        menu.hidden = false;
        menu.classList.add('is-floating');
        (item.closest('.admin-shell') || document.body).appendChild(menu);
        positionAudioMenu(item);
      });
    });

    root.querySelectorAll('.admin-mini-player').forEach((player) => {
      if (player.dataset.releasePlayerBound === '1') return;
      player.dataset.releasePlayerBound = '1';
      const button = player.querySelector('.admin-mini-play');
      const time = player.querySelector('.admin-mini-time');
      const fill = player.querySelector('.admin-mini-track div');
      const track = player.querySelector('.admin-mini-track');
      const audio = new Audio(player.dataset.audioSrc);
      audio.preload = 'metadata';

      button?.addEventListener('click', async () => {
        if (activeAudio && activeAudio !== audio) {
          activeAudio.pause();
          resetPlayer(activePlayer);
        }

        if (audio.paused) {
          activeAudio = audio;
          activePlayer = player;
          await audio.play();
          player.classList.add('is-playing');
          button.textContent = 'II';
        } else {
          audio.pause();
          resetPlayer(player);
        }
      });

      audio.addEventListener('timeupdate', () => {
        if (time) time.textContent = formatTime(audio.currentTime);
        if (fill && audio.duration) fill.style.width = `${Math.min(100, (audio.currentTime / audio.duration) * 100)}%`;
      });

      function seekFromEvent(event) {
        if (!audio.duration) return;
        const box = track.getBoundingClientRect();
        const ratio = Math.min(1, Math.max(0, (event.clientX - box.left) / box.width));
        audio.currentTime = ratio * audio.duration;
        if (fill) fill.style.width = `${ratio * 100}%`;
      }

      track?.addEventListener('click', seekFromEvent);
      track?.addEventListener('pointerdown', (event) => {
        seekFromEvent(event);
        track.setPointerCapture(event.pointerId);
        const move = (moveEvent) => seekFromEvent(moveEvent);
        const up = () => {
          track.removeEventListener('pointermove', move);
          track.removeEventListener('pointerup', up);
          track.removeEventListener('pointercancel', up);
        };
        track.addEventListener('pointermove', move);
        track.addEventListener('pointerup', up);
        track.addEventListener('pointercancel', up);
      });

      audio.addEventListener('ended', () => resetPlayer(player));
    });
  }

  initReleaseControls();

  document.addEventListener('click', async (event) => {
    const link = event.target.closest('#releases-search .pager a[href]');
    if (!link) return;
    event.preventDefault();
    const scope = document.getElementById('releases-search');
    if (!scope) return;
    scope.classList.add('is-loading');
    try {
      const response = await fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const html = await response.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const nextScope = doc.getElementById('releases-search');
      if (!nextScope) {
        window.location.href = link.href;
        return;
      }
      scope.innerHTML = nextScope.innerHTML;
      history.pushState(null, '', link.href);
      initReleaseControls(scope);
      window.GreenerryApplyAdminLang?.(localStorage.getItem('g_lang') || 'pt');
      scope.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (_) {
      window.location.href = link.href;
    } finally {
      scope.classList.remove('is-loading');
    }
  });
})();
</script>

<?php include 'admin_footer.php'; ?>

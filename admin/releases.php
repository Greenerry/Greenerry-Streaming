<?php
require_once '../includes/config.php';
require_admin_login();

$adminId = current_admin_id();
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = verify_csrf_request() ?? '';
    $releaseId = (int)($_POST['release_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $reasonSafe = db_escape($conn, $reason);

    if ($feedback === '' && $releaseId > 0 && in_array($action, ['aprovar', 'rejeitar', 'inativar', 'reativar'], true)) {
        $releaseActionRow = db_one($conn, "SELECT estado FROM release_musical WHERE idRelease = {$releaseId} LIMIT 1");
        $currentReleaseState = (string)($releaseActionRow['estado'] ?? '');

        if ($action === 'aprovar') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'aprovado', motivo_rejeicao = NULL, idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 1 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'aprovada', ativo = 1 WHERE idRelease = {$releaseId}");
            $feedback = tr('success.release_approved');
        } elseif ($action === 'rejeitar') {
            $tracksToDelete = db_all($conn, "SELECT ficheiro_audio FROM faixa WHERE idRelease = {$releaseId}");
            foreach ($tracksToDelete as $trackToDelete) {
                delete_asset_file('audio', $trackToDelete['ficheiro_audio'] ?? '');
            }

            mysqli_query($conn, "UPDATE release_musical SET estado = 'rejeitado', motivo_rejeicao = '{$reasonSafe}', idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 0 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'rejeitada', ativo = 0, ficheiro_audio = '' WHERE idRelease = {$releaseId}");
            $feedback = tr('success.release_rejected');
        } elseif ($action === 'inativar') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'inativo', ativo = 0, bloqueado_admin = 1 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'inativa', ativo = 0 WHERE idRelease = {$releaseId}");
            $feedback = tr('success.release_deactivated');
        } elseif ($action === 'reativar' && $currentReleaseState !== 'rejeitado') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'aprovado', ativo = 1, bloqueado_admin = 0 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'aprovada', ativo = 1 WHERE idRelease = {$releaseId}");
            $feedback = tr('success.release_reactivated');
        }
    }
}

$pending = db_all(
    $conn,
    "SELECT r.*, c.nome AS artista,
            COUNT(f.idFaixa) AS total_faixas
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     LEFT JOIN faixa f ON f.idRelease = r.idRelease
     WHERE r.estado = 'pendente'
     GROUP BY r.idRelease
     ORDER BY r.created_at DESC"
);

$allReleases = db_all(
    $conn,
    "SELECT r.*, c.nome AS artista,
            COUNT(f.idFaixa) AS total_faixas
     FROM release_musical r
     JOIN cliente c ON c.idCliente = r.idCliente
     LEFT JOIN faixa f ON f.idRelease = r.idRelease
     GROUP BY r.idRelease
     ORDER BY r.created_at DESC"
);

$releaseTracks = [];
if ($allReleases) {
    $releaseIds = implode(',', array_map(static fn($release) => (int)$release['idRelease'], $allReleases));
    $trackRows = db_all(
        $conn,
        "SELECT idRelease, numero_faixa, titulo, ficheiro_audio
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

foreach ($allReleases as $release) {
    $state = (string)($release['estado'] ?? '');
    if ($state === 'pendente') {
        $releaseStats['pendentes']++;
    } elseif ($state === 'aprovado') {
        $releaseStats['aprovados']++;
    } elseif ($state === 'rejeitado') {
        $releaseStats['rejeitados']++;
    } elseif ($state === 'inativo') {
        $releaseStats['inativos']++;
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="releases_title">Lancamentos</h2>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat"><div class="stat-val"><?= (int)$releaseStats['pendentes'] ?></div><div class="stat-lbl" data-admin-t="state_pending">Pendentes</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$releaseStats['aprovados'] ?></div><div class="stat-lbl" data-admin-t="state_approved">Aprovados</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$releaseStats['rejeitados'] ?></div><div class="stat-lbl" data-admin-t="state_rejected">Rejeitados</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$releaseStats['inativos'] ?></div><div class="stat-lbl" data-admin-t="state_inactive">Inativos</div></div>
</div>

<div class="admin-search-row">
  <label class="sbar">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" data-admin-search="releases-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
  </label>
</div>

<div id="releases-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="releases_pending">Lancamentos pendentes</h4>
    <span class="badge badge-red"><?= count($pending) ?></span>
  </div>

  <?php if (!$pending): ?>
    <p data-admin-t="releases_empty_pending">Sem lancamentos pendentes.</p>
  <?php else: ?>
    <div class="admin-card-list">
      <?php foreach ($pending as $release): ?>
        <article class="admin-review-card">
          <div class="admin-review-main">
            <div class="admin-review-meta">
              <span class="badge badge-light"><?= h($release['tipo']) ?></span>
              <strong><?= h($release['titulo']) ?></strong>
              <p><span data-admin-t="label_artist">Artista</span>: <?= h($release['artista']) ?></p>
              <p><span data-admin-t="label_tracks">Faixas</span>: <?= (int)$release['total_faixas'] ?></p>
              <?php if (!empty($release['data_lancamento'])): ?>
                <p><span data-admin-t="label_release_date">Lancamento</span>: <?= date('d/m/Y', strtotime($release['data_lancamento'])) ?></p>
              <?php endif; ?>
              <?php if (!empty($release['descricao'])): ?>
                <p><?= h($release['descricao']) ?></p>
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
            <textarea name="reason" class="finput" placeholder="Motivo de rejeicao (recomendado se recusares o lancamento)." data-admin-tp="releases_reason_placeholder"></textarea>
            <div class="admin-action-buttons">
              <button type="submit" name="action" value="aprovar" class="btn btn-dark btn-sm" data-admin-t="btn_approve">Aprovar</button>
              <button type="submit" name="action" value="rejeitar" class="btn btn-danger btn-sm" data-admin-t="btn_reject">Rejeitar</button>
            </div>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="releases_all">Todos os lancamentos</h4>
  </div>

  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th data-admin-t="products_image">Capa</th>
          <th>Titulo</th>
          <th>Artista</th>
          <th>Tipo</th>
          <th>Faixas</th>
          <th class="col-audio" data-admin-t="releases_audio">Audio</th>
          <th>Estado</th>
          <th>Acao</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allReleases as $release): ?>
          <tr>
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
            <td>
              <?php if (!empty($releaseTracks[(int)$release['idRelease']])): ?>
                <details class="admin-audio-item admin-audio-item--table">
                  <summary>
                    <span data-admin-t="releases_tracks">Faixas</span>
                    <span data-admin-t="releases_listen">Ouvir</span>
                  </summary>
                  <div class="admin-audio-list">
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
                </details>
              <?php else: ?>
                <span class="color-text3" data-admin-t="releases_no_tracks">Sem faixas</span>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= h(state_badge_class($release['estado'])) ?>"><?= h(order_status_label($release['estado'])) ?></span></td>
            <td>
              <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="release_id" value="<?= (int)$release['idRelease'] ?>">
                <?php if ($release['estado'] === 'aprovado' && (int)$release['ativo'] === 1): ?>
                  <button type="submit" name="action" value="inativar" class="btn btn-ghost btn-sm" data-admin-t="btn_deactivate">Inativar</button>
                <?php elseif ($release['estado'] !== 'pendente' && $release['estado'] !== 'rejeitado'): ?>
                  <button type="submit" name="action" value="reativar" class="btn btn-ghost btn-sm" data-admin-t="btn_reactivate">Reativar</button>
                <?php elseif ($release['estado'] === 'rejeitado'): ?>
                  <span class="color-text3" data-admin-t="state_rejected">Rejeitado</span>
                <?php else: ?>
                  <span class="color-text3" data-admin-t="state_in_review">Em revisao</span>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
</div>

<script>
(() => {
  const players = Array.from(document.querySelectorAll('.admin-mini-player'));
  let activeAudio = null;
  let activePlayer = null;

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

  players.forEach((player) => {
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
      if (fill && audio.duration) {
        fill.style.width = `${Math.min(100, (audio.currentTime / audio.duration) * 100)}%`;
      }
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
})();
</script>

<?php include 'admin_footer.php'; ?>

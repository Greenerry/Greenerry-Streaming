<?php
require_once '../includes/config.php';
require_admin_login();

$adminId = current_admin_id();
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $releaseId = (int)($_POST['release_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $reasonSafe = db_escape($conn, $reason);

    if ($releaseId > 0 && in_array($action, ['aprovar', 'rejeitar', 'inativar', 'reativar'], true)) {
        if ($action === 'aprovar') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'aprovado', motivo_rejeicao = NULL, idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 1 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'aprovada', ativo = 1 WHERE idRelease = {$releaseId}");
            $feedback = 'Lancamento aprovado com sucesso.';
        } elseif ($action === 'rejeitar') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'rejeitado', motivo_rejeicao = '{$reasonSafe}', idAdminAprovacao = {$adminId}, aprovado_em = NOW(), ativo = 0 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'rejeitada', ativo = 0 WHERE idRelease = {$releaseId}");
            $feedback = 'Lancamento rejeitado.';
        } elseif ($action === 'inativar') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'inativo', ativo = 0 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'inativa', ativo = 0 WHERE idRelease = {$releaseId}");
            $feedback = 'Lancamento inativado.';
        } elseif ($action === 'reativar') {
            mysqli_query($conn, "UPDATE release_musical SET estado = 'aprovado', ativo = 1 WHERE idRelease = {$releaseId}");
            mysqli_query($conn, "UPDATE faixa SET estado = 'aprovada', ativo = 1 WHERE idRelease = {$releaseId}");
            $feedback = 'Lancamento reativado.';
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
  <div class="stat"><div class="stat-val"><?= (int)$releaseStats['pendentes'] ?></div><div class="stat-lbl">Pendentes</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$releaseStats['aprovados'] ?></div><div class="stat-lbl">Aprovados</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$releaseStats['rejeitados'] ?></div><div class="stat-lbl">Rejeitados</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$releaseStats['inativos'] ?></div><div class="stat-lbl">Inativos</div></div>
</div>

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
              <p>Artista: <?= h($release['artista']) ?></p>
              <p>Faixas: <?= (int)$release['total_faixas'] ?></p>
              <?php if (!empty($release['data_lancamento'])): ?>
                <p>Lancamento: <?= date('d/m/Y', strtotime($release['data_lancamento'])) ?></p>
              <?php endif; ?>
              <?php if (!empty($release['descricao'])): ?>
                <p><?= h($release['descricao']) ?></p>
              <?php endif; ?>
            </div>
            <?php if (!empty($release['capa'])): ?>
              <img src="../assets/img/<?= h($release['capa']) ?>" alt="" class="admin-review-image">
            <?php endif; ?>
          </div>

          <form method="post" class="admin-review-actions">
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
          <th>Titulo</th>
          <th>Artista</th>
          <th>Tipo</th>
          <th>Faixas</th>
          <th>Estado</th>
          <th>Acao</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allReleases as $release): ?>
          <tr>
            <td>#<?= (int)$release['idRelease'] ?></td>
            <td>
              <strong><?= h($release['titulo']) ?></strong>
              <?php if (!empty($release['motivo_rejeicao'])): ?>
                <br><span class="color-text3"><?= h($release['motivo_rejeicao']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= h($release['artista']) ?></td>
            <td><?= h($release['tipo']) ?></td>
            <td><?= (int)$release['total_faixas'] ?></td>
            <td><span class="badge <?= h(state_badge_class($release['estado'])) ?>"><?= h(order_status_label($release['estado'])) ?></span></td>
            <td>
              <form method="post">
                <input type="hidden" name="release_id" value="<?= (int)$release['idRelease'] ?>">
                <?php if ($release['estado'] === 'aprovado' && (int)$release['ativo'] === 1): ?>
                  <button type="submit" name="action" value="inativar" class="btn btn-ghost btn-sm">Inativar</button>
                <?php elseif ($release['estado'] !== 'pendente'): ?>
                  <button type="submit" name="action" value="reativar" class="btn btn-ghost btn-sm">Reativar</button>
                <?php else: ?>
                  <span class="color-text3">Em revisao</span>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include 'admin_footer.php'; ?>

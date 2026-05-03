<?php
require_once '../includes/config.php';
require_admin_login();

$adminId = current_admin_id();
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = verify_csrf_request() ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);
    $state = $_POST['state'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($feedback === '' && $requestId > 0 && in_array($state, ['em_analise', 'concluido', 'recusado'], true)) {
        $stateSafe = db_escape($conn, $state);
        $noteSafe = db_escape($conn, $adminNote);
        $resolvedSql = $state === 'em_analise' ? 'NULL' : 'NOW()';

        mysqli_query(
            $conn,
            "UPDATE pedido_reset_password
             SET estado = '{$stateSafe}', observacoes_admin = '{$noteSafe}', idAdmin = {$adminId}, resolved_at = {$resolvedSql}
             WHERE idPedidoReset = {$requestId}"
        );
        $feedback = tr('success.reset_updated');
    }
}

$requests = db_all(
    $conn,
    "SELECT pr.*, c.nome AS cliente_nome, a.nome AS admin_nome
     FROM pedido_reset_password pr
     JOIN cliente c ON c.idCliente = pr.idCliente
     LEFT JOIN admin a ON a.idAdmin = pr.idAdmin
     ORDER BY FIELD(pr.estado, 'pendente', 'em_analise', 'concluido', 'recusado'), pr.created_at DESC"
);

$requestStats = [
    'pendentes' => 0,
    'em_analise' => 0,
    'concluidos' => 0,
    'recusados' => 0,
];

foreach ($requests as $request) {
    $state = (string)($request['estado'] ?? '');
    if ($state === 'pendente') {
        $requestStats['pendentes']++;
    } elseif ($state === 'em_analise') {
        $requestStats['em_analise']++;
    } elseif ($state === 'concluido') {
        $requestStats['concluidos']++;
    } elseif ($state === 'recusado') {
        $requestStats['recusados']++;
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="password_title">Pedidos de recuperacao</h2>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>

<section class="stats-grid">
  <div class="stat"><div class="stat-val"><?= (int)$requestStats['pendentes'] ?></div><div class="stat-lbl" data-admin-t="state_pending">Pendentes</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$requestStats['em_analise'] ?></div><div class="stat-lbl" data-admin-t="password_state_review">Em analise</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$requestStats['concluidos'] ?></div><div class="stat-lbl" data-admin-t="password_state_done">Concluidos</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$requestStats['recusados'] ?></div><div class="stat-lbl" data-admin-t="password_state_refused">Recusados</div></div>
</section>

<div class="admin-search-row">
  <label class="sbar">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" data-admin-search="password-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
  </label>
</div>

<div id="password-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="password_requests_all">Pedidos registados</h4>
  </div>

  <?php if (!$requests): ?>
    <p data-admin-t="password_empty">Sem pedidos de recuperacao.</p>
  <?php else: ?>
    <div class="admin-card-list">
      <?php foreach ($requests as $request): ?>
        <article class="admin-review-card">
          <div class="admin-review-main">
            <div class="admin-review-meta">
              <span class="badge <?= h(state_badge_class($request['estado'])) ?>"><?= h(order_status_label($request['estado'])) ?></span>
              <strong><?= h($request['cliente_nome']) ?></strong>
              <p><?= h($request['email']) ?></p>
              <?php if (!empty($request['motivo'])): ?>
                <p><?= nl2br(h($request['motivo'])) ?></p>
              <?php endif; ?>
              <?php if (!empty($request['observacoes_admin'])): ?>
                <div class="message-reply-box">
                  <span class="slabel"><span data-admin-t="password_admin_note">Nota do admin</span><?= !empty($request['admin_nome']) ? ' - ' . h($request['admin_nome']) : '' ?></span>
                  <p><?= nl2br(h($request['observacoes_admin'])) ?></p>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <form method="post" class="admin-review-actions">
            <?= csrf_input() ?>
            <input type="hidden" name="request_id" value="<?= (int)$request['idPedidoReset'] ?>">
            <textarea name="admin_note" class="finput" placeholder="Nota interna ou informacao sobre a resolucao do pedido." data-admin-tp="password_note_placeholder"></textarea>
            <div class="frow">
              <div class="fg">
                <label class="flabel" data-admin-t="password_new_state">Novo estado</label>
                <select name="state" class="finput">
                  <option value="em_analise" data-admin-t="password_state_review">Em analise</option>
                  <option value="concluido" data-admin-t="password_state_done">Concluido</option>
                  <option value="recusado" data-admin-t="password_state_refused">Recusado</option>
                </select>
              </div>
              <div class="fg admin-inline-align">
                <button type="submit" class="btn btn-dark" data-admin-t="password_update">Atualizar pedido</button>
              </div>
            </div>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
</div>

<?php include 'admin_footer.php'; ?>

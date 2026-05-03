<?php
require_once '../includes/config.php';
require_admin_login();

$adminId = current_admin_id();
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = verify_csrf_request() ?? '';
    $messageId = (int)($_POST['message_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    $state = $_POST['state'] ?? 'respondida';

    if ($feedback === '' && $messageId > 0 && $reply !== '') {
        $replySafe = db_escape($conn, $reply);
        $stateSafe = db_escape($conn, in_array($state, ['respondida', 'fechada'], true) ? $state : 'respondida');

        mysqli_query(
            $conn,
            "UPDATE mensagem_admin
             SET resposta_admin = '{$replySafe}', estado = '{$stateSafe}', idAdminResposta = {$adminId}, responded_at = NOW()
             WHERE idMensagem = {$messageId}"
        );
        $feedback = tr('success.admin_reply_sent');
    }
}

$messages = db_all(
    $conn,
    "SELECT m.*, c.nome AS cliente_nome, c.email AS cliente_email, a.nome AS admin_nome
     FROM mensagem_admin m
     JOIN cliente c ON c.idCliente = m.idCliente
     LEFT JOIN admin a ON a.idAdmin = m.idAdminResposta
     ORDER BY FIELD(m.estado, 'aberta', 'respondida', 'fechada'), m.created_at DESC"
);

$messageStats = [
    'abertas' => 0,
    'respondidas' => 0,
    'fechadas' => 0,
];

foreach ($messages as $message) {
    $state = (string)($message['estado'] ?? '');
    if ($state === 'aberta') {
        $messageStats['abertas']++;
    } elseif ($state === 'respondida') {
        $messageStats['respondidas']++;
    } elseif ($state === 'fechada') {
        $messageStats['fechadas']++;
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="messages_title">Mensagens</h2>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>

<section class="stats-grid">
  <div class="stat"><div class="stat-val"><?= (int)$messageStats['abertas'] ?></div><div class="stat-lbl" data-admin-t="messages_open">Em aberto</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$messageStats['respondidas'] ?></div><div class="stat-lbl" data-admin-t="messages_answered">Respondidas</div></div>
  <div class="stat"><div class="stat-val"><?= (int)$messageStats['fechadas'] ?></div><div class="stat-lbl" data-admin-t="messages_closed">Fechadas</div></div>
</section>

<div class="admin-search-row">
  <label class="sbar">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" data-admin-search="messages-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
  </label>
</div>

<div id="messages-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="messages_inbox">Inbox</h4>
  </div>

  <?php if (!$messages): ?>
    <p data-admin-t="messages_empty">Sem mensagens recebidas.</p>
  <?php else: ?>
    <div class="admin-card-list">
      <?php foreach ($messages as $message): ?>
        <article class="admin-review-card">
          <div class="admin-review-main">
            <div class="admin-review-meta">
              <span class="badge <?= h(state_badge_class($message['estado'])) ?>"><?= h(order_status_label($message['estado'])) ?></span>
              <strong><?= h($message['assunto']) ?></strong>
              <p><?= h($message['cliente_nome']) ?> - <?= h($message['cliente_email']) ?></p>
              <p><?= nl2br(h($message['mensagem'])) ?></p>
              <?php if (!empty($message['resposta_admin'])): ?>
                <div class="message-reply-box">
                  <span class="slabel"><span data-admin-t="messages_current_reply">Resposta atual</span><?= !empty($message['admin_nome']) ? ' - ' . h($message['admin_nome']) : '' ?></span>
                  <p><?= nl2br(h($message['resposta_admin'])) ?></p>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <form method="post" class="admin-review-actions">
            <?= csrf_input() ?>
            <input type="hidden" name="message_id" value="<?= (int)$message['idMensagem'] ?>">
            <textarea name="reply" class="finput" placeholder="Escreve aqui a resposta do admin." data-admin-tp="messages_reply_placeholder"></textarea>
            <div class="frow">
              <div class="fg">
                <label class="flabel" data-admin-t="messages_state_after">Estado apos resposta</label>
                <select name="state" class="finput">
                  <option value="respondida" data-admin-t="messages_state_answered">Respondida</option>
                  <option value="fechada" data-admin-t="messages_state_closed">Fechada</option>
                </select>
              </div>
              <div class="fg admin-inline-align">
                <button type="submit" class="btn btn-dark" data-admin-t="messages_save_reply">Guardar resposta</button>
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

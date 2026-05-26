<?php
require_once '../includes/config.php';
require_admin_permission('messages');

$adminId = current_admin_id();
$feedback = '';
$showAllMessages = (int)($_GET['all'] ?? 0) === 1;
$messageLimit = 3;
$allowedMessageStates = ['aberta', 'respondida', 'fechada'];
$requestedMessageState = (string)($_GET['state'] ?? 'aberta');
$selectedMessageState = in_array($requestedMessageState, $allowedMessageStates, true) ? $requestedMessageState : 'aberta';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = verify_csrf_request() ?? '';
    $messageId = (int)($_POST['message_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    $state = $_POST['state'] ?? 'respondida';

    if ($feedback === '' && $messageId > 0 && $reply !== '') {
        $messageOwner = db_one($conn, "SELECT idCliente, assunto FROM mensagem_admin WHERE idMensagem = {$messageId} LIMIT 1");
        $replySafe = db_escape($conn, $reply);
        $stateSafe = db_escape($conn, in_array($state, ['respondida', 'fechada'], true) ? $state : 'respondida');

        mysqli_query(
            $conn,
            "UPDATE mensagem_admin
             SET resposta_admin = '{$replySafe}', estado = '{$stateSafe}', idAdminResposta = {$adminId}, respondido_em = NOW()
             WHERE idMensagem = {$messageId}"
        );
        if ($messageOwner) {
            create_notification(
                $conn,
                (int)$messageOwner['idCliente'],
                'Resposta do admin',
                'O admin respondeu a tua mensagem: ' . (string)$messageOwner['assunto'],
                'mensagem'
            );
        }
        $feedback = tr('success.admin_reply_sent');
    }
}

$messageRows = db_all($conn, "SELECT estado, COUNT(*) AS total FROM mensagem_admin GROUP BY estado");
$messageStats = [
    'abertas' => 0,
    'respondidas' => 0,
    'fechadas' => 0,
];

foreach ($messageRows as $row) {
    $state = (string)($row['estado'] ?? '');
    if ($state === 'aberta') {
        $messageStats['abertas'] = (int)$row['total'];
    } elseif ($state === 'respondida') {
        $messageStats['respondidas'] = (int)$row['total'];
    } elseif ($state === 'fechada') {
        $messageStats['fechadas'] = (int)$row['total'];
    }
}

$openMessagesTotal = $messageStats['abertas'];

$messages = db_all(
    $conn,
    "SELECT m.*, c.nome AS cliente_nome, c.email AS cliente_email, a.nome AS admin_nome
     FROM mensagem_admin m
     JOIN cliente c ON c.idCliente = m.idCliente
     LEFT JOIN admin a ON a.idAdmin = m.idAdminResposta
     WHERE m.estado = '{$selectedMessageState}'
     ORDER BY m.criado_em DESC" . ($showAllMessages ? "" : " LIMIT {$messageLimit}")
);

$selectedMessageTotal = $messageStats[
    $selectedMessageState === 'aberta' ? 'abertas' : ($selectedMessageState === 'respondida' ? 'respondidas' : 'fechadas')
];
$messageListTitleKey = $selectedMessageState === 'aberta' ? 'messages_open' : ($selectedMessageState === 'respondida' ? 'messages_answered' : 'messages_closed');

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="messages_kicker">Support desk</span>
    <h2 data-admin-t="messages_title">Mensagens</h2>
    <p data-admin-t="messages_intro">Responde aos clientes e fecha conversas de suporte.</p>
  </div>
  <section class="stats-grid admin-top-stats admin-top-stats--three">
    <a href="messages.php?state=aberta" class="stat stat-link <?= $selectedMessageState === 'aberta' ? 'is-active' : '' ?>"><div class="stat-val"><?= (int)$messageStats['abertas'] ?></div><div class="stat-lbl" data-admin-t="messages_open">Em aberto</div></a>
    <a href="messages.php?state=respondida" class="stat stat-link <?= $selectedMessageState === 'respondida' ? 'is-active' : '' ?>"><div class="stat-val"><?= (int)$messageStats['respondidas'] ?></div><div class="stat-lbl" data-admin-t="messages_answered">Respondidas</div></a>
    <a href="messages.php?state=fechada" class="stat stat-link <?= $selectedMessageState === 'fechada' ? 'is-active' : '' ?>"><div class="stat-val"><?= (int)$messageStats['fechadas'] ?></div><div class="stat-lbl" data-admin-t="messages_closed">Fechadas</div></a>
  </section>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>

<div id="messages-search" data-admin-search-scope>
<section class="acard-box">
  <div class="acard-box-head">
    <h4 data-admin-t="<?= h($messageListTitleKey) ?>">Inbox</h4>
    <div class="admin-card-head-tools">
      <label class="sbar admin-section-search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" data-admin-search="messages-search" placeholder="Pesquisar..." data-admin-tp="admin_search_placeholder">
      </label>
      <span class="badge badge-light"><?= count($messages) ?>/<?= (int)$selectedMessageTotal ?></span>
    </div>
  </div>

  <?php if ($selectedMessageTotal > $messageLimit): ?>
    <div class="admin-history-tools">
      <p>
        <span data-admin-t="messages_recent">A mostrar as mensagens mais recentes.</span>
        <span><?= min($selectedMessageTotal, $showAllMessages ? $selectedMessageTotal : $messageLimit) ?>/<?= (int)$selectedMessageTotal ?></span>
      </p>
      <a class="btn btn-ghost btn-sm" href="messages.php?state=<?= h($selectedMessageState) ?><?= $showAllMessages ? '' : '&all=1' ?>" data-admin-t="<?= $showAllMessages ? 'messages_show_recent' : 'messages_show_all' ?>">
        <?= $showAllMessages ? 'Mostrar recentes' : 'Mostrar todas' ?>
      </a>
    </div>
  <?php endif; ?>

  <?php if (!$messages): ?>
    <p data-admin-t="messages_empty_filtered">Sem mensagens nesta lista.</p>
  <?php else: ?>
    <div class="admin-card-list">
      <?php foreach ($messages as $message): ?>
        <article class="admin-review-card">
          <div class="admin-review-main">
            <div class="admin-review-meta">
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

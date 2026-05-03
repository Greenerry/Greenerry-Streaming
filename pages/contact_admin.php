<?php
require_once '../includes/config.php';
require_user_login();

$err = '';
$ok = '';
$assuntoValue = trim($_POST['assunto'] ?? '');
$mensagemValue = trim($_POST['mensagem'] ?? '');
$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = verify_csrf_request();

    if (!$err && $assuntoValue === '') {
        $err = tr('error.subject_required');
    } elseif (!$err && mb_strlen($assuntoValue) > 160) {
        $err = tr('error.subject_long');
    } elseif (!$err && $mensagemValue === '') {
        $err = tr('error.message_required');
    } elseif (!$err && mb_strlen($mensagemValue) < 10) {
        $err = tr('error.message_short');
    }

    if (!$err) {
        $assuntoSafe = db_escape($conn, $assuntoValue);
        $mensagemSafe = db_escape($conn, $mensagemValue);
        $sql = "INSERT INTO mensagem_admin (idCliente, assunto, mensagem, estado)
                VALUES ({$uid}, '{$assuntoSafe}', '{$mensagemSafe}', 'aberta')";

        if (mysqli_query($conn, $sql)) {
            $ok = tr('success.message_sent');
            $assuntoValue = '';
            $mensagemValue = '';
        } else {
            $err = tr('error.message_send');
        }
    }
}

$messages = db_all(
    $conn,
    "SELECT m.*, a.nome AS admin_nome
     FROM mensagem_admin m
     LEFT JOIN admin a ON a.idAdmin = m.idAdminResposta
     WHERE m.idCliente = {$uid}
     ORDER BY m.created_at DESC"
);

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="support-hero hero-card--single">
      <div class="support-hero-copy">
        <span class="slabel" data-t="contact_label">Contacto</span>
        <h2 data-t="contact_title">Fala com o admin.</h2>
      </div>
    </div>

    <div class="two-column-layout">
      <div class="card surface-card surface-card--soft">
        <div class="card-body">
          <?php if ($err): ?>
            <div class="alert alert-err"><?= h($err) ?></div>
          <?php endif; ?>
          <?php if ($ok): ?>
            <div class="alert alert-ok"><?= h($ok) ?></div>
          <?php endif; ?>

          <form method="post" class="stack-form" novalidate>
            <?= csrf_input() ?>
            <div class="fg">
              <label class="flabel" for="assunto" data-t="contact_subject">Assunto</label>
              <input id="assunto" type="text" name="assunto" class="finput" required maxlength="160" value="<?= h($assuntoValue) ?>">
            </div>

            <div class="fg">
              <label class="flabel" for="mensagem" data-t="contact_message">Mensagem</label>
              <textarea id="mensagem" name="mensagem" class="finput" required maxlength="3000" data-tp="contact_message_placeholder" placeholder="Explica o que precisas de forma clara."><?= h($mensagemValue) ?></textarea>
            </div>

            <button type="submit" class="btn btn-dark" data-t="contact_submit">Enviar mensagem</button>
          </form>
        </div>
      </div>

      <div class="card surface-card surface-card--soft">
        <div class="card-body">
          <h3 class="section-card-title" data-t="contact_history">Historico</h3>
          <?php if (!$messages): ?>
            <p data-t="contact_empty">Ainda nao enviaste nenhuma mensagem.</p>
          <?php else: ?>
            <div class="message-thread-list">
              <?php foreach ($messages as $message): ?>
                <article class="message-thread-item">
                  <div class="message-thread-head">
                    <strong><?= h($message['assunto']) ?></strong>
                    <span class="badge <?= $message['estado'] === 'respondida' ? 'badge-blue' : 'badge-light' ?>"><?= h(order_status_label($message['estado'])) ?></span>
                  </div>
                  <p class="message-thread-meta"><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></p>
                  <p><?= nl2br(h($message['mensagem'])) ?></p>

                  <?php if (!empty($message['resposta_admin'])): ?>
                    <div class="message-reply-box">
                      <span class="slabel"><?= h(tr('messages_reply_label')) ?><?= !empty($message['admin_nome']) ? ' - ' . h($message['admin_nome']) : '' ?></span>
                      <p><?= nl2br(h($message['resposta_admin'])) ?></p>
                    </div>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

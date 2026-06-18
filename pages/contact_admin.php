<?php
// Page purpose: Lets the user send support messages to the administration team.
// Keep this page simple: prepare data first, then render the view.
require_once '../includes/config.php';
require_user_login();

$err = '';
$ok = '';
$assuntoValue = trim($_POST['assunto'] ?? '');
$mensagemValue = trim($_POST['mensagem'] ?? '');
$uid = current_user_id();
$messageLimit = 5;
$pageNumber = max(1, (int)($_GET['page'] ?? 1));

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
        if (db_prepared(
            $conn,
            "INSERT INTO mensagem_admin (idCliente, assunto, mensagem, estado)
             VALUES (?, ?, ?, 'aberta')",
            'iss',
            [$uid, $assuntoValue, $mensagemValue]
        )) {
            $ok = tr('success.message_sent');
            $assuntoValue = '';
            $mensagemValue = '';
        } else {
            $err = tr('error.message_send');
        }
    }
}

$totalMessages = (int)(db_one(
    $conn,
    "SELECT COUNT(*) AS total FROM mensagem_admin WHERE idCliente = {$uid}"
)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalMessages / $messageLimit));
$pageNumber = min($pageNumber, $totalPages);
$messageOffset = ($pageNumber - 1) * $messageLimit;

$messages = db_all(
    $conn,
    "SELECT m.*, a.nome AS admin_nome
     FROM mensagem_admin m
     LEFT JOIN admin a ON a.idAdmin = m.idAdminResposta
     WHERE m.idCliente = {$uid}
     ORDER BY m.criado_em DESC
     LIMIT {$messageLimit} OFFSET {$messageOffset}"
);

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="support-plain-title">
      <h2 data-t="contact_title">Falar com o admin</h2>
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
            <p data-t="contact_empty">Ainda não enviaste nenhuma mensagem.</p>
          <?php else: ?>
            <div class="message-thread-list">
              <?php foreach ($messages as $message): ?>
                <article class="message-thread-item">
                  <div class="message-thread-head">
                    <strong><?= h($message['assunto']) ?></strong>
                  </div>
                  <p class="message-thread-meta"><?= date('d/m/Y H:i', strtotime($message['criado_em'])) ?></p>
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
          <?php if ($totalPages > 1): ?>
            <nav class="pager" aria-label="Pagination">
              <?php if ($pageNumber > 1): ?>
                <a class="btn btn-ghost btn-sm" href="contact_admin.php?page=<?= $pageNumber - 1 ?>" data-t="pagination_previous">Anterior</a>
              <?php else: ?>
                <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_previous">Anterior</span>
              <?php endif; ?>
              <span class="pager-status"><span data-t="pagination_page">Página</span> <?= $pageNumber ?> <span data-t="pagination_of">de</span> <?= $totalPages ?></span>
              <?php if ($pageNumber < $totalPages): ?>
                <a class="btn btn-ghost btn-sm" href="contact_admin.php?page=<?= $pageNumber + 1 ?>" data-t="pagination_next">Seguinte</a>
              <?php else: ?>
                <span class="btn btn-ghost btn-sm is-disabled" data-t="pagination_next">Seguinte</span>
              <?php endif; ?>
            </nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$feedback = '';

if (isset($_GET['go'])) {
    // Opening a notification marks it read before sending the user to its context.
    $goId = (int)$_GET['go'];
    if ($goId > 0) {
        $goNote = db_one_prepared(
            $conn,
            "SELECT * FROM notificacao WHERE idCliente = ? AND idNotificacao = ? LIMIT 1",
            'ii',
            [$uid, $goId]
        );
        if ($goNote) {
            db_prepared(
                $conn,
                "UPDATE notificacao SET lida = 1 WHERE idCliente = ? AND idNotificacao = ?",
                'ii',
                [$uid, $goId]
            );
            header('Location: ' . notification_context($conn, $goNote, $uid)['url']);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bulk and single read actions share this small handler.
    $feedback = verify_csrf_request() ?? '';
    $action = (string)($_POST['action'] ?? '');
    $noteId = (int)($_POST['notification_id'] ?? 0);

    if ($feedback === '') {
        if ($action === 'read_all') {
            db_prepared($conn, "UPDATE notificacao SET lida = 1 WHERE idCliente = ?", 'i', [$uid]);
            $feedback = tr('success.notifications_read');
        } elseif ($action === 'read' && $noteId > 0) {
            db_prepared(
                $conn,
                "UPDATE notificacao SET lida = 1 WHERE idCliente = ? AND idNotificacao = ?",
                'ii',
                [$uid, $noteId]
            );
            $feedback = tr('success.notification_read');
        }
    }
}

$notifications = db_all_prepared(
    $conn,
    "SELECT *
     FROM notificacao
     WHERE idCliente = ?
     ORDER BY criado_em DESC
     LIMIT 80",
    'i',
    [$uid]
);
$unreadTotal = 0;
foreach ($notifications as $note) {
    if ((int)$note['lida'] === 0) {
        $unreadTotal++;
    }
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap">
    <div class="page-intro page-intro--row">
      <div>
        <span class="slabel" data-t="notifications_label">Conta</span>
        <h2 data-t="notifications_title">Notificações</h2>
        <p data-t="notifications_intro">Atualizações sobre aprovações, mensagens e encomendas.</p>
      </div>
      <?php if ($unreadTotal > 0): ?>
        <form method="post">
          <?= csrf_input() ?>
          <input type="hidden" name="action" value="read_all">
          <button type="submit" class="btn btn-dark btn-sm" data-t="notifications_mark_all">Marcar tudo como lido</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($feedback): ?>
      <div class="alert alert-ok"><?= h($feedback) ?></div>
    <?php endif; ?>

    <section class="card surface-card notifications-card">
      <div class="card-body">
        <div class="between mb4">
          <h3 data-t="notifications_recent">Recentes</h3>
          <span class="badge badge-light notification-unread-badge"><?= (int)$unreadTotal ?> <span data-t="notifications_unread">por ler</span></span>
        </div>

        <?php if (!$notifications): ?>
          <p class="empty-copy" data-t="notifications_empty">Ainda não tens notificações.</p>
        <?php else: ?>
          <div class="notification-list">
            <?php foreach ($notifications as $note): ?>
              <?php $context = notification_context($conn, $note, $uid); ?>
              <?php // Resolve translated copy once so data attributes and visible text match. ?>
              <?php $displayNote = notification_display_text($note); ?>
              <?php $displayNotePt = notification_display_text($note, 'pt'); ?>
              <?php $displayNoteEn = notification_display_text($note, 'en'); ?>
              <article class="notification-item <?= (int)$note['lida'] === 0 ? 'is-unread' : '' ?>">
                <a href="notifications.php?go=<?= (int)$note['idNotificacao'] ?>" class="notification-main-link">
                  <span class="notification-media">
                    <?php if ($context['image'] !== ''): ?>
                      <img src="<?= h($context['image']) ?>" alt="">
                    <?php else: ?>
                      <?= notification_icon_svg((string)$note['tipo']) ?>
                    <?php endif; ?>
                  </span>
                  <span class="notification-copy">
                    <span class="notification-line">
                      <strong data-lang-pt="<?= h($displayNotePt['title']) ?>" data-lang-en="<?= h($displayNoteEn['title']) ?>"><?= h($displayNote['title']) ?></strong>
                      <span class="notification-type-pill" data-lang-pt="<?= h(notification_type_label((string)$note['tipo'], 'pt')) ?>" data-lang-en="<?= h(notification_type_label((string)$note['tipo'], 'en')) ?>"><?= h($context['label']) ?></span>
                    </span>
                    <span class="notification-message" data-lang-pt="<?= h($displayNotePt['message']) ?>" data-lang-en="<?= h($displayNoteEn['message']) ?>"><?= nl2br(h($displayNote['message'])) ?></span>
                    <small><?= h(date('d/m/Y H:i', strtotime($note['criado_em']))) ?></small>
                  </span>
                </a>
                <?php if ((int)$note['lida'] === 0): ?>
                  <form method="post">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="read">
                    <input type="hidden" name="notification_id" value="<?= (int)$note['idNotificacao'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" data-t="notifications_mark_read">Lida</button>
                  </form>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

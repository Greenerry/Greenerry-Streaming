<?php
require_once '../includes/config.php';
redirect_if_authenticated();

$token = (string)($_GET['token'] ?? '');
$result = verify_email_token($conn, $token);

if ($result === 'ok') {
    $message = tr('success.email_verified');
    $messageClass = 'alert-ok';
} elseif ($result === 'expired') {
    $message = tr('error.email_verify_expired');
    $messageClass = 'alert-err';
} else {
    $message = tr('error.email_verify_invalid');
    $messageClass = 'alert-err';
}

include '../includes/header.php';
?>

<section class="auth-shell auth-shell--narrow auth-shell--centered">
  <div class="auth-panel auth-panel--form auth-panel--form-only">
    <div class="auth-card auth-card--premium">
      <div class="auth-card-head">
        <span class="slabel">Greenerry</span>
        <h2><?= h(tr('email.verify_subject')) ?></h2>
      </div>

      <div class="alert <?= h($messageClass) ?>"><?= h($message) ?></div>

      <a href="login.php" class="btn btn-dark btn-full btn-lg" data-t="login_submit">Entrar</a>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

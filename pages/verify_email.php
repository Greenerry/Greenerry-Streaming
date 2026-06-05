<?php
require_once '../includes/config.php';
redirect_if_authenticated();

$token = (string)($_GET['token'] ?? '');
$emailValue = trim((string)($_POST['email'] ?? $_GET['email'] ?? ''));
$codeValue = greenerry_clean_code((string)($_POST['code'] ?? ''));
$err = '';
$ok = '';

if ($token !== '') {
    $result = verify_email_token($conn, $token);
    if ($result === 'ok') {
        $ok = tr('success.email_verified');
    } elseif ($result === 'expired') {
        $err = tr('error.email_verify_expired');
    } else {
        $err = tr('error.email_verify_invalid');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = verify_csrf_request() ?? validate_email($emailValue);
    if (!$err && !preg_match('/^\d{6}$/', $codeValue)) {
        $err = tr('error.email_verify_invalid');
    }

    if (!$err) {
        $result = verify_email_code($conn, $emailValue, $codeValue);
        if ($result === 'ok') {
            $ok = tr('success.email_verified');
        } elseif ($result === 'expired') {
            $err = tr('error.email_verify_expired');
        } else {
            $err = tr('error.email_verify_invalid');
        }
    }
}

$sent = isset($_GET['sent']) && $_GET['sent'] === '1';
$sentMessage = current_lang() === 'en'
    ? 'Code sent. Check your email and enter it below.'
    : 'Código enviado. Verifica o teu email e insere-o abaixo.';

include '../includes/header.php';
?>

<section class="auth-shell auth-shell--narrow auth-shell--centered">
  <div class="auth-panel auth-panel--form auth-panel--form-only">
    <div class="auth-card auth-card--premium">
      <div class="auth-card-head">
        <span class="slabel">Greenerry</span>
        <h2><?= h(tr('email.verify_subject')) ?></h2>
        <?php if (!$ok): ?>
          <p><?= h(current_lang() === 'en' ? 'Enter the code we sent to your email.' : 'Insere o código que enviamos para o teu email.') ?></p>
        <?php endif; ?>
      </div>

      <?php if ($sent && !$ok): ?>
        <div class="alert alert-ok"><?= h($sentMessage) ?></div>
      <?php endif; ?>

      <?php if ($err): ?>
        <div class="alert alert-err"><?= h($err) ?></div>
      <?php endif; ?>

      <?php if ($ok): ?>
        <div class="alert alert-ok"><?= h($ok) ?></div>
        <a href="login.php" class="btn btn-dark btn-full btn-lg" data-t="login_submit">Entrar</a>
      <?php elseif ($token === ''): ?>
        <form method="post" class="auth-form" novalidate>
          <?= csrf_input() ?>
          <div class="fg">
            <label class="flabel" for="email">Email</label>
            <input id="email" type="email" name="email" class="finput" required maxlength="150" autocomplete="email" value="<?= h($emailValue) ?>">
          </div>

          <div class="fg">
            <label class="flabel" for="code"><?= current_lang() === 'en' ? 'Code' : 'Código' ?></label>
            <input id="code" type="text" name="code" class="finput" required inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" value="<?= h($codeValue) ?>">
          </div>

          <button type="submit" class="btn btn-dark btn-full btn-lg"><?= h(current_lang() === 'en' ? 'Verify email' : 'Verificar email') ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

<?php
// Page purpose: Lets a user set a new password after verification.
// Keep this page simple: prepare data first, then render the view.
require_once '../includes/config.php';
redirect_if_authenticated();

$token = (string)($_GET['token'] ?? '');
$emailValue = trim((string)($_POST['email'] ?? $_GET['email'] ?? ''));
$codeValue = greenerry_clean_code((string)($_POST['code'] ?? ''));
$err = '';
$ok = '';
$tokenRow = null;

if ($token !== '') {
    $tokenRow = password_reset_user($conn, $token);
    if (!$tokenRow) {
        $err = tr('error.reset_invalid');
    } elseif (!empty($tokenRow['usado_em']) || (int)($tokenRow['expirado'] ?? 0) === 1) {
        $err = tr('error.reset_expired');
    } elseif ((string)$tokenRow['estado'] !== 'ativo') {
        $err = tr('error.account_inactive');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['senha'] ?? '');
    $confirmPassword = (string)($_POST['confirmar_senha'] ?? '');

    $err = verify_csrf_request() ?? validate_password($password);
    if (!$err && $password !== $confirmPassword) {
        $err = tr('error.password_mismatch');
    }

    if (!$err && $token === '') {
        $err = validate_email($emailValue);
        if (!$err && !preg_match('/^\d{6}$/', $codeValue)) {
            $err = tr('error.reset_invalid');
        }
    }

    if (!$err) {
        $result = $token !== ''
            ? complete_password_reset($conn, $token, $password)
            : complete_password_reset_code($conn, $emailValue, $codeValue, $password);

        if ($result === 'ok') {
            $ok = tr('success.password_reset');
        } elseif ($result === 'expired') {
            $err = tr('error.reset_expired');
        } elseif ($result === 'inactive') {
            $err = tr('error.account_inactive');
        } else {
            $err = tr('error.reset_invalid');
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
        <span class="slabel" data-t="forgot_label">Recuperacao</span>
        <h2 data-t="reset_title">Mudar palavra-passe</h2>
        <p data-t="reset_intro">Insere o código recebido e escolhe uma nova palavra-passe.</p>
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
      <?php else: ?>
        <form method="post" class="auth-form" novalidate>
          <?= csrf_input() ?>
          <?php if ($token !== ''): ?>
            <input type="hidden" name="token" value="<?= h($token) ?>">
          <?php else: ?>
            <div class="fg">
              <label class="flabel" for="email">Email</label>
              <input id="email" type="email" name="email" class="finput" required maxlength="150" autocomplete="email" value="<?= h($emailValue) ?>">
            </div>

            <div class="fg">
              <label class="flabel" for="code"><?= current_lang() === 'en' ? 'Code' : 'Código' ?></label>
              <input id="code" type="text" name="code" class="finput" required inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" value="<?= h($codeValue) ?>">
            </div>
          <?php endif; ?>

          <div class="fg">
            <label class="flabel" for="senha" data-t="reset_new_password">Nova palavra-passe</label>
            <input id="senha" type="password" name="senha" class="finput" required minlength="8" autocomplete="new-password">
          </div>

          <div class="fg">
            <label class="flabel" for="confirmar_senha" data-t="reset_confirm_password">Confirmar palavra-passe</label>
            <input id="confirmar_senha" type="password" name="confirmar_senha" class="finput" required minlength="8" autocomplete="new-password">
          </div>

          <button type="submit" class="btn btn-dark btn-full btn-lg" data-t="reset_submit">Guardar palavra-passe</button>
        </form>
      <?php endif; ?>

      <p class="auth-foot-note auth-foot-note--center">
        <a href="login.php" data-t="release_back">Voltar</a>
      </p>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

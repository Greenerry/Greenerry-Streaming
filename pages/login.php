<?php
// Page purpose: Handles user login and account access.
// Keep this page simple: prepare data first, then render the view.
require_once '../includes/config.php';
redirect_if_authenticated();

$err = '';
$emailValue = '';
$inactiveNotice = (int)($_GET['inactive'] ?? 0) === 1;
$next = (string)($_POST['next'] ?? $_GET['next'] ?? '');
if ($next !== '' && (str_contains($next, '://') || str_starts_with($next, '//') || str_contains($next, '..'))) {
    $next = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim($_POST['email'] ?? '');
    $password = $_POST['senha'] ?? '';

    $err = verify_csrf_request()
        ?? validate_email($emailValue)
        ?? ($password === '' ? tr('error.required_password') : null);

    if (!$err) {
        $user = db_one_prepared($conn, "SELECT * FROM cliente WHERE email = ? LIMIT 1", 's', [$emailValue]);

        if (!$user || !password_matches($password, $user['palavra_passe'])) {
            $err = tr('error.invalid_login');
        } elseif ($user['estado'] !== 'ativo') {
            $err = tr('error.account_inactive');
        } else {
            db_prepared($conn, "UPDATE cliente SET ultimo_login = NOW() WHERE idCliente = ?", 'i', [(int)$user['idCliente']]);
            login_user_session($user);
            header('Location: ' . ($next !== '' ? $next : 'index.php'));
            exit;
        }
    }
}

include '../includes/header.php';
?>

<section class="auth-shell auth-shell--narrow auth-shell--centered">
  <div class="auth-panel auth-panel--form auth-panel--form-only">
    <div class="auth-card auth-card--premium">
      <div class="auth-card-head">
        <h2 data-t="login_title">Aceder a conta</h2>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-err"><?= h($err) ?></div>
      <?php elseif ($inactiveNotice): ?>
        <div class="alert alert-err"><?= h(tr('error.account_inactive')) ?></div>
      <?php endif; ?>

      <form method="post" class="auth-form" novalidate>
        <?= csrf_input() ?>
        <?php if ($next !== ''): ?>
          <input type="hidden" name="next" value="<?= h($next) ?>">
        <?php endif; ?>

        <div class="fg">
          <label class="flabel" for="email">Email</label>
          <input id="email" type="email" name="email" class="finput" required maxlength="150" autocomplete="email" value="<?= h($emailValue) ?>">
        </div>

        <div class="fg">
          <label class="flabel" for="senha" data-t="login_password">Palavra-passe</label>
          <input id="senha" type="password" name="senha" class="finput" required minlength="8" autocomplete="current-password">
        </div>

        <div class="auth-actions">
          <a href="forgot_password.php" class="auth-link" data-t="login_forgot">Esqueceste-te da palavra-passe?</a>
        </div>

        <button type="submit" class="btn btn-dark btn-full btn-lg" data-t="login_submit">Entrar</button>
      </form>

      <p class="auth-foot-note auth-foot-note--center">
        <span data-t="login_no_account">Ainda não tens conta?</span>
        <a href="registar.php" data-t="login_create_account">Criar conta</a>
      </p>
      <p class="auth-foot-note auth-foot-note--center">
        <a href="verify_email.php"><?= h(current_lang() === 'en' ? 'Have a verification code?' : 'Tens um código de verificação?') ?></a>
      </p>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

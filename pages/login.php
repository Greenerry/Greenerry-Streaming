<?php
require_once '../includes/config.php';
redirect_if_authenticated();

$err = '';
$emailValue = '';
$loginTypeValue = $_POST['login_type'] ?? 'cliente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim($_POST['email'] ?? '');
    $password = $_POST['senha'] ?? '';
    $loginType = $loginTypeValue;

    $err = verify_csrf_request()
        ?? validate_email($emailValue)
        ?? ($password === '' ? tr('error.required_password') : null);

    if (!$err && !in_array($loginType, ['cliente', 'admin'], true)) {
        $err = tr('error.invalid_access_type');
    }

    if (!$err) {
        $emailSafe = db_escape($conn, $emailValue);
        $adminByEmail = db_one($conn, "SELECT * FROM admin WHERE email = '{$emailSafe}' AND ativo = 1 LIMIT 1");

        if ($loginType === 'admin') {
            if (!$adminByEmail || !password_matches($password, $adminByEmail['palavra_passe'])) {
                $err = tr('error.invalid_admin_credentials');
            } else {
                mysqli_query($conn, "UPDATE admin SET ultimo_login = NOW() WHERE idAdmin = " . (int)$adminByEmail['idAdmin']);
                login_admin_session($adminByEmail);
                header('Location: ../admin/dashboard.php');
                exit;
            }
        } else {
            $user = db_one($conn, "SELECT * FROM cliente WHERE email = '{$emailSafe}' LIMIT 1");

            if ($adminByEmail && password_matches($password, $adminByEmail['palavra_passe'])) {
                $err = tr('error.use_admin_login');
            }

            if (!$err && (!$user || !password_matches($password, $user['palavra_passe']))) {
                $err = tr('error.invalid_login');
            } elseif (!$err && $user['estado'] !== 'ativo') {
                $err = tr('error.account_inactive');
            } elseif (!$err) {
                mysqli_query($conn, "UPDATE cliente SET ultimo_login = NOW() WHERE idCliente = " . (int)$user['idCliente']);
                login_user_session($user);
                header('Location: index.php');
                exit;
            }
        }
    }
}

include '../includes/header.php';
?>

<section class="auth-shell auth-shell--narrow">
  <div class="auth-panel auth-panel--form auth-panel--form-only">
    <div class="auth-card auth-card--premium">
      <div class="auth-card-head">
        <span class="slabel" data-t="login_label">Acesso</span>
        <h2 data-t="login_title">Aceder a conta</h2>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-err"><?= h($err) ?></div>
      <?php endif; ?>

      <form method="post" class="auth-form" novalidate>
        <?= csrf_input() ?>
        <div class="segmented-field">
          <label class="segmented-option">
            <input type="radio" name="login_type" value="cliente" <?= $loginTypeValue === 'cliente' ? 'checked' : '' ?>>
            <span data-t="login_user_type">Utilizador / Artista</span>
          </label>
          <label class="segmented-option">
            <input type="radio" name="login_type" value="admin" <?= $loginTypeValue === 'admin' ? 'checked' : '' ?>>
            <span data-t="login_admin_type">Administracao</span>
          </label>
        </div>

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

      <p class="auth-foot-note">
        <span data-t="login_no_account">Ainda nao tens conta?</span>
        <a href="registar.php" data-t="login_create_account">Criar conta</a>
      </p>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

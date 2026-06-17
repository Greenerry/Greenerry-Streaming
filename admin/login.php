<?php
// Admin page purpose: Handles the private administrator login.
// Keep this admin file simple: check access, load data, then render the view.
require_once '../includes/config.php';

if (is_admin_logged_in()) {
    header('Location: ' . admin_default_page(current_admin($conn)));
    exit;
}

$err = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['senha'] ?? '');

    $err = verify_csrf_request()
        ?? validate_email($emailValue)
        ?? ($password === '' ? tr('error.required_password') : null);

    if (!$err) {
        $admin = db_one_prepared($conn, "SELECT * FROM admin WHERE email = ? AND ativo = 1 LIMIT 1", 's', [$emailValue]);

        if (!$admin || !password_matches($password, $admin['palavra_passe'])) {
            $err = tr('error.invalid_admin_credentials');
        } else {
            db_prepared($conn, "UPDATE admin SET ultimo_login = NOW() WHERE idAdmin = ?", 'i', [(int)$admin['idAdmin']]);
            login_admin_session($admin);
            header('Location: ' . admin_default_page($admin));
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
        <h2 data-t="login_admin_type">Administração</h2>
        <p><?= h(current_lang() === 'en' ? 'Sign in with an active Greenerry admin account.' : 'Inicia sessão com uma conta administrativa Greenerry ativa.') ?></p>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-err"><?= h($err) ?></div>
      <?php endif; ?>

      <form method="post" class="auth-form" novalidate>
        <?= csrf_input() ?>

        <div class="fg">
          <label class="flabel" for="email">Email</label>
          <input id="email" type="email" name="email" class="finput" required maxlength="150" autocomplete="email" value="<?= h($emailValue) ?>">
        </div>

        <div class="fg">
          <label class="flabel" for="senha" data-t="login_password">Palavra-passe</label>
          <input id="senha" type="password" name="senha" class="finput" required minlength="8" autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-dark btn-full btn-lg" data-t="login_submit">Entrar</button>
      </form>

      <p class="auth-foot-note auth-foot-note--center">
        <a href="../pages/login.php" data-t="login_user_type">Utilizador / Artista</a>
      </p>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

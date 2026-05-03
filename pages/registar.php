<?php
require_once '../includes/config.php';
redirect_if_authenticated();

$err = '';
$ok = '';
$nomeValue = trim($_POST['nome'] ?? '');
$emailValue = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['senha'] ?? '';
    $confirmPassword = $_POST['confirmar_senha'] ?? '';

    $err = verify_csrf_request()
        ?? validate_nome($nomeValue)
        ?? validate_email($emailValue)
        ?? validate_password($password);

    if (!$err && $password !== $confirmPassword) {
        $err = tr('error.password_mismatch');
    }

    if (!$err) {
        $emailSafe = db_escape($conn, $emailValue);
        $exists = db_one($conn, "SELECT idCliente FROM cliente WHERE email = '{$emailSafe}' LIMIT 1");
        if ($exists) {
            $err = tr('error.account_exists');
        } else {
            $nomeSafe = db_escape($conn, $nomeValue);
            $slug = ensure_unique_cliente_slug($conn, $nomeValue);
            $slugSafe = db_escape($conn, $slug);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $passwordSafe = db_escape($conn, $passwordHash);

            $sql = "INSERT INTO cliente (nome, email, palavra_passe, slug, estado)
                    VALUES ('{$nomeSafe}', '{$emailSafe}', '{$passwordSafe}', '{$slugSafe}', 'ativo')";

            if (mysqli_query($conn, $sql)) {
                $newUserId = (int)mysqli_insert_id($conn);
                $newUser = db_one($conn, "SELECT * FROM cliente WHERE idCliente = {$newUserId} LIMIT 1");

                if ($newUser) {
                    login_user_session($newUser);
                    header('Location: index.php');
                    exit;
                }

                $ok = tr('success.account_created');
            } else {
                $err = tr('error.create_account');
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
        <span class="slabel" data-t="register_label">Registo</span>
        <h2 data-t="register_title">Criar conta</h2>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-err"><?= h($err) ?></div>
      <?php endif; ?>

      <?php if ($ok): ?>
        <div class="alert alert-ok"><?= h($ok) ?></div>
      <?php endif; ?>

      <form method="post" class="auth-form" novalidate>
        <?= csrf_input() ?>
        <div class="fg">
          <label class="flabel" for="nome">Nome</label>
          <input id="nome" type="text" name="nome" class="finput" required maxlength="120" autocomplete="name" value="<?= h($nomeValue) ?>">
        </div>

        <div class="fg">
          <label class="flabel" for="email">Email</label>
          <input id="email" type="email" name="email" class="finput" required maxlength="150" autocomplete="email" value="<?= h($emailValue) ?>">
        </div>

        <div class="frow">
          <div class="fg">
            <label class="flabel" for="senha" data-t="register_password">Palavra-passe</label>
            <input id="senha" type="password" name="senha" class="finput" required minlength="8" autocomplete="new-password">
          </div>
          <div class="fg">
            <label class="flabel" for="confirmar_senha" data-t="register_confirm_password">Confirmar palavra-passe</label>
            <input id="confirmar_senha" type="password" name="confirmar_senha" class="finput" required minlength="8" autocomplete="new-password">
          </div>
        </div>

        <button type="submit" class="btn btn-dark btn-full btn-lg" data-t="register_submit">Criar conta</button>
      </form>

      <p class="auth-foot-note">
        <span data-t="register_have_account">Ja tens conta?</span>
        <a href="login.php" data-t="register_login">Entrar</a>
      </p>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

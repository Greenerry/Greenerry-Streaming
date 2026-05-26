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
        $exists = db_one_prepared($conn, "SELECT idCliente FROM cliente WHERE email = ? LIMIT 1", 's', [$emailValue]);
        if ($exists) {
            $err = tr('error.account_exists');
        } else {
            $slug = ensure_unique_cliente_slug($conn, $nomeValue);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if (db_prepared(
                $conn,
                "INSERT INTO cliente (nome, email, palavra_passe, slug, estado)
                 VALUES (?, ?, ?, ?, 'inativo')",
                'ssss',
                [$nomeValue, $emailValue, $passwordHash, $slug]
            )) {
                $newUserId = (int)mysqli_insert_id($conn);
                $newUser = db_one_prepared($conn, "SELECT * FROM cliente WHERE idCliente = ? LIMIT 1", 'i', [$newUserId]);

                if ($newUser) {
                    $token = create_email_verification($conn, $newUserId);
                    if ($token && send_email_verification($newUser, $token)) {
                        $ok = tr('success.account_created');
                        $nomeValue = '';
                        $emailValue = '';
                    } else {
                        db_prepared($conn, "DELETE FROM cliente WHERE idCliente = ?", 'i', [$newUserId]);
                        $err = tr('error.verification_email_send');
                    }
                } else {
                    $err = tr('error.create_account');
                }
            } else {
                $err = tr('error.create_account');
            }
        }
    }
}

include '../includes/header.php';
?>

<section class="auth-shell auth-shell--narrow auth-shell--centered">
  <div class="auth-panel auth-panel--form auth-panel--form-only">
    <div class="auth-card auth-card--premium">
      <div class="auth-card-head">
        <span class="slabel" data-t="register_label">Registo</span>
        <h2 data-t="register_title">Criar conta</h2>
        <p data-t="register_card_intro">Começa com uma conta de listener. Depois podes publicar música e merch pelo teu perfil.</p>
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
          <input id="nome" type="text" name="nome" class="finput" required maxlength="120" autocomplete="name" data-name-only value="<?= h($nomeValue) ?>">
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

      <p class="auth-foot-note auth-foot-note--center">
        <span data-t="register_have_account">Já tens conta?</span>
        <a href="login.php" data-t="register_login">Entrar</a>
      </p>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/config.php';
redirect_if_authenticated();

$err = '';
$ok = '';
$emailValue = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = verify_csrf_request() ?? validate_email($emailValue);

    if (!$err) {
        $user = db_one_prepared($conn, "SELECT idCliente, email, nome, estado FROM cliente WHERE email = ? LIMIT 1", 's', [$emailValue]);

        if (!$user) {
            $err = tr('error.account_not_found');
        } elseif ((string)($user['estado'] ?? '') !== 'ativo') {
            $err = tr('error.account_inactive');
        } else {
            $token = create_password_reset($conn, (int)$user['idCliente']);
            if (!$token) {
                $err = tr('error.reset_request_save');
            } else {
                $user['reset_token'] = $token;
                if (!send_reset_request_email($user)) {
                    $err = tr('error.reset_request_save');
                }
            }
        }

        if (!$err) {
            $ok = tr('success.reset_request');
            $emailValue = '';
        }
    }
}

include '../includes/header.php';
?>

<section class="auth-shell auth-shell--narrow auth-shell--centered">
  <div class="auth-panel auth-panel--form auth-panel--form-only">
    <div class="auth-card auth-card--premium">
      <div class="auth-card-head">
        <span class="slabel" data-t="forgot_label">Recuperacao</span>
        <h2 data-t="forgot_title">Recuperar acesso</h2>
        <p data-t="forgot_intro">Envia o teu email e recebe um link seguro para mudares a palavra-passe.</p>
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
          <label class="flabel" for="email">Email</label>
          <input id="email" type="email" name="email" class="finput" required maxlength="150" autocomplete="email" value="<?= h($emailValue) ?>">
        </div>

        <button type="submit" class="btn btn-dark btn-full btn-lg" data-t="forgot_submit">Enviar link</button>
      </form>

      <p class="auth-foot-note auth-foot-note--center">
        <a href="login.php" data-t="release_back">Voltar</a>
      </p>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

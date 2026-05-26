<?php
require_once '../includes/config.php';
redirect_if_authenticated();

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$err = '';
$ok = '';
$tokenRow = password_reset_user($conn, $token);

if (!$tokenRow) {
    $err = tr('error.reset_invalid');
} elseif (!empty($tokenRow['usado_em']) || (int)($tokenRow['expirado'] ?? 0) === 1) {
    $err = tr('error.reset_expired');
} elseif ((string)$tokenRow['estado'] !== 'ativo') {
    $err = tr('error.account_inactive');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$err) {
    $password = (string)($_POST['senha'] ?? '');
    $confirmPassword = (string)($_POST['confirmar_senha'] ?? '');

    $err = verify_csrf_request() ?? validate_password($password);
    if (!$err && $password !== $confirmPassword) {
        $err = tr('error.password_mismatch');
    }

    if (!$err) {
        $result = complete_password_reset($conn, $token, $password);
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

include '../includes/header.php';
?>

<section class="auth-shell auth-shell--narrow auth-shell--centered">
  <div class="auth-panel auth-panel--form auth-panel--form-only">
    <div class="auth-card auth-card--premium">
      <div class="auth-card-head">
        <span class="slabel" data-t="forgot_label">Recuperacao</span>
        <h2 data-t="reset_title">Mudar palavra-passe</h2>
        <p data-t="reset_intro">Escolhe uma nova palavra-passe para a tua conta.</p>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-err"><?= h($err) ?></div>
      <?php endif; ?>

      <?php if ($ok): ?>
        <div class="alert alert-ok"><?= h($ok) ?></div>
        <a href="login.php" class="btn btn-dark btn-full btn-lg" data-t="login_submit">Entrar</a>
      <?php elseif (!$err || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <form method="post" class="auth-form" novalidate>
          <?= csrf_input() ?>
          <input type="hidden" name="token" value="<?= h($token) ?>">

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

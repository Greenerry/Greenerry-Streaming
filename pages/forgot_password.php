<?php
require_once '../includes/config.php';
redirect_if_authenticated();

$err = '';
$ok = '';
$emailValue = trim($_POST['email'] ?? '');
$motivoValue = trim($_POST['motivo'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = verify_csrf_request() ?? validate_email($emailValue);

    if (!$err && mb_strlen($motivoValue) > 500) {
        $err = tr('error.reset_request_long');
    }

    if (!$err) {
        $emailSafe = db_escape($conn, $emailValue);
        $user = db_one($conn, "SELECT idCliente, email, nome FROM cliente WHERE email = '{$emailSafe}' LIMIT 1");

        if (!$user) {
            $err = tr('error.account_not_found');
        } else {
            $motivoSafe = db_escape($conn, $motivoValue);
            $sql = "INSERT INTO pedido_reset_password (idCliente, email, motivo, estado)
                    VALUES (" . (int)$user['idCliente'] . ", '{$emailSafe}', '{$motivoSafe}', 'pendente')";

            if (mysqli_query($conn, $sql)) {
                $ok = tr('success.reset_request');
                $emailValue = '';
                $motivoValue = '';
            } else {
                $err = tr('error.reset_request_save');
            }
        }
    }
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap-sm">
    <div class="support-hero support-hero--single">
      <div class="support-hero-copy">
        <span class="slabel">Recuperacao</span>
        <h2>Precisas de recuperar o acesso a tua conta?</h2>
        <p>Introduz o teu email e deixa uma mensagem curta. O pedido fica registado para acompanhamento manual do admin.</p>
      </div>
      <div class="support-hero-note">
        <span class="slabel">Seguranca</span>
        <p>Este fluxo mostra validacao de conta, registo do pedido e resolucao administrativa sem complicar a implementacao.</p>
      </div>
    </div>

    <?php if ($err): ?>
      <div class="alert alert-err"><?= h($err) ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="alert alert-ok"><?= h($ok) ?></div>
    <?php endif; ?>

    <div class="card surface-card surface-card--soft">
      <div class="card-body">
        <form method="post" class="stack-form" novalidate>
          <?= csrf_input() ?>
          <div class="fg">
            <label class="flabel" for="email">Email</label>
            <input id="email" type="email" name="email" class="finput" required maxlength="150" value="<?= h($emailValue) ?>">
          </div>

          <div class="fg">
            <label class="flabel" for="motivo">Mensagem para o admin</label>
            <textarea id="motivo" name="motivo" class="finput" maxlength="500" placeholder="Explica que precisas de ajuda para recuperar o acesso."><?= h($motivoValue) ?></textarea>
          </div>

          <button type="submit" class="btn btn-dark btn-full">Enviar pedido</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

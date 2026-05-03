<?php
require_once '../includes/config.php';
require_admin_login();

$feedback = '';
$error = '';
$settings = [
    'site_name' => site_setting('site_name', 'Greenerry'),
    'contact_email' => site_setting('contact_email', 'support@greenerry.test'),
    'contact_phone' => site_setting('contact_phone', '+351 900 000 000'),
    'instagram_url' => site_setting('instagram_url', '#'),
    'x_url' => site_setting('x_url', '#'),
    'footer_note' => site_setting('footer_note', '(c) 2026 Greenerry. Built for PAP presentation use.'),
    'support_hours' => site_setting('support_hours', 'Mon-Fri, 09:00-18:00'),
    'commission_percent' => site_setting('commission_percent', '5'),
    'shipping_note' => site_setting('shipping_note', 'Digital support and merch handled by Greenerry admin.'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    foreach ($settings as $key => $value) {
        $settings[$key] = trim((string)($_POST[$key] ?? ''));
    }

    if ($error === '' && !filter_var($settings['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $error = tr('error.invalid_email');
    }

    if ($error === '') {
        mysqli_begin_transaction($conn);
        $ok = true;
        foreach ($settings as $key => $value) {
            $keySafe = db_escape($conn, $key);
            $valueSafe = db_escape($conn, $value);
            $ok = $ok && mysqli_query(
                $conn,
                "INSERT INTO site_config (setting_key, setting_value)
                 VALUES ('{$keySafe}', '{$valueSafe}')
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );
        }

        if ($ok) {
            mysqli_commit($conn);
            $feedback = tr('success.settings_updated');
        } else {
            mysqli_rollback($conn);
            $error = tr('error.settings_update');
        }
    }
}

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <h2 data-admin-t="settings_title">Definicoes</h2>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-err"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" class="settings-grid">
  <?= csrf_input() ?>

  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="settings_public_contact">Contacto publico</h4>
    </div>
    <div class="stack-form">
      <div class="fg">
        <label class="flabel" for="site-name" data-admin-t="settings_site_name">Nome do site</label>
        <input id="site-name" name="site_name" class="finput" value="<?= h($settings['site_name']) ?>" maxlength="120">
      </div>
      <div class="fg">
        <label class="flabel" for="contact-email" data-admin-t="settings_email">Email do site</label>
        <input id="contact-email" type="email" name="contact_email" class="finput" value="<?= h($settings['contact_email']) ?>" required maxlength="150">
      </div>
      <div class="fg">
        <label class="flabel" for="contact-phone" data-admin-t="settings_phone">Telefone do site</label>
        <input id="contact-phone" name="contact_phone" class="finput" value="<?= h($settings['contact_phone']) ?>" maxlength="40">
      </div>
      <div class="fg">
        <label class="flabel" for="support-hours" data-admin-t="settings_hours">Horario de suporte</label>
        <input id="support-hours" name="support_hours" class="finput" value="<?= h($settings['support_hours']) ?>" maxlength="120">
      </div>
    </div>
  </section>

  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="settings_brand">Marca e links</h4>
    </div>
    <div class="stack-form">
      <div class="fg">
        <label class="flabel" for="instagram-url">Instagram</label>
        <input id="instagram-url" name="instagram_url" class="finput" value="<?= h($settings['instagram_url']) ?>" maxlength="255">
      </div>
      <div class="fg">
        <label class="flabel" for="x-url">X</label>
        <input id="x-url" name="x_url" class="finput" value="<?= h($settings['x_url']) ?>" maxlength="255">
      </div>
      <div class="fg">
        <label class="flabel" for="footer-note" data-admin-t="settings_footer_note">Texto do footer</label>
        <textarea id="footer-note" name="footer_note" class="finput" rows="3"><?= h($settings['footer_note']) ?></textarea>
      </div>
    </div>
  </section>

  <section class="acard-box">
    <div class="acard-box-head">
      <h4 data-admin-t="settings_business">Loja</h4>
    </div>
    <div class="stack-form">
      <div class="fg">
        <label class="flabel" for="commission-percent" data-admin-t="settings_commission">Comissao padrao (%)</label>
        <input id="commission-percent" type="number" step="0.01" min="0" max="100" name="commission_percent" class="finput" value="<?= h($settings['commission_percent']) ?>">
      </div>
      <div class="fg">
        <label class="flabel" for="shipping-note" data-admin-t="settings_shipping_note">Nota da loja</label>
        <textarea id="shipping-note" name="shipping_note" class="finput" rows="4"><?= h($settings['shipping_note']) ?></textarea>
      </div>
    </div>
  </section>

  <section class="acard-box settings-save-card">
    <div>
      <span class="admin-kicker" data-admin-t="settings_live_kicker">User side</span>
      <h4 data-admin-t="settings_live_title">As alteracoes aparecem no footer publico.</h4>
    </div>
    <button type="submit" class="btn btn-dark" data-admin-t="settings_save">Guardar definicoes</button>
  </section>
</form>

<?php include 'admin_footer.php'; ?>

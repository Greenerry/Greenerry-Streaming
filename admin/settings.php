<?php
require_once '../includes/config.php';
require_admin_permission('settings');

$feedback = '';
$error = '';
$settings = [
    'site_name' => site_setting('site_name', 'Greenerry'),
    'contact_email' => site_setting('contact_email', 'support@greenerry.test'),
    'contact_phone' => site_setting('contact_phone', '+351 900 000 000'),
    'instagram_url' => site_setting('instagram_url', '#'),
    'x_url' => site_setting('x_url', '#'),
    'footer_note' => site_setting('footer_note', ''),
    'commission_percent' => site_setting('commission_percent', '5'),
    'shipping_note' => site_setting('shipping_note', 'Digital support and merch handled by Greenerry admin.'),
    'email_enabled' => site_setting('email_enabled', '0'),
    'smtp_host' => site_setting('smtp_host', ''),
    'smtp_port' => site_setting('smtp_port', '587'),
    'smtp_username' => site_setting('smtp_username', ''),
    'smtp_password' => site_setting('smtp_password', ''),
    'smtp_secure' => site_setting('smtp_secure', 'tls'),
];
$legacyFooterMarker = 'Built for ' . 'PAP';
$legacyFooterMarkerAlt = 'PAP' . ' presentation';
if (stripos($settings['footer_note'], $legacyFooterMarker) !== false || stripos($settings['footer_note'], $legacyFooterMarkerAlt) !== false) {
    $settings['footer_note'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    foreach ($settings as $key => $value) {
        if ($key === 'email_enabled') {
            $settings[$key] = isset($_POST[$key]) ? '1' : '0';
        } else {
            $settings[$key] = trim((string)($_POST[$key] ?? ''));
        }
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
                "INSERT INTO configuracao_site (chave_configuracao, valor_configuracao)
                 VALUES ('{$keySafe}', '{$valueSafe}')
                 ON DUPLICATE KEY UPDATE valor_configuracao = VALUES(valor_configuracao)"
            );
        }

        if ($ok) {
            mysqli_commit($conn);
            reload_site_settings();
            $feedback = tr('success.settings_updated');
            if (isset($_POST['send_test_email'])) {
                $feedback = send_test_email($settings['contact_email'])
                    ? tr('success.test_email_sent')
                    : tr('error.test_email_send');
            }
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
      <span class="admin-page-kicker" data-admin-t="settings_kicker">Site controls</span>
      <h2 data-admin-t="settings_title">Definicoes</h2>
      <p data-admin-t="settings_intro">Edita contacto publico, marca, email e regras comerciais do site.</p>
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

  <details class="acard-box settings-panel" open>
    <summary class="settings-panel-summary">
      <h4 data-admin-t="settings_public_contact">Contacto publico</h4>
      <span data-admin-t="settings_panel_email_note">Email, SMTP e contacto</span>
    </summary>
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
      <label class="admin-check-row">
        <input type="checkbox" name="email_enabled" value="1" <?= $settings['email_enabled'] === '1' ? 'checked' : '' ?>>
        <span data-admin-t="settings_email_enabled">Enviar emails automaticos</span>
      </label>
      <p class="admin-card-note" data-admin-t="settings_email_note">Usa o email do site como remetente. No XAMPP pode precisar de configuracao de mail do servidor.</p>
      <div class="frow">
        <div class="fg">
          <label class="flabel" for="smtp-host" data-admin-t="settings_smtp_host">Servidor SMTP</label>
          <input id="smtp-host" name="smtp_host" class="finput" value="<?= h($settings['smtp_host']) ?>" maxlength="150" placeholder="smtp.gmail.com">
        </div>
        <div class="fg">
          <label class="flabel" for="smtp-port" data-admin-t="settings_smtp_port">Porta SMTP</label>
          <input id="smtp-port" name="smtp_port" class="finput" value="<?= h($settings['smtp_port']) ?>" maxlength="6" placeholder="587">
        </div>
      </div>
      <div class="fg">
        <label class="flabel" for="smtp-username" data-admin-t="settings_smtp_username">Utilizador SMTP</label>
        <input id="smtp-username" name="smtp_username" class="finput" value="<?= h($settings['smtp_username']) ?>" maxlength="150" placeholder="email@gmail.com">
      </div>
      <div class="fg">
        <label class="flabel" for="smtp-password" data-admin-t="settings_smtp_password">Password SMTP</label>
        <input id="smtp-password" type="password" name="smtp_password" class="finput" value="<?= h($settings['smtp_password']) ?>" maxlength="255">
      </div>
      <div class="fg">
        <label class="flabel" for="smtp-secure" data-admin-t="settings_smtp_secure">Seguranca SMTP</label>
        <select id="smtp-secure" name="smtp_secure" class="finput">
          <option value="tls" <?= $settings['smtp_secure'] === 'tls' ? 'selected' : '' ?>>TLS</option>
          <option value="ssl" <?= $settings['smtp_secure'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
          <option value="" <?= $settings['smtp_secure'] === '' ? 'selected' : '' ?> data-admin-t="settings_smtp_none">Nenhuma</option>
        </select>
      </div>
      <button type="submit" name="send_test_email" value="1" class="btn btn-ghost btn-sm" data-admin-t="settings_test_email">Enviar email de teste</button>
    </div>
  </details>

  <details class="acard-box settings-panel">
    <summary class="settings-panel-summary">
      <h4 data-admin-t="settings_brand">Marca e links</h4>
      <span data-admin-t="settings_panel_brand_note">Redes sociais e footer</span>
    </summary>
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
  </details>

  <details class="acard-box settings-panel">
    <summary class="settings-panel-summary">
      <h4 data-admin-t="settings_business">Loja</h4>
      <span data-admin-t="settings_panel_shop_note">Comissão e notas da loja</span>
    </summary>
    <div class="stack-form">
      <div class="fg">
        <label class="flabel" for="commission-percent" data-admin-t="settings_commission">Comissão padrão (%)</label>
        <input id="commission-percent" type="number" step="0.01" min="0" max="100" name="commission_percent" class="finput" value="<?= h($settings['commission_percent']) ?>">
      </div>
      <div class="fg">
        <label class="flabel" for="shipping-note" data-admin-t="settings_shipping_note">Nota da loja</label>
        <textarea id="shipping-note" name="shipping_note" class="finput" rows="4"><?= h($settings['shipping_note']) ?></textarea>
      </div>
    </div>
  </details>

  <section class="acard-box settings-save-card">
    <div>
      <span class="admin-kicker" data-admin-t="settings_live_kicker">User side</span>
      <h4 data-admin-t="settings_live_title">As alteracoes aparecem no footer publico.</h4>
    </div>
    <button type="submit" class="btn btn-dark" data-admin-t="settings_save">Guardar definicoes</button>
  </section>
</form>

<?php include 'admin_footer.php'; ?>

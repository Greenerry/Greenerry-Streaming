<?php
require_once '../includes/config.php';
require_admin_permission('maintenance');

$feedback = '';
$error = '';
$maintenancePageOptions = [
    'index.php' => ['label' => 'Home', 'key' => 'settings_page_home'],
    'music.php' => ['label' => 'Music', 'key' => 'settings_page_music'],
    'artists.php' => ['label' => 'Artists', 'key' => 'settings_page_artists'],
    'artist.php' => ['label' => 'Artist profile', 'key' => 'settings_page_artist'],
    'release.php' => ['label' => 'Release detail', 'key' => 'settings_page_release'],
    'shop.php' => ['label' => 'Store', 'key' => 'settings_page_store'],
    'produto.php' => ['label' => 'Product detail', 'key' => 'settings_page_product'],
    'cart.php' => ['label' => 'Cart', 'key' => 'settings_page_cart'],
    'checkout.php' => ['label' => 'Checkout', 'key' => 'settings_page_checkout'],
    'favourites.php' => ['label' => 'Favourites', 'key' => 'settings_page_favourites'],
    'profile.php' => ['label' => 'Profile', 'key' => 'settings_page_profile'],
    'orders.php' => ['label' => 'Orders', 'key' => 'settings_page_orders'],
    'revenue.php' => ['label' => 'Revenue', 'key' => 'settings_page_revenue'],
    'upload_music.php' => ['label' => 'Upload music', 'key' => 'settings_page_upload_music'],
    'upload_merch.php' => ['label' => 'Upload merch', 'key' => 'settings_page_upload_merch'],
    'contact_admin.php' => ['label' => 'Contact admin', 'key' => 'settings_page_contact_admin'],
];
$selectedPages = array_filter(array_map('trim', explode(',', site_setting('maintenance_pages', ''))));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = verify_csrf_request() ?? '';
    $submittedPages = array_intersect((array)($_POST['maintenance_pages'] ?? []), array_keys($maintenancePageOptions));
    $selectedPages = $submittedPages;

    if ($error === '') {
        $valueSafe = db_escape($conn, implode(',', $submittedPages));
        if (mysqli_query(
            $conn,
            "INSERT INTO configuracao_site (chave_configuracao, valor_configuracao)
             VALUES ('maintenance_pages', '{$valueSafe}')
             ON DUPLICATE KEY UPDATE valor_configuracao = VALUES(valor_configuracao)"
        )) {
            reload_site_settings();
            $feedback = tr('success.settings_updated');
        } else {
            $error = tr('error.settings_update');
        }
    }
}

$selectedPageCount = count($selectedPages);
$livePageCount = count($maintenancePageOptions) - $selectedPageCount;

include 'admin_header.php';
?>

<div class="admin-top">
  <div>
    <span class="admin-page-kicker" data-admin-t="maintenance_kicker">Website control</span>
    <h2 data-admin-t="settings_maintenance">Manutencao de paginas</h2>
    <p data-admin-t="settings_maintenance_note">Escolhe as paginas do cliente que devem ficar temporariamente indisponíveis.</p>
  </div>
  <div class="stats-grid admin-top-stats admin-top-stats--three">
    <button type="button" class="stat maintenance-stat" data-maintenance-filter="paused"><div class="stat-val" data-maintenance-paused><?= $selectedPageCount ?></div><div class="stat-lbl" data-admin-t="maintenance_paused">Pausadas</div></button>
    <button type="button" class="stat maintenance-stat" data-maintenance-filter="live"><div class="stat-val" data-maintenance-live><?= $livePageCount ?></div><div class="stat-lbl" data-admin-t="maintenance_live">Disponiveis</div></button>
    <button type="button" class="stat maintenance-stat is-active" data-maintenance-filter="all"><div class="stat-val" data-maintenance-total><?= count($maintenancePageOptions) ?></div><div class="stat-lbl" data-admin-t="maintenance_total">Total</div></button>
  </div>
</div>

<?php if ($feedback): ?>
  <div class="alert alert-ok"><?= h($feedback) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-err"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" class="maintenance-admin-form">
  <?= csrf_input() ?>

  <section class="acard-box settings-panel maintenance-admin-panel">
    <div class="settings-panel-summary settings-panel-summary--static">
      <div>
        <h4 data-admin-t="settings_panel_maintenance_note">Ativar ou pausar áreas</h4>
        <span data-admin-t="maintenance_note">Admins continuam a conseguir ver as paginas para testar.</span>
      </div>
      <div class="maintenance-panel-actions">
        <span class="badge badge-light" data-maintenance-badge><?= $selectedPageCount ?>/<?= count($maintenancePageOptions) ?></span>
        <button type="submit" class="btn btn-dark btn-sm" data-admin-t="settings_save">Guardar definicoes</button>
      </div>
    </div>
    <div class="admin-check-grid admin-check-grid--pages">
      <?php foreach ($maintenancePageOptions as $pageFile => $pageInfo): ?>
        <label class="admin-check-row maintenance-page-card">
          <input type="checkbox" name="maintenance_pages[]" value="<?= h($pageFile) ?>" <?= in_array($pageFile, $selectedPages, true) ? 'checked' : '' ?>>
          <span>
            <strong data-admin-t="<?= h($pageInfo['key']) ?>"><?= h($pageInfo['label']) ?></strong>
            <small><?= h($pageFile) ?></small>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

</form>

<?php include 'admin_footer.php'; ?>

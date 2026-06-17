<?php
// Shared public layout: Builds the public sidebar, top navigation, and player shell.
// Keep this shared code small, reusable, and safe for every page.
if (!isset($conn)) {
    require_once __DIR__ . '/config.php';
}

$page = basename($_SERVER['PHP_SELF']);
$displayName = '';
$displayAvatar = '';
$pendingArtistOrders = 0;
$pendingArtistMessages = 0;
$unreadNotifications = 0;
$recentNotifications = [];
if (is_user_logged_in() && !active_user_session($conn)) {
    end_user_session_only();
}

if (is_user_logged_in()) {
    $displayName = $currentUser['nome'] ?? ($_SESSION['user_name'] ?? '');
    $displayAvatar = asset_url('img', $currentUser['foto'] ?? '');
    $unreadNotifications = (int)(db_one(
        $conn,
        "SELECT COUNT(*) AS total FROM notificacao WHERE idCliente = " . (int)current_user_id() . " AND lida = 0"
    )['total'] ?? 0);
    $recentNotifications = db_all(
        $conn,
        "SELECT idNotificacao, titulo, mensagem, tipo, lida, criado_em
         FROM notificacao
         WHERE idCliente = " . (int)current_user_id() . "
         ORDER BY criado_em DESC
         LIMIT 5"
    );
    $pendingArtistOrders = (int)(db_one(
        $conn,
        "SELECT COUNT(DISTINCT ei.idEncomenda) AS total
         FROM encomenda_item ei
         JOIN produto p ON p.idProduto = ei.idProduto
         WHERE ei.idArtista = " . (int)current_user_id() . "
           AND ei.estado_item = 'pendente'
           AND p.idCliente = " . (int)current_user_id()
    )['total'] ?? 0);
    $pendingArtistMessages = (int)(db_one(
        $conn,
        "SELECT COUNT(DISTINCT CONCAT(idEncomenda, '-', idProduto, '-', idComprador)) AS total
         FROM encomenda_mensagem
         WHERE idArtista = " . (int)current_user_id() . "
           AND remetente = 'comprador'
           AND lida = 0"
    )['total'] ?? 0);
} elseif (is_admin_logged_in()) {
    $displayName = $currentAdmin['nome'] ?? ($_SESSION['admin_name'] ?? '');
}

$artistPages = ['artist_dashboard.php', 'artist_analytics.php', 'artist_releases.php', 'artist_products.php', 'artist_messages.php', 'artist_customers.php', 'upload_music.php', 'upload_merch.php', 'orders.php', 'revenue.php'];
$isArtistAreaPage = in_array($page, $artistPages, true);

$showMaintenanceContent = false;
$clientPagesDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
if (str_ends_with($clientPagesDir, '/pages') && page_under_maintenance($page) && !is_admin_logged_in()) {
    $showMaintenanceContent = true;
}

?>
<!DOCTYPE html>
<html lang="<?= h(current_lang()) ?>" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Greenerry</title>
  <script>
    document.documentElement.dataset.theme = localStorage.getItem('g_theme') || 'light';
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $_base ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body data-user-id="<?= (int)$jsUserId ?>" class="<?= $isArtistAreaPage ? 'artist-sidebar-mode' : '' ?>">
<script>
window.SITE_BASE='<?= $_base ?>';
window.CSRF_TOKEN='<?= h(csrf_token()) ?>';
</script>
<div class="theme-wipe" id="theme-wipe" aria-hidden="true"></div>

<div class="sl-overlay" id="sl-overlay"></div>

<div class="shell">
  <aside class="sl" id="sl">
    <a href="<?= $_base ?>/pages/index.php" class="sl-brand greenerry-brand" aria-label="Greenerry">
      <span class="greenerry-brand-mark" aria-hidden="true">
        <img class="greenerry-logo greenerry-logo--dark" src="<?= $_base ?>/assets/img/brand/greenerry-mark-dark.png" alt="">
        <img class="greenerry-logo greenerry-logo--light" src="<?= $_base ?>/assets/img/brand/greenerry-mark-light.png" alt="">
      </span>
      <span class="greenerry-brand-wordmark" aria-hidden="true">
        <img class="greenerry-logo greenerry-logo--dark" src="<?= $_base ?>/assets/img/brand/greenerry-wordmark-dark.png" alt="">
        <img class="greenerry-logo greenerry-logo--light" src="<?= $_base ?>/assets/img/brand/greenerry-wordmark-light.png" alt="">
      </span>
      <span class="greenerry-brand-text">Greenerry</span>
    </a>
    <nav class="sl-nav">
      <div class="sl-sec sl-main-nav">
        <span class="sl-lbl" data-t="nav_discover">Descobrir</span>
        <a href="<?= $_base ?>/pages/index.php" class="sl-link <?= $page === 'index.php' ? 'on' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span data-t="nav_home">Inicio</span>
        </a>
        <a href="<?= $_base ?>/pages/music.php" class="sl-link <?= $page === 'music.php' ? 'on' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
          <span data-t="nav_music">Musica</span>
        </a>
        <a href="<?= $_base ?>/pages/artists.php" class="sl-link <?= $page === 'artists.php' ? 'on' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
          <span data-t="nav_artists">Artistas</span>
        </a>
        <a href="<?= $_base ?>/pages/shop.php" class="sl-link <?= $page === 'shop.php' ? 'on' : '' ?>">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span data-t="nav_shop">Loja</span>
        </a>
      </div>

      <?php if (is_user_logged_in()): ?>
        <div class="sl-sec sl-main-nav">
          <span class="sl-lbl" data-t="nav_account">Conta</span>
          <a href="<?= $_base ?>/pages/profile.php" class="sl-link <?= $page === 'profile.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span data-t="nav_profile">Perfil</span>
          </a>
          <a href="<?= $_base ?>/pages/cart.php" class="sl-link <?= $page === 'cart.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span data-t="nav_cart">Carrinho</span><span class="cart-badge">0</span>
          </a>
          <a href="<?= $_base ?>/pages/favourites.php" class="sl-link <?= $page === 'favourites.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            <span data-t="nav_library">Biblioteca</span>
          </a>
          <a href="<?= $_base ?>/pages/my_orders.php" class="sl-link <?= $page === 'my_orders.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M8 10h6"/><path d="M8 14h6"/><path d="M8 18h6"/></svg>
            <span data-t="nav_my_orders">Compras</span>
          </a>
          <a href="<?= $_base ?>/pages/contact_admin.php" class="sl-link sl-link--contact-admin <?= $page === 'contact_admin.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 9h8"/><path d="M8 13h5"/></svg>
            <span data-t="nav_contact_admin">Suporte</span>
          </a>
        </div>

        <div class="sl-sec sl-artist-nav">
          <span class="sl-lbl" data-t="nav_artist_area">Área de artista</span>
          <a href="<?= $_base ?>/pages/artist_dashboard.php" class="sl-link <?= $page === 'artist_dashboard.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M8 19V9"/><path d="M12 19V3"/><path d="M16 19v-7"/><path d="M20 19V8"/></svg>
            <span data-t="nav_artist_overview">Overview</span>
          </a>
          <a href="<?= $_base ?>/pages/artist_messages.php" class="sl-link <?= $page === 'artist_messages.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
            <span data-t="nav_artist_messages">Messages</span><span class="orders-badge" style="<?= $pendingArtistMessages > 0 ? 'display:inline-block' : '' ?>"><?= (int)$pendingArtistMessages ?></span>
          </a>
          <a href="<?= $_base ?>/pages/artist_analytics.php" class="sl-link <?= $page === 'artist_analytics.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 16V9"/><path d="M12 16V5"/><path d="M17 16v-3"/></svg>
            <span data-t="nav_artist_analytics">Analytics</span>
          </a>
          <a href="<?= $_base ?>/pages/artist_releases.php" class="sl-link <?= $page === 'artist_releases.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            <span data-t="nav_artist_releases">Releases</span>
          </a>
          <a href="<?= $_base ?>/pages/artist_products.php" class="sl-link <?= $page === 'artist_products.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/></svg>
            <span data-t="nav_artist_products">Products</span>
          </a>
          <a href="<?= $_base ?>/pages/artist_customers.php" class="sl-link <?= $page === 'artist_customers.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span data-t="nav_artist_customers">Customers</span>
          </a>
        </div>

        <div class="sl-sec sl-artist-nav">
          <span class="sl-lbl" data-t="nav_artist_create">Create</span>
          <a href="<?= $_base ?>/pages/upload_music.php" class="sl-link <?= $page === 'upload_music.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            <span data-t="nav_upload_music">Upload music</span>
          </a>
          <a href="<?= $_base ?>/pages/upload_merch.php" class="sl-link <?= $page === 'upload_merch.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/></svg>
            <span data-t="nav_upload_merch">Upload product</span>
          </a>
        </div>

        <div class="sl-sec sl-artist-nav">
          <span class="sl-lbl" data-t="nav_artist_commerce">Commerce</span>
          <a href="<?= $_base ?>/pages/orders.php" class="sl-link <?= $page === 'orders.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="8" height="4" x="8" y="2" rx="1"/><path d="M8 12h8M8 16h8"/></svg>
            <span data-t="nav_orders">Orders</span><span class="orders-badge" style="<?= $pendingArtistOrders > 0 ? 'display:inline-block' : '' ?>"><?= (int)$pendingArtistOrders ?></span>
          </a>
          <a href="<?= $_base ?>/pages/revenue.php" class="sl-link <?= $page === 'revenue.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-7"/></svg>
            <span data-t="nav_revenue">Revenue</span>
          </a>
        </div>
      <?php elseif (is_admin_logged_in()): ?>
        <div class="sl-sec">
          <span class="sl-lbl" data-t="nav_admin">Administração</span>
          <a href="<?= $_base ?>/admin/dashboard.php" class="sl-link">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
            <span data-t="nav_go_admin">Ir para admin</span>
          </a>
        </div>
      <?php endif; ?>
    </nav>

    <div class="sl-foot">
      <div class="sl-mobile-preferences">
        <div class="lang">
          <button type="button" data-l="pt">PT</button>
          <button type="button" data-l="en">EN</button>
        </div>
        <button type="button" class="theme-toggle" data-theme-toggle aria-label="Theme" title="Theme">
          <svg class="theme-toggle-sun" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
          <svg class="theme-toggle-moon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.6 6.6 0 0 0 9.8 9.8z"/></svg>
        </button>
      </div>
      <?php if (is_user_logged_in() || is_admin_logged_in()): ?>
        <?php if (is_user_logged_in()): ?>
          <button type="button" class="artist-mode-switch" data-artist-mode-toggle aria-pressed="<?= $isArtistAreaPage ? 'true' : 'false' ?>">
            <span class="artist-mode-icon">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3v18"/><path d="M17 8H9.5a3.5 3.5 0 0 0 0 7H15a3 3 0 0 1 0 6H7"/><path d="M6 3h12"/></svg>
            </span>
            <span data-t="nav_artist_side">Artista</span>
            <span class="artist-mode-cue">→</span>
          </button>
        <?php endif; ?>
        <a href="<?= $_base ?>/pages/logout.php" class="sl-link sl-link--muted">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          <span data-t="nav_logout">Sair</span>
        </a>
      <?php endif; ?>
    </div>
    <button type="button" class="sl-peek" id="sl-peek" aria-label="Expand sidebar" aria-expanded="false">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
    </button>
  </aside>

  <div class="main footer-wrap">
    <nav class="nav" id="main-nav">
      <div class="nav-inner">
        <div class="nav-center"></div>
        <div class="nav-right">
          <button class="sr-open-btn" id="sr-open-btn">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            <span data-t="player_now">A tocar</span>
          </button>
          <div class="lang" id="nav-lang">
            <button type="button" data-l="pt" onclick="window.GreenerrySetLang && window.GreenerrySetLang('pt')">PT</button>
            <button type="button" data-l="en" onclick="window.GreenerrySetLang && window.GreenerrySetLang('en')">EN</button>
          </div>
          <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Theme" title="Theme">
            <svg class="theme-toggle-sun" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            <svg class="theme-toggle-moon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.6 6.6 0 0 0 9.8 9.8z"/></svg>
          </button>
          <?php if (is_user_logged_in()): ?>
            <div class="nav-notifications" data-notifications-menu>
              <button type="button" class="nav-icon-btn nav-notification-btn" aria-label="<?= h(current_lang() === 'en' ? 'Notifications' : 'Notificações') ?>" aria-expanded="false" data-notification-toggle>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <?php if ($unreadNotifications > 0): ?><span><?= (int)$unreadNotifications ?></span><?php endif; ?>
              </button>
              <div class="notification-popover" hidden>
                <div class="notification-popover-head">
                  <strong data-t="notifications_title">Notificações</strong>
                  <a href="<?= $_base ?>/pages/notifications.php" data-t="notifications_view_all">Ver tudo</a>
                </div>
                <?php if (!$recentNotifications): ?>
                  <p class="notification-empty" data-t="notifications_empty">Ainda não tens notificações.</p>
                <?php else: ?>
                  <div class="notification-mini-list">
                    <?php foreach ($recentNotifications as $note): ?>
                      <?php $noteContext = notification_context($conn, $note, (int)current_user_id()); ?>
                      <?php $displayNote = notification_display_text($note); ?>
                      <?php $displayNotePt = notification_display_text($note, 'pt'); ?>
                      <?php $displayNoteEn = notification_display_text($note, 'en'); ?>
                      <a href="<?= $_base ?>/pages/notifications.php?go=<?= (int)$note['idNotificacao'] ?>" class="notification-mini-item <?= (int)$note['lida'] === 0 ? 'is-unread' : '' ?>">
                        <span class="notification-media notification-media--mini">
                          <?php if ($noteContext['image'] !== ''): ?>
                            <img src="<?= h($noteContext['image']) ?>" alt="">
                          <?php else: ?>
                            <?= notification_icon_svg((string)$note['tipo']) ?>
                          <?php endif; ?>
                        </span>
                        <span class="notification-copy">
                          <span class="notification-line">
                            <strong data-lang-pt="<?= h($displayNotePt['title']) ?>" data-lang-en="<?= h($displayNoteEn['title']) ?>"><?= h($displayNote['title']) ?></strong>
                            <span class="notification-type-pill" data-lang-pt="<?= h(notification_type_label((string)$note['tipo'], 'pt')) ?>" data-lang-en="<?= h(notification_type_label((string)$note['tipo'], 'en')) ?>"><?= h($noteContext['label']) ?></span>
                          </span>
                        </span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if ($displayName): ?>
            <?php if (is_user_logged_in()): ?>
              <div class="nav-account-menu" data-account-menu>
                <button type="button" class="nav-account-toggle" data-account-toggle aria-expanded="false">
                  <span class="nav-avatar">
                    <?php if ($displayAvatar !== ''): ?>
                      <img src="<?= h($displayAvatar) ?>" alt="">
                    <?php else: ?>
                      <?= h(mb_strtoupper(mb_substr($displayName, 0, 1))) ?>
                    <?php endif; ?>
                  </span>
                  <span class="nav-user-name"><?= h($displayName) ?></span>
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="account-popover" hidden>
                  <a href="<?= $_base ?>/pages/profile.php" data-t="nav_profile">Profile</a>
                  <a href="<?= $_base ?>/pages/favourites.php" data-t="nav_library">Library</a>
                  <a href="<?= $_base ?>/pages/my_orders.php" data-t="nav_my_orders">My orders</a>
                  <a href="<?= $_base ?>/pages/logout.php" data-t="nav_logout">Sign out</a>
                </div>
              </div>
            <?php else: ?>
              <span class="nav-user-name"><?= h($displayName) ?></span>
            <?php endif; ?>
          <?php elseif (!is_admin_logged_in()): ?>
            <a href="<?= $_base ?>/pages/login.php" class="btn btn-outline btn-sm nav-auth-btn" data-t="nav_login">Entrar</a>
            <a href="<?= $_base ?>/pages/registar.php" class="btn btn-dark btn-sm nav-auth-btn" data-t="nav_register">Registar</a>
          <?php endif; ?>
          <button class="hamburger" id="ham" aria-label="Menu"><span></span><span></span><span></span></button>
        </div>
      </div>
    </nav>
    <div class="page-body pad-top">
      <?php if ($showMaintenanceContent) { ob_start(); } ?>

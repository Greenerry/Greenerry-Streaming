<?php
if (!isset($conn)) {
    require_once __DIR__ . '/config.php';
}

$page = basename($_SERVER['PHP_SELF']);
$displayName = '';
if (is_user_logged_in()) {
    $displayName = $currentUser['nome'] ?? ($_SESSION['user_name'] ?? '');
} elseif (is_admin_logged_in()) {
    $displayName = $currentAdmin['nome'] ?? ($_SESSION['admin_name'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Greenerry</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $_base ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body data-user-id="<?= (int)$jsUserId ?>">
<script>window.SITE_BASE='<?= $_base ?>';</script>

<div class="sl-overlay" id="sl-overlay"></div>

<div class="shell">
  <aside class="sl" id="sl">
    <a href="<?= $_base ?>/pages/index.php" class="sl-brand"><span class="sl-brand-dot"></span>Greenerry</a>
    <nav class="sl-nav">
      <div class="sl-sec">
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
        <div class="sl-sec">
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
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span data-t="nav_favourites">Favoritos</span>
          </a>
          <a href="<?= $_base ?>/pages/contact_admin.php" class="sl-link <?= $page === 'contact_admin.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span data-t="nav_contact_admin">Falar com o admin</span>
          </a>
        </div>

        <div class="sl-sec">
          <span class="sl-lbl" data-t="nav_tools">Ferramentas</span>
          <a href="<?= $_base ?>/pages/upload_music.php" class="sl-link <?= $page === 'upload_music.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><polyline points="9 18 5 15 9 12"/></svg>
            <span data-t="nav_upload_music">Publicar musica</span>
          </a>
          <a href="<?= $_base ?>/pages/upload_merch.php" class="sl-link <?= $page === 'upload_merch.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><polyline points="16 10 12 6 8 10"/><line x1="12" y1="6" x2="12" y2="16"/></svg>
            <span data-t="nav_upload_merch">Publicar merch</span>
          </a>
          <a href="<?= $_base ?>/pages/orders.php" class="sl-link <?= $page === 'orders.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M8 10h6"/><path d="M8 14h6"/><path d="M8 18h6"/></svg>
            <span data-t="nav_orders">Pedidos</span>
          </a>
          <a href="<?= $_base ?>/pages/revenue.php" class="sl-link <?= $page === 'revenue.php' ? 'on' : '' ?>">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <span data-t="nav_revenue">Rendimento</span>
          </a>
        </div>
      <?php elseif (is_admin_logged_in()): ?>
        <div class="sl-sec">
          <span class="sl-lbl" data-t="nav_admin">Administracao</span>
          <a href="<?= $_base ?>/admin/dashboard.php" class="sl-link">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
            <span data-t="nav_go_admin">Ir para admin</span>
          </a>
        </div>
      <?php endif; ?>
    </nav>

    <div class="sl-foot">
      <?php if (is_user_logged_in() || is_admin_logged_in()): ?>
        <a href="<?= $_base ?>/pages/logout.php" class="sl-link sl-link--muted">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          <span data-t="nav_logout">Sair</span>
        </a>
      <?php endif; ?>
    </div>
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
          <?php if ($displayName): ?>
            <span class="nav-user-name"><?= h($displayName) ?></span>
          <?php elseif (!is_admin_logged_in()): ?>
            <a href="<?= $_base ?>/pages/login.php" class="btn btn-outline btn-sm nav-auth-btn" data-t="nav_login">Entrar</a>
            <a href="<?= $_base ?>/pages/registar.php" class="btn btn-dark btn-sm nav-auth-btn" data-t="nav_register">Registar</a>
          <?php endif; ?>
          <button class="hamburger" id="ham" aria-label="Menu"><span></span><span></span><span></span></button>
        </div>
      </div>
    </nav>
    <div class="page-body pad-top">

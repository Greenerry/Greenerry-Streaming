<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

$page = basename($_SERVER['PHP_SELF']);
$adminAccount = current_admin($conn);
$adminName = trim((string)($_SESSION['admin_name'] ?? 'Admin'));
$adminHomePage = admin_default_page($adminAccount);
$todayLabel = date('l, F j');
$adminPendingCounts = [
    'products' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM produto WHERE estado = 'pendente'")['total'] ?? 0),
    'releases' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM release_musical WHERE estado = 'pendente'")['total'] ?? 0),
    'messages' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM mensagem_admin WHERE estado = 'aberta'")['total'] ?? 0),
];
$adminPageLabels = [
    'dashboard.php' => ['key' => 'nav_dashboard', 'label' => 'Painel'],
    'products.php' => ['key' => 'nav_products', 'label' => 'Produtos'],
    'categories.php' => ['key' => 'nav_categories', 'label' => 'Categorias'],
    'releases.php' => ['key' => 'nav_releases', 'label' => 'Lançamentos'],
    'users.php' => ['key' => 'nav_users', 'label' => 'Utilizadores'],
    'messages.php' => ['key' => 'nav_messages', 'label' => 'Mensagens'],
    'reports.php' => ['key' => 'nav_reports', 'label' => 'Relatórios'],
    'admins.php' => ['key' => 'nav_admins', 'label' => 'Admins'],
    'home_curator.php' => ['key' => 'nav_home_curator', 'label' => 'Homepage'],
    'page_maintenance.php' => ['key' => 'nav_maintenance', 'label' => 'Manutencao'],
    'settings.php' => ['key' => 'nav_settings', 'label' => 'Definicoes'],
];
$adminCurrentPage = $adminPageLabels[$page] ?? ['key' => 'nav_dashboard', 'label' => 'Painel'];
$adminLiveStats = [
    'clients' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM cliente WHERE estado = 'ativo'")['total'] ?? 0),
    'products' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM produto WHERE estado = 'aprovado' AND ativo = 1")['total'] ?? 0),
    'releases' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM release_musical WHERE estado = 'aprovado' AND ativo = 1")['total'] ?? 0),
    'orders' => (int)(db_one($conn, "SELECT COUNT(*) AS total FROM encomenda")['total'] ?? 0),
];
$adminRecentMessages = db_all(
    $conn,
    "SELECT m.assunto, m.estado, m.criado_em, c.nome
     FROM mensagem_admin m
     JOIN cliente c ON c.idCliente = m.idCliente
     ORDER BY m.criado_em DESC
     LIMIT 4"
);
$adminPreviewPages = [
    ['key' => 'preview_home', 'label' => 'Home', 'url' => $_base . '/pages/index.php'],
    ['key' => 'preview_music', 'label' => 'Music', 'url' => $_base . '/pages/music.php'],
    ['key' => 'preview_store', 'label' => 'Store', 'url' => $_base . '/pages/shop.php'],
    ['key' => 'preview_artists', 'label' => 'Artists', 'url' => $_base . '/pages/artists.php'],
];
?>
<!DOCTYPE html>
<html lang="<?= h(current_lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Greenerry Admin</title>
  <script>
    document.documentElement.dataset.theme = localStorage.getItem('g_theme') || 'dark';
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=optional" rel="stylesheet">
  <link rel="stylesheet" href="<?= h($_base) ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
  <link rel="stylesheet" href="<?= h($_base) ?>/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin.css') ?>">
</head>
<body>
<script>
window.CSRF_TOKEN='<?= h(csrf_token()) ?>';
</script>
<div class="theme-wipe" id="theme-wipe" aria-hidden="true"></div>
<div class="admin-shell">
  <button type="button" class="admin-mobile-menu" id="admin-mobile-menu" aria-label="Menu" aria-controls="admin-sidebar" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
  <div class="admin-mobile-overlay" id="admin-mobile-overlay"></div>

  <aside class="admin-sl" id="admin-sidebar">
    <a href="<?= h($adminHomePage) ?>" class="brand"><span class="sl-brand-dot"></span>Greenerry</a>

    <nav class="admin-nav-card" aria-label="Admin">
    <div class="sec">
      <span class="lbl" data-admin-t="nav_summary">Resumo</span>
      <?php if (admin_can('dashboard', $adminAccount)): ?>
      <a href="dashboard.php" class="<?= $page === 'dashboard.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
        <span data-admin-t="nav_dashboard">Painel</span>
      </a>
      <?php endif; ?>
      <?php if (admin_can('home', $adminAccount)): ?>
      <a href="home_curator.php" class="<?= $page === 'home_curator.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3 3 10h2v10h14V10h2z"/><path d="M9 20v-6h6v6"/></svg>
        <span data-admin-t="nav_home_curator">Homepage</span>
      </a>
      <?php endif; ?>
    </div>

    <div class="sec">
      <span class="lbl" data-admin-t="nav_manage">Gestao</span>
      <?php if (admin_can('products', $adminAccount)): ?>
      <a href="products.php" class="<?= $page === 'products.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span data-admin-t="nav_products">Produtos</span>
        <?php if ($adminPendingCounts['products'] > 0): ?>
          <strong class="admin-nav-badge" data-admin-count="product"><?= $adminPendingCounts['products'] ?></strong>
        <?php endif; ?>
      </a>
      <?php endif; ?>
      <?php if (admin_can('categories', $adminAccount)): ?>
      <a href="categories.php" class="<?= $page === 'categories.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h6v6H4z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6H4z"/><path d="M14 14h6v6h-6z"/></svg>
        <span data-admin-t="nav_categories">Categorias</span>
      </a>
      <?php endif; ?>
      <?php if (admin_can('releases', $adminAccount)): ?>
      <a href="releases.php" class="<?= $page === 'releases.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <span data-admin-t="nav_releases">Lançamentos</span>
        <?php if ($adminPendingCounts['releases'] > 0): ?>
          <strong class="admin-nav-badge" data-admin-count="release"><?= $adminPendingCounts['releases'] ?></strong>
        <?php endif; ?>
      </a>
      <?php endif; ?>
    </div>

    <div class="sec">
      <span class="lbl" data-admin-t="nav_operations">Operacoes</span>
      <?php if (admin_can('users', $adminAccount)): ?>
      <a href="users.php" class="<?= $page === 'users.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span data-admin-t="nav_users">Utilizadores</span>
      </a>
      <?php endif; ?>
      <?php if (admin_can('messages', $adminAccount)): ?>
      <a href="messages.php" class="<?= $page === 'messages.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span data-admin-t="nav_messages">Mensagens</span>
        <?php if ($adminPendingCounts['messages'] > 0): ?>
          <strong class="admin-nav-badge" data-admin-count="message"><?= $adminPendingCounts['messages'] ?></strong>
        <?php endif; ?>
      </a>
      <?php endif; ?>
    </div>

    <div class="sec">
      <span class="lbl" data-admin-t="nav_system">Sistema</span>
      <?php if (admin_can('reports', $adminAccount)): ?>
      <a href="reports.php" class="<?= $page === 'reports.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        <span data-admin-t="nav_reports">Relatórios</span>
      </a>
      <?php endif; ?>
      <?php if (admin_can('maintenance', $adminAccount)): ?>
      <a href="page_maintenance.php" class="<?= $page === 'page_maintenance.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3z"/></svg>
        <span data-admin-t="nav_maintenance">Manutencao</span>
      </a>
      <?php endif; ?>
      <?php if (admin_can('admins', $adminAccount)): ?>
      <a href="admins.php" class="<?= $page === 'admins.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
        <span data-admin-t="nav_admins">Admins</span>
      </a>
      <?php endif; ?>
      <?php if (admin_can('settings', $adminAccount)): ?>
      <a href="settings.php" class="<?= $page === 'settings.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.56V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1-1.56 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1H3a2 2 0 1 1 0-4h.09a1.7 1.7 0 0 0 1.56-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.56V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.36.6.99 1 1.6 1H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1z"/></svg>
        <span data-admin-t="nav_settings">Definicoes</span>
      </a>
      <?php endif; ?>
    </div>
    </nav>

    <div class="admin-sidebar-logout">
      <a href="../pages/logout.php">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span data-admin-t="nav_logout">Sair</span>
      </a>
    </div>
  </aside>

  <header class="admin-commandbar">
    <div class="admin-command-actions">
      <div class="lang admin-lang admin-lang--top" id="admin-lang">
        <button type="button" data-l="pt">PT</button>
        <button type="button" data-l="en">EN</button>
      </div>
      <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Theme" title="Theme">
        <svg class="theme-toggle-sun" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
        <svg class="theme-toggle-moon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.6 6.6 0 0 0 9.8 9.8z"/></svg>
      </button>
      <a href="<?= h($_base) ?>/pages/index.php" target="_blank" class="btn btn-dark btn-sm admin-open-site" data-admin-t="admin_open_site">Ver site</a>
    </div>
  </header>

  <main class="admin-main">
    <div class="admin-main-scroll">

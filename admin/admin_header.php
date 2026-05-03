<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

$page = basename($_SERVER['PHP_SELF']);
$adminName = trim((string)($_SESSION['admin_name'] ?? 'Admin'));
?>
<!DOCTYPE html>
<html lang="<?= h(current_lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Greenerry Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <script>
    document.documentElement.dataset.theme = localStorage.getItem('g_theme') || 'dark';
  </script>
  <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<div class="admin-shell">
  <button type="button" class="admin-mobile-menu" id="admin-mobile-menu" aria-label="Menu" aria-controls="admin-sidebar" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
  <div class="admin-mobile-overlay" id="admin-mobile-overlay"></div>

  <aside class="admin-sl" id="admin-sidebar">
    <a href="dashboard.php" class="brand">Greenerry Admin</a>

    <div class="admin-sidebar-tools">
      <span data-admin-t="theme_label">Tema</span>
      <button type="button" class="theme-toggle theme-toggle--admin" id="theme-toggle" aria-label="Theme" title="Theme">
        <svg class="theme-toggle-sun" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
        <svg class="theme-toggle-moon" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.6 6.6 0 0 0 9.8 9.8z"/></svg>
      </button>
    </div>

    <div class="admin-profile-chip">
      <div class="admin-profile-top">
        <span class="admin-profile-kicker" data-admin-t="chip_kicker">Admin</span>
        <div class="lang admin-lang" id="admin-lang">
          <button type="button" data-l="pt">PT</button>
          <button type="button" data-l="en">EN</button>
        </div>
      </div>
      <strong><?= h($adminName) ?></strong>
    </div>

    <div class="sec">
      <span class="lbl" data-admin-t="nav_summary">Resumo</span>
      <a href="dashboard.php" class="<?= $page === 'dashboard.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
        <span data-admin-t="nav_dashboard">Painel</span>
      </a>
    </div>

    <div class="sec">
      <span class="lbl" data-admin-t="nav_manage">Gestao</span>
      <a href="products.php" class="<?= $page === 'products.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span data-admin-t="nav_products">Produtos</span>
      </a>
      <a href="categories.php" class="<?= $page === 'categories.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h6v6H4z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6H4z"/><path d="M14 14h6v6h-6z"/></svg>
        <span data-admin-t="nav_categories">Categorias</span>
      </a>
      <a href="releases.php" class="<?= $page === 'releases.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <span data-admin-t="nav_releases">Lancamentos</span>
      </a>
    </div>

    <div class="sec">
      <span class="lbl" data-admin-t="nav_operations">Operacoes</span>
      <a href="orders.php" class="<?= $page === 'orders.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M8 10h8"/></svg>
        <span data-admin-t="nav_orders">Encomendas</span>
      </a>
      <a href="users.php" class="<?= $page === 'users.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span data-admin-t="nav_users">Utilizadores</span>
      </a>
      <a href="messages.php" class="<?= $page === 'messages.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span data-admin-t="nav_messages">Mensagens</span>
      </a>
    </div>

    <div class="sec">
      <span class="lbl" data-admin-t="nav_system">Sistema</span>
      <a href="reports.php" class="<?= $page === 'reports.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        <span data-admin-t="nav_reports">Relatorios</span>
      </a>
      <a href="password_requests.php" class="<?= $page === 'password_requests.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span data-admin-t="nav_password">Password reset</span>
      </a>
      <a href="settings.php" class="<?= $page === 'settings.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.56V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1-1.56 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1H3a2 2 0 1 1 0-4h.09a1.7 1.7 0 0 0 1.56-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.56V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.36.6.99 1 1.6 1H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1z"/></svg>
        <span data-admin-t="nav_settings">Definicoes</span>
      </a>
    </div>

    <div style="margin-top:auto;padding-top:16px;border-top:1px solid var(--border);">
      <a href="../pages/logout.php" style="color:var(--text3)!important;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span data-admin-t="nav_logout">Sair</span>
      </a>
    </div>
  </aside>

  <main class="admin-main">

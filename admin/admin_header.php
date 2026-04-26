<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

$page = basename($_SERVER['PHP_SELF']);
$adminName = trim((string)($_SESSION['admin_name'] ?? 'Admin'));
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Greenerry Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sl">
    <a href="dashboard.php" class="brand">Greenerry Admin</a>

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
      <a href="releases.php" class="<?= $page === 'releases.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <span data-admin-t="nav_releases">Lancamentos</span>
      </a>
      <a href="messages.php" class="<?= $page === 'messages.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span data-admin-t="nav_messages">Mensagens</span>
      </a>
      <a href="password_requests.php" class="<?= $page === 'password_requests.php' ? 'on' : '' ?>">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span data-admin-t="nav_password">Password reset</span>
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

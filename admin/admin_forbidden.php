<?php
$forbiddenTitle = current_lang() === 'en' ? 'Access limited' : 'Acesso limitado';
$forbiddenText = current_lang() === 'en'
    ? 'Your admin role does not have permission to open this área.'
    : 'O teu cargo de admin não tem permissão para abrir esta área.';
?>
<!DOCTYPE html>
<html lang="<?= h(current_lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Greenerry Admin</title>
  <link rel="stylesheet" href="<?= h($_base ?? '') ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= h($_base ?? '') ?>/assets/css/admin.css">
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card">
      <span class="slabel">Greenerry Admin</span>
      <h2><?= h($forbiddenTitle) ?></h2>
      <p><?= h($forbiddenText) ?></p>
      <a href="dashboard.php" class="btn btn-dark"><?= current_lang() === 'en' ? 'Back to dashboard' : 'Voltar ao painel' ?></a>
    </section>
  </main>
</body>
</html>

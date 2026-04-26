<?php
require_once '../includes/config.php';

logout_all_sessions();
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Greenerry</title>
</head>
<body>
<script>
  try {
    localStorage.removeItem('g_favs_guest');
    sessionStorage.removeItem('g_track');
    sessionStorage.removeItem('g_queue');
  } catch (e) {}
  window.location.replace('../pages/index.php');
</script>
</body>
</html>

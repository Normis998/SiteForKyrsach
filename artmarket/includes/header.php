<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
$user = current_user();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'АртМаркет — фриланс для художников') ?></title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="logo" href="/index.php">🎨 АртМаркет</a>
  <nav>
    <a href="/artists.php">Художники</a>
    <?php if ($user): ?>
      <a href="/orders.php">Заказы</a>
      <a href="/dashboard.php">Кабинет</a>
      <span class="user"><?= e($user['name']) ?></span>
      <a href="/logout.php" class="btn btn-ghost btn-sm">Выйти</a>
    <?php else: ?>
      <a href="/login.php">Вход</a>
      <a href="/register.php" class="btn btn-sm">Регистрация</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
<?php if ($m = flash()): ?>
  <div class="flash"><?= e($m) ?></div>
<?php endif; ?>
<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$artworks = db()->query("
    SELECT a.*, u.name AS artist_name
    FROM artworks a
    JOIN users u ON u.id = a.user_id
    WHERE u.role = 'artist'
    ORDER BY a.created_at DESC
    LIMIT 12
")->fetchAll();

$pageTitle = 'АртМаркет — фриланс для художников';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>Закажите artwork у художника</h1>
  <p>Площадка, где художники находят заказы, а заказчики — свой стиль.</p>
  <a href="/artists.php" class="btn">Найти художника</a>
  <?php if (!current_user()): ?>
    <a href="/register.php" class="btn btn-ghost" style="margin-left:10px">Я художник</a>
  <?php endif; ?>
</section>

<h2>Свежие работы</h2>
<div class="grid">
<?php foreach ($artworks as $a): ?>
  <article class="card">
    <a href="/artwork.php?id=<?= (int)$a['id'] ?>">
      <img src="/<?= e($a['image']) ?>" alt="<?= e($a['title']) ?>">
      <h3><?= e($a['title']) ?></h3>
    </a>
    <p class="muted"><?= e($a['artist_name']) ?></p>
    <p class="price"><?= number_format((float)$a['price'], 0, '.', ' ') ?> ₽</p>
  </article>
<?php endforeach; ?>
<?php if (!$artworks): ?><p class="muted">Пока нет работ.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
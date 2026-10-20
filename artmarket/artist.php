<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'artist'");
$st->execute([$id]);
$artist = $st->fetch();
if (!$artist) { http_response_code(404); exit('Художник не найден'); }

$st = db()->prepare('SELECT * FROM artworks WHERE user_id = ? ORDER BY created_at DESC');
$st->execute([$id]);
$artworks = $st->fetchAll();

$st = db()->prepare('SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE artist_id = ?');
$st->execute([$id]);
$rating = $st->fetch();

$st = db()->prepare('SELECT r.*, u.name AS author FROM reviews r
                     JOIN users u ON u.id = r.author_id
                     WHERE r.artist_id = ? ORDER BY r.created_at DESC LIMIT 20');
$st->execute([$id]);
$reviews = $st->fetchAll();

$me = current_user();
$pageTitle = $artist['name'];
require __DIR__ . '/includes/header.php';
?>
<div class="profile-head">
  <div class="avatar big"><?= e(mb_substr($artist['name'], 0, 1)) ?></div>
  <div>
    <h1 style="margin-top:0"><?= e($artist['name']) ?></h1>
    <?php if ($rating['cnt']): ?>
      <p class="rating">★ <?= number_format((float)$rating['avg_r'], 1) ?>
         · <?= (int)$rating['cnt'] ?> отзыв(ов)</p>
    <?php endif; ?>
    <p><?= nl2br(e($artist['bio'] ?? 'Художник пока не заполнил описание.')) ?></p>
    <?php if ($me && (int)$me['id'] !== (int)$artist['id']): ?>
      <a class="btn" href="/new_order.php?artist_id=<?= (int)$artist['id'] ?>">Заказать работу</a>
    <?php endif; ?>
  </div>
</div>

<h2>Портфолио</h2>
<div class="grid">
<?php foreach ($artworks as $a): ?>
  <article class="card">
    <a href="/artwork.php?id=<?= (int)$a['id'] ?>">
      <img src="/<?= e($a['image']) ?>" alt="<?= e($a['title']) ?>">
    </a>
    <h3><a href="/artwork.php?id=<?= (int)$a['id'] ?>"><?= e($a['title']) ?></a></h3>
    <p class="price"><?= number_format((float)$a['price'], 0, '.', ' ') ?> ₽</p>
  </article>
<?php endforeach; ?>
<?php if (!$artworks): ?><p class="muted">Пока нет работ.</p><?php endif; ?>
</div>

<h2>Отзывы</h2>
<?php foreach ($reviews as $r): ?>
  <div class="review">
    <strong><?= e($r['author']) ?></strong>
    <span class="stars"><?= str_repeat('★', (int)$r['rating']) ?></span>
    <p><?= nl2br(e($r['comment'] ?? '')) ?></p>
  </div>
<?php endforeach; ?>
<?php if (!$reviews): ?><p class="muted">Отзывов пока нет.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.id, u.name, u.bio, COUNT(a.id) AS works,
               (SELECT AVG(rating) FROM reviews WHERE artist_id = u.id) AS rating
        FROM users u
        LEFT JOIN artworks a ON a.user_id = u.id
        WHERE u.role = 'artist'";
$params = [];
if ($q !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.bio LIKE ?)';
    $params = ["%$q%", "%$q%"];
}
$sql .= ' GROUP BY u.id ORDER BY works DESC, u.name ASC';

$st = db()->prepare($sql);
$st->execute($params);
$artists = $st->fetchAll();

$pageTitle = 'Художники';
require __DIR__ . '/includes/header.php';
?>
<h1>Художники</h1>
<form class="search">
  <input name="q" value="<?= e($q) ?>" placeholder="Поиск по имени или описанию">
  <button class="btn">Найти</button>
</form>

<div class="grid">
<?php foreach ($artists as $ar): ?>
  <a class="card" href="/artist.php?id=<?= (int)$ar['id'] ?>" style="text-decoration:none">
    <div class="avatar"><?= e(mb_substr($ar['name'], 0, 1)) ?></div>
    <h3 style="margin-top:10px"><?= e($ar['name']) ?></h3>
    <p class="muted"><?= e(mb_substr($ar['bio'] ?? '', 0, 120)) ?></p>
    <p class="muted">
      Работ: <?= (int)$ar['works'] ?>
      <?php if ($ar['rating']): ?> · ★ <?= number_format((float)$ar['rating'], 1) ?><?php endif; ?>
    </p>
  </a>
<?php endforeach; ?>
<?php if (!$artists): ?><p class="muted">Художники не найдены.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
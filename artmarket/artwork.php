<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT a.*, u.name AS artist_name FROM artworks a
                     JOIN users u ON u.id = a.user_id WHERE a.id = ?');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) { http_response_code(404); exit('Работа не найдена'); }

$me = current_user();
$pageTitle = $a['title'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="/artist.php?id=<?= (int)$a['user_id'] ?>">← <?= e($a['artist_name']) ?></a></p>
<div class="profile-head">
  <img src="/<?= e($a['image']) ?>" alt="<?= e($a['title']) ?>"
       style="max-width:520px;border-radius:14px">
  <div>
    <h1 style="margin-top:0"><?= e($a['title']) ?></h1>
    <p class="price" style="font-size:1.4rem">
      <?= number_format((float)$a['price'], 0, '.', ' ') ?> ₽
    </p>
    <?php if ($a['category']): ?><p class="muted"><?= e($a['category']) ?></p><?php endif; ?>
    <p><?= nl2br(e($a['description'] ?? '')) ?></p>
    <?php if ($me && (int)$me['id'] !== (int)$a['user_id']): ?>
      <a class="btn" href="/new_order.php?artist_id=<?= (int)$a['user_id'] ?>&artwork_id=<?= (int)$a['id'] ?>">
        Заказать похожую работу
      </a>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
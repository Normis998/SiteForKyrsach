<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$artistId  = (int)($_GET['artist_id']  ?? $_POST['artist_id']  ?? 0);
$artworkId = (int)($_GET['artwork_id'] ?? $_POST['artwork_id'] ?? 0) ?: null;

$st = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'artist'");
$st->execute([$artistId]);
$artist = $st->fetch();
if (!$artist) { http_response_code(404); exit('Художник не найден'); }
if ((int)$artist['id'] === (int)$user['id']) exit('Нельзя заказать у себя');

$artwork = null;
if ($artworkId) {
    $st = db()->prepare('SELECT * FROM artworks WHERE id = ? AND user_id = ?');
    $st->execute([$artworkId, $artistId]);
    $artwork = $st->fetch() ?: null;
    if (!$artwork) $artworkId = null;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $budget   = (float)($_POST['budget'] ?? 0);
    $deadline = $_POST['deadline'] ?: null;

    if (mb_strlen($title) < 3)  $errors[] = 'Укажите название заказа';
    if (mb_strlen($desc) < 10)  $errors[] = 'Опишите задачу подробнее';
    if ($budget <= 0)           $errors[] = 'Укажите бюджет';

    if (!$errors) {
        db()->prepare('INSERT INTO orders (customer_id, artist_id, artwork_id, title, description, budget, deadline)
                       VALUES (?,?,?,?,?,?,?)')
            ->execute([$user['id'], $artistId, $artworkId, $title, $desc, $budget, $deadline]);
        flash('Заказ отправлен художнику');
        redirect('/order.php?id=' . (int)db()->lastInsertId());
    }
}
$pageTitle = 'Новый заказ';
require __DIR__ . '/includes/header.php';
?>
<h1>Заказ для <?= e($artist['name']) ?></h1>
<?php if ($artwork): ?>
  <p class="muted">По работе: <a href="/artwork.php?id=<?= (int)$artwork['id'] ?>"><?= e($artwork['title']) ?></a></p>
<?php endif; ?>

<?php foreach ($errors as $er): ?><div class="error"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" class="form">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <input type="hidden" name="artist_id" value="<?= (int)$artistId ?>">
  <?php if ($artworkId): ?>
    <input type="hidden" name="artwork_id" value="<?= (int)$artworkId ?>">
  <?php endif; ?>

  <label>Название заказа
    <input name="title" required
           value="<?= e($_POST['title'] ?? ($artwork['title'] ?? '')) ?>">
  </label>
  <label>Описание задачи
    <textarea name="description" required placeholder="Что нужно нарисовать, стиль, референсы, размер...">
      <?= e($_POST['description'] ?? '') ?>
    </textarea>
  </label>
  <label>Бюджет, ₽
    <input type="number" name="budget" min="1" step="100" required
           value="<?= e($_POST['budget'] ?? ($artwork['price'] ?? '1000')) ?>">
  </label>
  <label>Дедлайн
    <input type="date" name="deadline" value="<?= e($_POST['deadline'] ?? '') ?>">
  </label>
  <button class="btn">Отправить заказ</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
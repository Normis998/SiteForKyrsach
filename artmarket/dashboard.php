<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'profile':
                $name = trim($_POST['name'] ?? '');
                $bio  = trim($_POST['bio'] ?? '');
                if (mb_strlen($name) < 2) throw new RuntimeException('Имя слишком короткое');
                db()->prepare('UPDATE users SET name = ?, bio = ? WHERE id = ?')
                    ->execute([$name, $bio, $user['id']]);
                flash('Профиль обновлён');
                redirect('/dashboard.php');

            case 'artwork':
                if ($user['role'] !== 'artist') {
                    throw new RuntimeException('Добавлять работы могут только художники');
                }
                $title = trim($_POST['title'] ?? '');
                $desc  = trim($_POST['description'] ?? '');
                $price = (float)($_POST['price'] ?? 0);
                $cat   = trim($_POST['category'] ?? '');
                if (mb_strlen($title) < 2) throw new RuntimeException('Укажите название работы');
                $image = upload_image($_FILES['image'] ?? []);
                if (!$image) throw new RuntimeException('Загрузите изображение');
                db()->prepare('INSERT INTO artworks (user_id,title,description,price,image,category)
                               VALUES (?,?,?,?,?,?)')
                    ->execute([$user['id'], $title, $desc, $price, $image, $cat ?: null]);
                flash('Работа добавлена в портфолио');
                redirect('/dashboard.php');

            case 'delete_artwork':
                $aid = (int)($_POST['artwork_id'] ?? 0);
                $st = db()->prepare('SELECT image FROM artworks WHERE id = ? AND user_id = ?');
                $st->execute([$aid, $user['id']]);
                if ($row = $st->fetch()) {
                    db()->prepare('DELETE FROM artworks WHERE id = ?')->execute([$aid]);
                    @unlink(__DIR__ . '/' . $row['image']);
                }
                flash('Работа удалена');
                redirect('/dashboard.php');
        }
    } catch (Throwable $ex) {
        $errors[] = $ex->getMessage();
    }
}

$artworks = [];
$stats = [];
$field = $user['role'] === 'artist' ? 'artist_id' : 'customer_id';
$st = db()->prepare("SELECT status, COUNT(*) c FROM orders WHERE $field = ? GROUP BY status");
$st->execute([$user['id']]);
foreach ($st as $row) $stats[$row['status']] = (int)$row['c'];

if ($user['role'] === 'artist') {
    $st = db()->prepare('SELECT * FROM artworks WHERE user_id = ? ORDER BY created_at DESC');
    $st->execute([$user['id']]);
    $artworks = $st->fetchAll();
}

$pageTitle = 'Личный кабинет';
require __DIR__ . '/includes/header.php';
?>
<h1>Личный кабинет</h1>
<p class="muted">
  <?= $user['role'] === 'artist' ? 'Художник' : 'Заказчик' ?> · <?= e($user['email']) ?>
</p>

<?php foreach ($errors as $er): ?><div class="error"><?= e($er) ?></div><?php endforeach; ?>

<div class="row" style="margin:18px 0 28px">
  <span class="badge pending">Ожидают: <?= $stats['pending'] ?? 0 ?></span>
  <span class="badge accepted">В работе: <?= ($stats['accepted'] ?? 0) + ($stats['in_progress'] ?? 0) ?></span>
  <span class="badge completed">Завершено: <?= $stats['completed'] ?? 0 ?></span>
  <span class="spacer"></span>
  <a class="btn btn-ghost" href="/orders.php">Все заказы →</a>
</div>

<h2>Профиль</h2>
<form method="post" class="form">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <input type="hidden" name="action" value="profile">
  <label>Имя <input name="name" value="<?= e($user['name']) ?>" required></label>
  <label>О себе <?php if ($user['role'] === 'artist'): ?><span class="muted">(стиль, опыт, что рисуете)</span><?php endif; ?>
    <textarea name="bio"><?= e($user['bio'] ?? '') ?></textarea>
  </label>
  <button class="btn">Сохранить</button>
</form>

<?php if ($user['role'] === 'artist'): ?>
  <h2 style="margin-top:40px">Добавить работу</h2>
  <form method="post" class="form" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="action" value="artwork">
    <label>Название <input name="title" required></label>
    <label>Описание <textarea name="description"></textarea></label>
    <label>Категория <input name="category" placeholder="Иллюстрация, портрет, концепт..."></label>
    <label>Цена, ₽ <input type="number" name="price" min="0" step="100" value="1000"></label>
    <label>Изображение <input type="file" name="image" accept="image/*" required></label>
    <button class="btn">Добавить</button>
  </form>

  <h2 style="margin-top:40px">Мои работы</h2>
  <?php if (!$artworks): ?>
    <p class="muted">Пока нет работ.</p>
  <?php else: ?>
    <div class="grid">
    <?php foreach ($artworks as $a): ?>
      <article class="card">
        <img src="/<?= e($a['image']) ?>" alt="">
        <h3><?= e($a['title']) ?></h3>
        <p class="price"><?= number_format((float)$a['price'], 0, '.', ' ') ?> ₽</p>
        <form method="post" onsubmit="return confirm('Удалить работу?')">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="delete_artwork">
          <input type="hidden" name="artwork_id" value="<?= (int)$a['id'] ?>">
          <button class="btn btn-danger btn-sm">Удалить</button>
        </form>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
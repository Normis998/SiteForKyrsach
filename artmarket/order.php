<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$id = (int)($_GET['id'] ?? 0);

$st = db()->prepare('SELECT o.*, c.name AS customer_name, a.name AS artist_name
                     FROM orders o
                     JOIN users c ON c.id = o.customer_id
                     JOIN users a ON a.id = o.artist_id
                     WHERE o.id = ?');
$st->execute([$id]);
$order = $st->fetch();
if (!$order) { http_response_code(404); exit('Заказ не найден'); }

$isCustomer = (int)$order['customer_id'] === (int)$user['id'];
$isArtist   = (int)$order['artist_id']   === (int)$user['id'];
if (!$isCustomer && !$isArtist) { http_response_code(403); exit('Нет доступа к этому заказу'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'message') {
        $body = trim($_POST['body'] ?? '');
        if ($body !== '') {
            db()->prepare('INSERT INTO messages (order_id, sender_id, body) VALUES (?,?,?)')
                ->execute([$order['id'], $user['id'], $body]);
        }
        redirect('/order.php?id=' . $order['id']);
    }

    if ($action === 'review' && $isCustomer && $order['status'] === 'completed') {
        $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');
        try {
            db()->prepare('INSERT INTO reviews (order_id, author_id, artist_id, rating, comment)
                           VALUES (?,?,?,?,?)')
                ->execute([$order['id'], $user['id'], $order['artist_id'], $rating, $comment]);
            flash('Спасибо за отзыв!');
        } catch (PDOException $e) {
            flash('Отзыв по этому заказу уже оставлен');
        }
        redirect('/order.php?id=' . $order['id']);
    }

    $transitions = [
        'accept'   => ['from' => ['pending'],               'to' => 'accepted',    'owner' => 'artist'],
        'decline'  => ['from' => ['pending'],               'to' => 'declined',    'owner' => 'artist'],
        'start'    => ['from' => ['accepted'],              'to' => 'in_progress', 'owner' => 'artist'],
        'complete' => ['from' => ['accepted','in_progress'],'to' => 'completed',   'owner' => 'customer'],
        'cancel'   => ['from' => ['pending','accepted'],    'to' => 'cancelled',   'owner' => 'customer'],
    ];
    if (isset($transitions[$action])) {
        $t = $transitions[$action];
        $allowed = ($t['owner'] === 'artist' && $isArtist) || ($t['owner'] === 'customer' && $isCustomer);
        if ($allowed && in_array($order['status'], $t['from'], true)) {
            db()->prepare('UPDATE orders SET status = ? WHERE id = ?')
                ->execute([$t['to'], $order['id']]);
            flash('Статус обновлён');
        }
        redirect('/order.php?id=' . $order['id']);
    }
}

$st = db()->prepare('SELECT m.*, u.name FROM messages m
                     JOIN users u ON u.id = m.sender_id
                     WHERE m.order_id = ? ORDER BY m.created_at');
$st->execute([$order['id']]);
$messages = $st->fetchAll();

$st = db()->prepare('SELECT * FROM reviews WHERE order_id = ?');
$st->execute([$order['id']]);
$review = $st->fetch() ?: null;

$labels = [
    'pending' => 'Ожидает', 'accepted' => 'Принят', 'declined' => 'Отклонён',
    'in_progress' => 'В работе', 'completed' => 'Завершён', 'cancelled' => 'Отменён',
];

$pageTitle = 'Заказ #' . $order['id'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="/orders.php">← Все заказы</a></p>
<h1>
  <?= e($order['title']) ?>
  <span class="badge <?= e($order['status']) ?>"><?= e($labels[$order['status']]) ?></span>
</h1>

<div class="profile-head">
  <div>
    <p class="muted">Заказчик: <strong><?= e($order['customer_name']) ?></strong></p>
    <p class="muted">Художник: <strong>
      <a href="/artist.php?id=<?= (int)$order['artist_id'] ?>"><?= e($order['artist_name']) ?></a>
    </strong></p>
    <p class="muted">Бюджет: <strong><?= number_format((float)$order['budget'], 0, '.', ' ') ?> ₽</strong></p>
    <?php if ($order['deadline']): ?>
      <p class="muted">Дедлайн: <?= e($order['deadline']) ?></p>
    <?php endif; ?>
    <p class="muted">Создан: <?= e($order['created_at']) ?></p>

    <div class="row" style="margin-top:14px">
      <?php if ($isArtist && $order['status'] === 'pending'): ?>
        <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="accept">
          <button class="btn">Принять заказ</button></form>
        <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="decline">
          <button class="btn btn-ghost">Отклонить</button></form>
      <?php endif; ?>

      <?php if ($isArtist && $order['status'] === 'accepted'): ?>
        <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="start">
          <button class="btn">Начать работу</button></form>
      <?php endif; ?>

      <?php if ($isCustomer && in_array($order['status'], ['accepted','in_progress'], true)): ?>
        <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="complete">
          <button class="btn">Заказ выполнен</button></form>
      <?php endif; ?>

      <?php if ($isCustomer && in_array($order['status'], ['pending','accepted'], true)): ?>
        <form method="post" onsubmit="return confirm('Отменить заказ?')">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="action" value="cancel">
          <button class="btn btn-ghost">Отменить</button></form>
      <?php endif; ?>
    </div>
  </div>
</div>

<h2>Описание задачи</h2>
<p><?= nl2br(e($order['description'])) ?></p>

<h2>Переписка</h2>
<div class="chat" id="chat">
  <?php foreach ($messages as $m): ?>
    <div class="msg <?= (int)$m['sender_id'] === (int)$user['id'] ? 'mine' : '' ?>">
      <?= nl2br(e($m['body'])) ?>
      <small><?= e($m['name']) ?> · <?= e($m['created_at']) ?></small>
    </div>
  <?php endforeach; ?>
  <?php if (!$messages): ?><p class="muted">Сообщений пока нет.</p><?php endif; ?>
</div>

<form method="post" class="form" style="max-width:none;margin-top:14px">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <input type="hidden" name="action" value="message">
  <textarea name="body" placeholder="Написать сообщение..." required></textarea>
  <button class="btn">Отправить</button>
</form>
<script>
  const c = document.getElementById('chat');
  if (c) c.scrollTop = c.scrollHeight;
</script>

<?php if ($order['status'] === 'completed'): ?>
  <h2>Отзыв</h2>
  <?php if ($review): ?>
    <div class="review">
      <span class="stars"><?= str_repeat('★', (int)$review['rating']) ?></span>
      <p><?= nl2br(e($review['comment'] ?? '')) ?></p>
    </div>
  <?php elseif ($isCustomer): ?>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="action" value="review">
      <label>Оценка
        <select name="rating">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>"><?= str_repeat('★', $i) ?></option>
          <?php endfor; ?>
        </select>
      </label>
      <label>Комментарий <textarea name="comment"></textarea></label>
      <button class="btn">Оставить отзыв</button>
    </form>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
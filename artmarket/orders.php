<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$field = $user['role'] === 'artist' ? 'o.artist_id' : 'o.customer_id';
$st = db()->prepare("SELECT o.*, c.name AS customer_name, a.name AS artist_name
                     FROM orders o
                     JOIN users c ON c.id = o.customer_id
                     JOIN users a ON a.id = o.artist_id
                     WHERE $field = ?
                     ORDER BY o.created_at DESC");
$st->execute([$user['id']]);
$orders = $st->fetchAll();

$labels = [
    'pending' => 'Ожидает', 'accepted' => 'Принят', 'declined' => 'Отклонён',
    'in_progress' => 'В работе', 'completed' => 'Завершён', 'cancelled' => 'Отменён',
];

$pageTitle = 'Заказы';
require __DIR__ . '/includes/header.php';
?>
<h1>Заказы</h1>
<p class="muted">Вы — <?= $user['role'] === 'artist' ? 'исполнитель' : 'заказчик' ?>.</p>

<?php if (!$orders): ?>
  <p class="muted">
    Заказов пока нет.
    <?php if ($user['role'] === 'customer'): ?>
      <a href="/artists.php">Найдите художника</a>.
    <?php endif; ?>
  </p>
<?php else: ?>
  <table>
    <tr>
      <th>#</th><th>Заказ</th>
      <th><?= $user['role'] === 'artist' ? 'Заказчик' : 'Художник' ?></th>
      <th>Бюджет</th><th>Статус</th><th>Дата</th>
    </tr>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= (int)$o['id'] ?></td>
        <td><a href="/order.php?id=<?= (int)$o['id'] ?>"><?= e($o['title']) ?></a></td>
        <td><?= e($user['role'] === 'artist' ? $o['customer_name'] : $o['artist_name']) ?></td>
        <td><?= number_format((float)$o['budget'], 0, '.', ' ') ?> ₽</td>
        <td><span class="badge <?= e($o['status']) ?>"><?= e($labels[$o['status']]) ?></span></td>
        <td class="muted"><?= e($o['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
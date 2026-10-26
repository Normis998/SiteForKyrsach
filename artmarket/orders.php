<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Получаем заказы, где я либо заказчик, либо художник
$sql = "SELECT o.*, 
               c.username as client_name, 
               a.username as artist_name 
        FROM orders o
        JOIN users c ON o.client_id = c.id
        JOIN users a ON o.artist_id = a.id
        WHERE o.client_id = ? OR o.artist_id = ?
        ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $user_id]);
$orders = $stmt->fetchAll();

// Функция для перевода статусов на русский
function getStatusName($status) {
    $names = [
        'pending' => 'Ожидает',
        'in_progress' => 'В работе',
        'completed' => 'Выполнен',
        'cancelled' => 'Отклонен'
    ];
    return $names[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои заказы - АртМаркет</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>Мои заказы</h2>
        
        <?php if (empty($orders)): ?>
            <div class="card">
                <p>У вас пока нет заказов.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;"> <!-- Чтобы таблица не ломала экран на телефонах -->
                <table>
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Заказчик</th>
                            <th>Художник</th>
                            <th>Бюджет</th>
                            <th>Статус</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                <td><?= escape($order['client_name']) ?></td>
                                <td><?= escape($order['artist_name']) ?></td>
                                <td><?= escape($order['budget']) ?> ₽</td>
                                <td style="font-weight: bold; color: <?= $order['status'] === 'completed' ? '#2ecc71' : ($order['status'] === 'cancelled' ? '#e74c3c' : '#f39c12') ?>;">
                                    <?= getStatusName($order['status']) ?>
                                </td>
                                <td>
                                    <a href="order.php?id=<?= $order['id'] ?>">Открыть</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
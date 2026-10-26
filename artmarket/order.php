<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$order_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$error = '';

// Получаем заказ
$stmt = $pdo->prepare("SELECT o.*, c.username as client_name, a.username as artist_name 
                       FROM orders o 
                       JOIN users c ON o.client_id = c.id 
                       JOIN users a ON o.artist_id = a.id 
                       WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order || ($order['client_id'] != $user_id && $order['artist_id'] != $user_id)) {
    die("Заказ не найден или у вас нет доступа.");
}

// Обработка отправки сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $msg = trim($_POST['message'] ?? '');
    if (!empty($msg)) {
        $stmt = $pdo->prepare("INSERT INTO messages (order_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $user_id, $msg]);
        header("Location: order.php?id=$order_id");
        exit;
    }
}

// Обработка смены статуса (только для художника)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $user_id == $order['artist_id']) {
    $new_status = $_POST['status'] ?? '';
    $allowed = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        header("Location: order.php?id=$order_id");
        exit;
    }
}

// Обработка отзыва (только для заказчика и только для выполненного заказа)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_review']) && $user_id == $order['client_id'] && $order['status'] === 'completed') {
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    
    $check = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ?");
    $check->execute([$order_id]);
    
    if (!$check->fetch() && $comment) {
        $stmt = $pdo->prepare("INSERT INTO reviews (order_id, client_id, artist_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $user_id, $order['artist_id'], $rating, $comment]);
        header("Location: order.php?id=$order_id");
        exit;
    }
}

// Получаем сообщения
$stmt = $pdo->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.order_id = ? ORDER BY m.created_at ASC");
$stmt->execute([$order_id]);
$messages = $stmt->fetchAll();

// Проверяем, есть ли отзыв
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE order_id = ?");
$stmt->execute([$order_id]);
$existing_review = $stmt->fetch();

function getStatusName($status) {
    $names = ['pending' => 'Ожидает', 'in_progress' => 'В работе', 'completed' => 'Выполнен', 'cancelled' => 'Отклонен'];
    return $names[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ #<?= $order_id ?> - АртМаркет</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <div class="card">
            <h2>Заказ #<?= $order_id ?> (<?= getStatusName($order['status']) ?>)</h2>
            <p><strong>Заказчик:</strong> <?= escape($order['client_name']) ?> | <strong>Художник:</strong> <?= escape($order['artist_name']) ?></p>
            <p><strong>Бюджет:</strong> <?= escape($order['budget']) ?> ₽</p>
            
            <div style="background: #f4f7f6; padding: 15px; border-radius: 6px; margin-top: 15px;">
                <h3>Описание:</h3>
                <p style="margin-bottom: 0;"><?= nl2br(escape($order['description'])) ?></p>
            </div>

            <?php if ($user_id == $order['artist_id']): ?>
                <form method="POST" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <input type="hidden" name="update_status" value="1">
                    <label><strong>Изменить статус заказа:</strong></label>
                    <div style="display: flex; gap: 10px;">
                        <select name="status" style="margin-bottom: 0; width: auto;">
                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Ожидает</option>
                            <option value="in_progress" <?= $order['status'] == 'in_progress' ? 'selected' : '' ?>>В работе</option>
                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Выполнен</option>
                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отклонен</option>
                        </select>
                        <button type="submit">Обновить</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($order['status'] === 'completed'): ?>
            <div class="card" style="background: #fff8e1;">
                <h3>Отзыв о работе</h3>
                
                <?php if ($existing_review): ?>
                    <p><strong>Оценка заказчика:</strong> <span style="color: #f39c12; font-size: 18px;"><?= str_repeat('★', $existing_review['rating']) ?></span></p>
                    <p><strong>Комментарий:</strong> <?= nl2br(escape($existing_review['comment'])) ?></p>
                
                <?php elseif ($user_id == $order['client_id']): ?>
                    <form method="POST">
                        <input type="hidden" name="leave_review" value="1">
                        <p>
                            <label>Оцените работу (от 1 до 5):</label>
                            <select name="rating" required style="width: auto;">
                                <option value="5">5 ★ (Отлично)</option>
                                <option value="4">4 ★ (Хорошо)</option>
                                <option value="3">3 ★ (Нормально)</option>
                                <option value="2">2 ★ (Плохо)</option>
                                <option value="1">1 ★ (Ужасно)</option>
                            </select>
                        </p>
                        <p>
                            <label>Комментарий:</label>
                            <textarea name="comment" rows="3" required placeholder="Напишите, как прошла работа..."></textarea>
                        </p>
                        <button type="submit" class="btn-success">Отправить отзыв</button>
                    </form>
                <?php else: ?>
                    <p style="color: #888;">Заказчик пока не оставил отзыв.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Чат по заказу</h3>
            <div class="chat-box">
                <?php if (empty($messages)): ?>
                    <p style="text-align: center; color: #888;">Сообщений пока нет. Напишите первым!</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $isMine = ($msg['sender_id'] == $user_id); ?>
                        <div class="message <?= $isMine ? 'my-msg' : 'their-msg' ?>">
                            <span class="sender"><?= escape($msg['username']) ?>, <?= date('H:i', strtotime($msg['created_at'])) ?></span>
                            <?= nl2br(escape($msg['message'])) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="POST" style="display: flex; gap: 10px;">
                <input type="hidden" name="send_message" value="1">
                <input type="text" name="message" placeholder="Напишите сообщение..." required style="margin-bottom: 0;">
                <button type="submit" class="btn-success">Отправить</button>
            </form>
        </div>

        <p><a href="orders.php" class="btn" style="background: #95a5a6;">← Назад к списку заказов</a></p>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
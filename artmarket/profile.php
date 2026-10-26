<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$profile_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    die("Пользователь не найден.");
}

// Получаем выполненные заказы этого пользователя
$sql_orders = "SELECT o.*, c.username as client_name, a.username as artist_name 
               FROM orders o 
               JOIN users c ON o.client_id = c.id 
               JOIN users a ON o.artist_id = a.id 
               WHERE (o.client_id = ? OR o.artist_id = ?) AND o.status = 'completed'
               ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql_orders);
$stmt->execute([$profile_id, $profile_id]);
$completed_orders = $stmt->fetchAll();

// Получаем отзывы (если это художник, показываем отзывы о нем)
$stmt = $pdo->prepare("SELECT r.*, u.username as client_name 
                       FROM reviews r 
                       JOIN users u ON r.client_id = u.id 
                       WHERE r.artist_id = ? 
                       ORDER BY r.created_at DESC");
$stmt->execute([$profile_id]);
$reviews = $stmt->fetchAll();

// Средний рейтинг
$avg_rating = 0;
if (count($reviews) > 0) {
    $sum = 0;
    foreach ($reviews as $r) $sum += $r['rating'];
    $avg_rating = round($sum / count($reviews), 1);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль <?= escape($profile_user['username']) ?> - АртМаркет</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <div class="card" style="display: flex; gap: 30px; align-items: flex-start;">
            <div style="text-align: center;">
                <?php 
                    $avatarPath = 'uploads/avatars/' . $profile_user['avatar'];
                    if ($profile_user['avatar'] === 'default.jpg' || !file_exists($avatarPath)) {
                        echo '<div style="width: 150px; height: 150px; background: #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #666;">Нет фото</div>';
                    } else {
                        echo '<img src="' . escape($avatarPath) . '" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #3498db;">';
                    }
                ?>
                <h2 style="margin: 15px 0 5px;"><?= escape($profile_user['username']) ?></h2>
                <span style="background: #e1f5fe; color: #0288d1; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                    <?= $profile_user['role'] === 'artist' ? 'Художник' : 'Заказчик' ?>
                </span>
            </div>
            
            <div style="flex: 1;">
                <h3>О пользователе</h3>
                <p><?= nl2br(escape($profile_user['bio'] ?: 'Пользователь пока ничего не рассказал о себе.')) ?></p>
                
                <?php if ($profile_user['role'] === 'artist' && count($reviews) > 0): ?>
                    <div style="margin-top: 15px; padding: 10px; background: #fff8e1; border-radius: 6px; display: inline-block;">
                        <strong>Рейтинг: </strong>
                        <span style="color: #f39c12; font-size: 18px;">★</span> 
                        <strong><?= $avg_rating ?> / 5</strong> (на основе <?= count($reviews) ?> отзывов)
                    </div>
                <?php endif; ?>

                <?php if (isLoggedIn() && $_SESSION['user_id'] == $profile_id): ?>
                    <div style="margin-top: 20px;">
                        <a href="edit_profile.php" class="btn">Редактировать профиль</a>
                    </div>
                <?php elseif (isLoggedIn() && $_SESSION['role'] === 'client' && $profile_user['role'] === 'artist'): ?>
                    <div style="margin-top: 20px;">
                        <a href="artist.php?id=<?= $profile_user['id'] ?>" class="btn btn-success">Смотреть портфолио и заказать</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($profile_user['role'] === 'artist'): ?>
            <div class="card">
                <h3>Отзывы от заказчиков</h3>
                <?php if (empty($reviews)): ?>
                    <p style="color: #888;">Пока нет отзывов.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div style="border-bottom: 1px solid #eee; padding: 15px 0;">
                            <strong><?= escape($rev['client_name']) ?></strong> 
                            <span style="color: #f39c12;"><?= str_repeat('★', $rev['rating']) ?></span>
                            <span style="color: #999; font-size: 12px; margin-left: 10px;"><?= date('d.m.Y', strtotime($rev['created_at'])) ?></span>
                            <p style="margin: 5px 0 0;"><?= nl2br(escape($rev['comment'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Выполненные заказы</h3>
            <?php if (empty($completed_orders)): ?>
                <p style="color: #888;">Пока нет завершенных заказов.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($completed_orders as $order): ?>
                        <li style="margin-bottom: 10px;">
                            Заказ #<?= $order['id'] ?> (<?= escape($order['client_name']) ?> заказал у <?= escape($order['artist_name']) ?>) — 
                            <strong><?= escape($order['budget']) ?> ₽</strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
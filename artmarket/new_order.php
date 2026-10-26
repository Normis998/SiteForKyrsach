<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Только авторизованные заказчики могут делать заказы
if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    redirect('index.php');
}

$artist_id = $_GET['artist_id'] ?? 0;
$error = '';
$success = '';

// Проверяем, существует ли художник
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'artist'");
$stmt->execute([$artist_id]);
$artist = $stmt->fetch();

if (!$artist) {
    die("Художник не найден.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    $budget = trim($_POST['budget'] ?? 0);

    if (empty($description)) {
        $error = 'Пожалуйста, опишите, что вы хотите заказать.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO orders (client_id, artist_id, description, budget) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $artist_id, $description, $budget])) {
            $success = 'Заказ успешно отправлен художнику!';
        } else {
            $error = 'Ошибка при создании заказа.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Новый заказ - АртМаркет</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
    <?php include 'includes/header.php'; ?>
    <main style="max-width: 600px; margin: 20px auto; padding: 20px;">
        <h2>Создать заказ для художника: <?= escape($artist['username']) ?></h2>
        
        <?php if ($success): ?>
            <p style="color: green; font-weight: bold;"><?= escape($success) ?></p>
            <p><a href="orders.php">Перейти к моим заказам</a></p>
        <?php else: ?>
            <?php if ($error): ?><p style="color: red;"><?= escape($error) ?></p><?php endif; ?>
            
            <form method="POST">
                <p>
                    <label>Опишите задачу (что нужно нарисовать, стиль, референсы):<br>
                    <textarea name="description" rows="6" style="width: 100%;" required></textarea></label>
                </p>
                <p>
                    <label>Ваш бюджет (в рублях):<br>
                    <input type="number" name="budget" min="0" step="100" value="1000" required></label>
                </p>
                <button type="submit" style="padding: 10px 20px; background: #4CAF50; color: white; border: none;">Отправить заказ</button>
            </form>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
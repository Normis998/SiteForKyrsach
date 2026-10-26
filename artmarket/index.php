<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

try {
    $stmt = $pdo->query("SELECT a.*, u.username FROM artworks a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10");
    $artworks = $stmt->fetchAll();
} catch (PDOException $e) {
    $artworks = [];
    $error = "Ошибка базы данных: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>АртМаркет - Платформа для художников</title>
    <link rel="stylesheet" href="assets/style.css"> 
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main style="max-width: 1000px; margin: 20px auto; padding: 0 20px;">
        <h1>Добро пожаловать на АртМаркет!</h1>
        <p>Здесь вы можете найти художника или показать свои работы.</p>

        <?php if (isset($error)): ?>
            <p style="color: red;"><?= escape($error) ?></p>
        <?php endif; ?>

        <h2>Свежие работы</h2>
        <div class="gallery" style="display: flex; gap: 20px; flex-wrap: wrap;">
            <?php if (empty($artworks)): ?>
                <p>Пока нет загруженных работ. (База данных пуста).</p>
            <?php else: ?>
                <?php foreach ($artworks as $art): ?>
                    <div class="art-card">
                        <img src="uploads/<?= escape($art['image_path']) ?>" alt="<?= escape($art['title']) ?>" style="width: 100%;">
                        <h3><?= escape($art['title']) ?></h3>
                        <p>Автор: <?= escape($art['username']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
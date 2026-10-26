<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$artist_id = $_GET['id'] ?? 0;

// Инфа о художнике
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'artist'");
$stmt->execute([$artist_id]);
$artist = $stmt->fetch();

if (!$artist) {
    die("Художник не найден.");
}

// Работы художника
$stmt = $pdo->prepare("SELECT * FROM artworks WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$artist_id]);
$artworks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title><?= escape($artist['username']) ?> - АртМаркет</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
    <?php include 'includes/header.php'; ?>
    <main style="max-width: 1000px; margin: 20px auto; padding: 20px;">
        <h2>Профиль художника: <?= escape($artist['username']) ?></h2>
        <p><?= escape($artist['bio'] ?: 'Художник пока не рассказал о себе.') ?></p>
        
        <?php if (isLoggedIn() && $_SESSION['role'] === 'client'): ?>
            <p>
                <a href="new_order.php?artist_id=<?= $artist['id'] ?>">
                    <button style="background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer;">Заказать работу</button>
                </a>
            </p>
        <?php endif; ?>

        <hr>
        <h3>Портфолио</h3>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <?php if (empty($artworks)): ?>
                <p>Художник пока не загрузил работы.</p>
            <?php else: ?>
                <?php foreach ($artworks as $art): ?>
                    <div style="border: 1px solid #ddd; padding: 10px; width: 250px;">
                        <a href="artwork.php?id=<?= $art['id'] ?>">
                            <img src="uploads/<?= escape($art['image_path']) ?>" alt="<?= escape($art['title']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                        </a>
                        <h4><?= escape($art['title']) ?></h4>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
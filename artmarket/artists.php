<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'artist' ORDER BY created_at DESC");
$artists = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Художники - АртМаркет</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>Наши художники</h2>
        <div class="gallery">
            <?php if (empty($artists)): ?>
                <p>Пока нет зарегистрированных художников.</p>
            <?php else: ?>
                <?php foreach ($artists as $artist): ?>
                    <div class="card" style="text-align: center;">
                        <?php 
                            $avatarPath = 'uploads/avatars/' . $artist['avatar'];
                            if ($artist['avatar'] === 'default.jpg' || !file_exists($avatarPath)) {
                                echo '<div style="width: 80px; height: 80px; background: #ccc; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white;">?</div>';
                            } else {
                                echo '<img src="' . escape($avatarPath) . '" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 10px; display: block;">';
                            }
                        ?>
                        <h3><?= escape($artist['username']) ?></h3>
                        <p style="font-size: 14px; color: #666; height: 40px; overflow: hidden;"><?= escape($artist['bio'] ?: 'Без описания') ?></p>
                        
                        <a href="profile.php?id=<?= $artist['id'] ?>" class="btn" style="width: 100%; box-sizing: border-box; margin-bottom: 5px;">Профиль и отзывы</a>
                        <a href="artist.php?id=<?= $artist['id'] ?>" class="btn btn-success" style="width: 100%; box-sizing: border-box;">Портфолио</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
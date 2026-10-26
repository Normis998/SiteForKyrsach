<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');
    $avatar_name = $user['avatar'];

    // Загрузка аватарки
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['avatar']['type'], $allowedTypes)) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_name = 'avatar_' . uniqid() . '.' . $ext;
            $uploadPath = 'uploads/avatars/' . $new_name;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
                $avatar_name = $new_name;
                if ($user['avatar'] !== 'default.jpg' && file_exists('uploads/avatars/' . $user['avatar'])) {
                    unlink('uploads/avatars/' . $user['avatar']);
                }
            }
        } else {
            $message = "Неверный формат картинки. Нужен JPG или PNG.";
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET bio = ?, avatar = ? WHERE id = ?");
    if ($stmt->execute([$bio, $avatar_name, $user_id])) {
        $message = "Профиль успешно обновлен!";
        $user['bio'] = $bio;
        $user['avatar'] = $avatar_name;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать профиль - АртМаркет</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h2>Редактировать профиль</h2>
            
            <?php if ($message): ?>
                <p style="color: green; font-weight: bold;"><?= escape($message) ?></p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
                    <div>
                        <?php 
                            $avatarPath = 'uploads/avatars/' . $user['avatar'];
                            if ($user['avatar'] === 'default.jpg' || !file_exists($avatarPath)) {
                                echo '<div style="width: 100px; height: 100px; background: #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">Нет фото</div>';
                            } else {
                                echo '<img src="' . escape($avatarPath) . '" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db;">';
                            }
                        ?>
                    </div>
                    <div style="flex: 1;">
                        <label>Загрузить аватарку (JPG/PNG):</label>
                        <input type="file" name="avatar" accept="image/*">
                    </div>
                </div>

                <p>
                    <label>О себе (Стиль работы, опыт):</label>
                    <textarea name="bio" rows="5" placeholder="Привет! Я рисую..."><?= escape($user['bio']) ?></textarea>
                </p>
                
                <button type="submit" class="btn-success">Сохранить изменения</button>
                <a href="profile.php?id=<?= $user_id ?>" class="btn" style="background: #95a5a6;">Смотреть мой профиль</a>
            </form>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
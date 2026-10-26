<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';

// Обработка загрузки работы (только для художников)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'artist' && isset($_FILES['image'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image = $_FILES['image'];

    if ($title && $image['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($image['type'], $allowedTypes)) {
            $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
            $filename = uniqid('art_') . '.' . $ext;
            $uploadPath = 'uploads/' . $filename;

            if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("INSERT INTO artworks (user_id, title, description, image_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $title, $description, $filename]);
                $message = "Работа успешно загружена!";
            } else {
                $message = "Ошибка при сохранении файла.";
            }
        } else {
            $message = "Можно загружать только JPG, PNG или GIF.";
        }
    } else {
        $message = "Заполните название и выберите файл.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$artworks = [];
if ($role === 'artist') {
    $stmt = $pdo->prepare("SELECT * FROM artworks WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $artworks = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет - АртМаркет</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>Личный кабинет</h2>
        <p>Привет, <strong><?= escape($user['username']) ?></strong>! Твоя роль: <strong><?= $role === 'artist' ? 'Художник' : 'Заказчик' ?></strong></p>
        
        <?php if ($message): ?>
            <p style="color: green; font-weight: bold;"><?= escape($message) ?></p>
        <?php endif; ?>

        <div style="display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap;">
            <a href="index.php" class="btn">На главную</a>
            <a href="artists.php" class="btn">Список художников</a>
            <a href="orders.php" class="btn">Мои заказы</a>
            <a href="profile.php?id=<?= $user_id ?>" class="btn btn-success">Мой профиль</a>
        </div>

        <?php if ($role === 'artist'): ?>
            <div class="card">
                <h3>Добавить новую работу в портфолио</h3>
                <form method="POST" enctype="multipart/form-data">
                    <p><label>Название:<br><input type="text" name="title" required></label></p>
                    <p><label>Описание:<br><textarea name="description" rows="3"></textarea></label></p>
                    <p><label>Картинка (JPG/PNG):<br><input type="file" name="image" accept="image/*" required></label></p>
                    <button type="submit">Загрузить</button>
                </form>
            </div>

            <div class="card">
                <h3>Мои работы</h3>
                <div class="gallery">
                    <?php foreach ($artworks as $art): ?>
                        <div class="art-card">
                            <img src="uploads/<?= escape($art['image_path']) ?>" alt="<?= escape($art['title']) ?>">
                            <h3 style="padding: 10px 15px;"><?= escape($art['title']) ?></h3>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
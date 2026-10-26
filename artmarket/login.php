<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        redirect('dashboard.php');
    } else {
        $error = 'Неверное имя пользователя или пароль.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/style.css">
    <title>Вход - АртМаркет</title>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc;">
        <h2>Вход</h2>
        <?php if ($error): ?><p style="color: red;"><?= escape($error) ?></p><?php endif; ?>
        <form method="POST">
            <p><label>Имя пользователя:<br><input type="text" name="username" required></label></p>
            <p><label>Пароль:<br><input type="password" name="password" required></label></p>
            <button type="submit">Войти</button>
        </form>
        <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'client';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните все поля.';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким именем или email уже существует.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hash, $role])) {
                redirect('login.php');
            } else {
                $error = 'Ошибка при регистрации. Попробуйте снова.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Регистрация - АртМаркет</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
    <?php include 'includes/header.php'; ?>
    <main style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc;">
        <h2>Регистрация</h2>
        <?php if ($error): ?><p style="color: red;"><?= escape($error) ?></p><?php endif; ?>
        <form method="POST">
            <p><label>Имя пользователя:<br><input type="text" name="username" required></label></p>
            <p><label>Email:<br><input type="email" name="email" required></label></p>
            <p><label>Пароль:<br><input type="password" name="password" required></label></p>
            <p><label>Я:<br>
                <select name="role">
                    <option value="client">Заказчик (ищу художника)</option>
                    <option value="artist">Художник (ищу заказы)</option>
                </select>
            </label></p>
            <button type="submit">Зарегистрироваться</button>
        </form>
        <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();

    if ($u && password_verify($pass, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];
        flash('С возвращением, ' . $u['name'] . '!');
        redirect('/dashboard.php');
    }
    $errors[] = 'Неверный email или пароль';
}
$pageTitle = 'Вход';
require __DIR__ . '/includes/header.php';
?>
<h1>Вход</h1>
<?php foreach ($errors as $er): ?><div class="error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="form">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <label>Email <input type="email" name="email" required></label>
  <label>Пароль <input type="password" name="password" required></label>
  <button class="btn">Войти</button>
</form>
<p class="muted">Нет аккаунта? <a href="/register.php">Зарегистрируйтесь</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
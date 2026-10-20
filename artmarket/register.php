<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = ($_POST['role'] ?? 'customer') === 'artist' ? 'artist' : 'customer';

    if (mb_strlen($name) < 2)                             $errors[] = 'Имя слишком короткое';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors[] = 'Неверный email';
    if (strlen($pass) < 8)                                $errors[] = 'Пароль минимум 8 символов';

    if (!$errors) {
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) $errors[] = 'Этот email уже зарегистрирован';
    }

    if (!$errors) {
        $st = db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)');
        $st->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)db()->lastInsertId();
        flash('Добро пожаловать на АртМаркет!');
        redirect('/dashboard.php');
    }
}
$pageTitle = 'Регистрация';
require __DIR__ . '/includes/header.php';
?>
<h1>Регистрация</h1>
<?php foreach ($errors as $er): ?><div class="error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="form">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <label>Имя <input name="name" value="<?= e($_POST['name'] ?? '') ?>" required></label>
  <label>Email <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></label>
  <label>Пароль <input type="password" name="password" minlength="8" required></label>
  <label>Я регистрируюсь как
    <select name="role">
      <option value="customer">Заказчик</option>
      <option value="artist">Художник</option>
    </select>
  </label>
  <button class="btn">Создать аккаунт</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
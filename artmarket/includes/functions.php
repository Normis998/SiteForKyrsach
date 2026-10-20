<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function current_user(): ?array
{
    static $loaded = false, $user = null;
    if (!$loaded) {
        $loaded = true;
        if (!empty($_SESSION['user_id'])) {
            $st = db()->prepare('SELECT * FROM users WHERE id = ?');
            $st->execute([$_SESSION['user_id']]);
            $user = $st->fetch() ?: null;
            if (!$user) unset($_SESSION['user_id']);
        }
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) redirect('/login.php');
    return $u;
}

/** Без аргумента — читает и очищает flash. С аргументом — записывает. */
function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF token mismatch');
    }
}

/** Загрузка изображения. Возвращает относительный путь или null, если файла нет. */
function upload_image(array $file, string $subdir = 'artworks'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки файла');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Файл больше 5 МБ');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info) {
        throw new RuntimeException('Это не изображение');
    }
    $map = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $ext = $map[$info['mime']] ?? null;
    if (!$ext) {
        throw new RuntimeException('Неподдерживаемый формат (jpg, png, webp, gif)');
    }
    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Не удалось сохранить файл');
    }
    return 'uploads/' . $subdir . '/' . $name;
}
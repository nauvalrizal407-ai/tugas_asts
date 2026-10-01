<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

// Lokasi ini berada di folder aplikasi agar sesi tetap bekerja pada instalasi
// XAMPP yang folder temp PHP-nya tidak bisa ditulis Apache.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_save_path(__DIR__ . DIRECTORY_SEPARATOR . '.sessions');
    session_start();
}

require_once __DIR__ . '/koneksi.php';

// Isi melalui environment variable FONNTE_TOKEN di server. Jangan menaruh token
// di JavaScript karena token tersebut adalah rahasia aplikasi.
const FONNTE_ENDPOINT = 'https://api.fonnte.com/send';
const FONNTE_TOKEN = 'sxJ32QB6Dqb19qWN2X1J'; // Lebih aman gunakan env FONNTE_TOKEN di server.

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(419);
        exit('Permintaan tidak valid. Silakan muat ulang halaman.');
    }
}

function setup_schema(): void
{
    global $koneksi;

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        nisn VARCHAR(20) NOT NULL,
        ttl VARCHAR(150) NOT NULL,
        gender VARCHAR(20) NOT NULL,
        email VARCHAR(190) NOT NULL,
        address TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'phone' => "ALTER TABLE users ADD phone VARCHAR(30) NULL AFTER email",
        'password_hash' => "ALTER TABLE users ADD password_hash VARCHAR(255) NULL AFTER phone",
        'role' => "ALTER TABLE users ADD role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash",
        'created_at' => "ALTER TABLE users ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE users ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];

    foreach ($columns as $column => $alter) {
        $check = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE '" . $column . "'");
        if ($check && mysqli_num_rows($check) === 0) {
            mysqli_query($koneksi, $alter);
        }
    }

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        type VARCHAR(40) NOT NULL,
        message VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_created (created_at),
        INDEX idx_notifications_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        set_flash('error', 'Halaman tersebut hanya bisa diakses admin.');
        redirect('profile.php');
    }
}

function current_user(): ?array
{
    global $koneksi;
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $id = (int) $_SESSION['user_id'];
    $stmt = mysqli_prepare($koneksi, 'SELECT * FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result) ?: null;
}

function normalize_phone(string $phone): string
{
    $phone = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';
    if (str_starts_with($phone, '+')) {
        $phone = substr($phone, 1);
    }
    if (str_starts_with($phone, '0')) {
        $phone = '62' . substr($phone, 1);
    } elseif (str_starts_with($phone, '8')) {
        $phone = '62' . $phone;
    }
    return $phone;
}

function send_whatsapp(?string $phone, string $message): bool
{
    $token = trim((string) (getenv('FONNTE_TOKEN') ?: FONNTE_TOKEN));
    $target = normalize_phone((string) $phone);
    if ($token === '' || $target === '' || !function_exists('curl_init')) {
        return false;
    }

    $curl = curl_init(FONNTE_ENDPOINT);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['target' => $target, 'message' => $message, 'countryCode' => '62'],
        CURLOPT_HTTPHEADER => ['Authorization: ' . $token],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $result = is_string($response) ? json_decode($response, true) : null;
    $success = $response !== false && $httpCode >= 200 && $httpCode < 300;

    // Fonnte dapat mengembalikan HTTP 200 ketika proses ditolak. Periksa status API juga.
    if ($success && is_array($result) && array_key_exists('status', $result)) {
        $success = in_array($result['status'], [true, 1, '1', 'true'], true);
    }

    curl_close($curl);
    return $success;
}

function notify_admins(string $message): int
{
    global $koneksi;
    mysqli_query($koneksi, "INSERT INTO notifications (user_id, type, message) VALUES (NULL, 'user_new', '" . mysqli_real_escape_string($koneksi, $message) . "')");

    $admins = mysqli_query($koneksi, "SELECT phone FROM users WHERE role = 'admin' AND phone IS NOT NULL AND phone <> ''");
    $sent = 0;
    $recipients = 0;
    while ($admin = mysqli_fetch_assoc($admins)) {
        $recipients++;
        if (send_whatsapp($admin['phone'], $message)) {
            $sent++;
        }
    }

    return $recipients === 0 ? -1 : $sent;
}

setup_schema();

<?php
require_once __DIR__ . '/config.php';
$existing = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM users WHERE role = 'admin' LIMIT 1"));
if ($existing) { set_flash('error', 'Admin sudah tersedia. Silakan login.'); redirect('login.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $name = trim($_POST['name'] ?? 'Admin'); $email = strtolower(trim($_POST['email'] ?? '')); $phone = normalize_phone($_POST['phone'] ?? ''); $password = (string)($_POST['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^62[0-9]{8,14}$/', $phone) || strlen($password) < 6) { $error = 'Nama, email, nomor HP valid, dan password minimal 6 karakter wajib diisi.'; }
    else {
        $nisn = 'ADMIN' . random_int(100000, 999999); $ttl = '-'; $gender = '-'; $address = '-'; $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi, "INSERT INTO users (name, nisn, ttl, gender, email, phone, password_hash, role, address) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', ?)"); mysqli_stmt_bind_param($stmt, 'ssssssss', $name, $nisn, $ttl, $gender, $email, $phone, $hash, $address);
        if (mysqli_stmt_execute($stmt)) { set_flash('success', 'Admin berhasil dibuat. Silakan login.'); redirect('login.php'); } $error = 'Gagal membuat admin: ' . mysqli_error($koneksi);
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup Admin</title><link rel="stylesheet" href="app.css"></head><body><?php require __DIR__ . '/decor.php'; ?><main class="wrap narrow"><div class="card"><h1>Setup Admin Pertama</h1><p class="muted">Halaman ini otomatis terkunci setelah admin pertama dibuat.</p><?php if($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Nama</label><input name="name" required><label>Email</label><input type="email" name="email" required><label>No. HP / WhatsApp</label><input name="phone" placeholder="08123456789" required><label>Password</label><input type="password" name="password" minlength="6" required><div class="actions"><button class="primary">Buat Admin</button><a class="btn secondary" href="login.php">Batal</a></div></form></div></main></body></html>

<?php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['user_id'])) { redirect('profile.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? ''); $nisn = trim($_POST['nisn'] ?? ''); $ttl = trim($_POST['ttl'] ?? '');
    $gender = trim($_POST['gender'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $phone = normalize_phone($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? ''); $password = (string) ($_POST['password'] ?? '');
    if ($name === '' || $ttl === '' || $gender === '' || $email === '' || $phone === '' || $address === '' || strlen($password) < 6) {
        $error = 'Semua data wajib diisi dan password minimal 6 karakter.';
    } elseif (!preg_match('/^[0-9]{10}$/', $nisn)) { $error = 'NISN harus berisi tepat 10 angka.';
    } elseif (!preg_match('/^62[0-9]{8,14}$/', $phone)) { $error = 'Nomor HP tidak valid. Contoh: 08123456789.';
    } else {
        $check = mysqli_prepare($koneksi, 'SELECT id FROM users WHERE email = ? LIMIT 1'); mysqli_stmt_bind_param($check, 's', $email); mysqli_stmt_execute($check);
        if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) { $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi, "INSERT INTO users (name, nisn, ttl, gender, email, phone, password_hash, role, address) VALUES (?, ?, ?, ?, ?, ?, ?, 'user', ?)");
            mysqli_stmt_bind_param($stmt, 'ssssssss', $name, $nisn, $ttl, $gender, $email, $phone, $hash, $address);
            if (mysqli_stmt_execute($stmt)) {
                $userMessage = 'Halo ' . $name . ', akun Ujian ASTS Anda berhasil terdaftar. Silakan login menggunakan email dan password yang Anda daftarkan.';
                $userSent = send_whatsapp($phone, $userMessage);
                $sent = notify_admins('User baru mendaftar: ' . $name . ' (' . $email . ').');
                $message = $sent < 0
                    ? 'Pendaftaran berhasil, tetapi belum ada admin dengan nomor WhatsApp. Buat admin terlebih dahulu melalui halaman setup admin.'
                    : ($sent > 0
                        ? 'Pendaftaran berhasil. Notifikasi WhatsApp sudah dikirim ke admin.'
                        : 'Pendaftaran berhasil, tetapi WhatsApp belum terkirim. Pastikan token Fonnte, nomor HP admin, dan koneksi server benar.');
                $message .= $userSent
                    ? ' Notifikasi konfirmasi juga sudah dikirim ke nomor Anda.'
                    : ' Notifikasi konfirmasi ke nomor Anda belum terkirim.';
                set_flash('success', $message);
                redirect('login.php');
            }
            $error = 'Pendaftaran gagal: ' . mysqli_error($koneksi);
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daftar Akun | Ujian ASTS</title><link rel="stylesheet" href="app.css"></head><body><?php require __DIR__ . '/decor.php'; ?>
<main class="wrap"><div class="card"><h1>Daftar Akun</h1><p class="muted">Admin akan menerima notifikasi dan konfirmasi akan dikirim ke nomor WhatsApp Anda.</p><?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="grid">
<div><label>Nama Lengkap</label><input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></div><div><label>NISN (10 angka)</label><input name="nisn" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" required value="<?= e($_POST['nisn'] ?? '') ?>"></div>
<div><label>Tempat, Tanggal Lahir</label><input name="ttl" required value="<?= e($_POST['ttl'] ?? '') ?>"></div><div><label>Jenis Kelamin</label><select name="gender" required><option value="">-- Pilih --</option><option value="MALE">Laki-laki</option><option value="FEMALE">Perempuan</option></select></div>
<div><label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div><div><label>No. HP / WhatsApp</label><input name="phone" required placeholder="08123456789" value="<?= e($_POST['phone'] ?? '') ?>"></div>
<div><label>Password</label><input type="password" name="password" minlength="6" required></div><div><label>Alamat</label><textarea name="address" rows="3" required><?= e($_POST['address'] ?? '') ?></textarea></div>
</div><div class="actions"><button class="primary">Daftar</button><a class="btn secondary" href="login.php">Kembali ke login</a></div></form></div></main></body></html>

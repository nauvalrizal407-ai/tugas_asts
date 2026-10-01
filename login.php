<?php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['user_id'])) { redirect(($_SESSION['role'] ?? '') === 'admin' ? 'admin.php' : 'profile.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $stmt = mysqli_prepare($koneksi, 'SELECT * FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email); mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id']; $_SESSION['role'] = $user['role'];
        set_flash('success', 'Anda berhasil login.');
        send_whatsapp($user['phone'] ?? '', 'Halo ' . $user['name'] . ', anda berhasil login ke sistem Ujian ASTS.');
        redirect($user['role'] === 'admin' ? 'admin.php' : 'profile.php');
    }
    $error = 'Email atau password salah. Jika akun lama belum memiliki password, minta admin mengaturnya.';
}
$flash = get_flash();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | Ujian ASTS</title><link rel="stylesheet" href="app.css"></head><body><?php require __DIR__ . '/decor.php'; ?>
<main class="wrap narrow"><div class="card"><h1>Login Ujian ASTS</h1><p class="muted">Masuk untuk melihat profil pribadi Anda.</p>
<?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Email</label><input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"><label>Password</label><input type="password" name="password" required autocomplete="current-password"><div class="actions"><button class="primary" type="submit">Login</button><a class="btn secondary" href="register.php">Buat akun</a></div></form>
<p class="muted" style="margin-top:20px;font-size:13px">Admin pertama dapat dibuat melalui <a href="setup_admin.php">setup admin</a> jika belum ada admin.</p>
</div></main></body></html>

<?php
require_once __DIR__ . '/config.php'; require_login();
$user = current_user(); if (!$user) { redirect('logout.php'); }
$flash = get_flash();
function gender_label(string $gender): string { return $gender === 'FEMALE' ? 'Perempuan' : 'Laki-laki'; }
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profil Saya</title><link rel="stylesheet" href="app.css"></head><body><?php require __DIR__ . '/decor.php'; ?>
<nav class="nav"><a class="brand" href="profile.php">Ujian ASTS</a><div><span><?= e($user['name']) ?></span><a href="edit.php?id=<?= (int)$user['id'] ?>">Edit profil</a><a href="logout.php">Logout</a></div></nav>
<main class="wrap"><div class="card"><h1>Profil Pribadi</h1><p class="muted">Data ini hanya dapat dilihat oleh akun Anda.</p><?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="profile-grid"><div class="profile-item"><small>Nama Lengkap</small><?= e($user['name']) ?></div><div class="profile-item"><small>NISN</small><?= e($user['nisn']) ?></div><div class="profile-item"><small>Tempat, Tanggal Lahir</small><?= e($user['ttl']) ?></div><div class="profile-item"><small>Jenis Kelamin</small><?= e(gender_label($user['gender'])) ?></div><div class="profile-item"><small>Email</small><?= e($user['email']) ?></div><div class="profile-item"><small>No. HP / WhatsApp</small><?= e($user['phone']) ?></div><div class="profile-item" style="grid-column:1/-1"><small>Alamat</small><?= nl2br(e($user['address'])) ?></div></div>
</div></main></body></html>

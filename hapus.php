<?php
require_once __DIR__ . '/config.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { set_flash('error','Penghapusan harus dilakukan melalui tombol yang tersedia.'); redirect('admin.php'); }
verify_csrf(); $id=(int)($_POST['id']??0); $me=current_user();
if (!$id || $id===(int)$me['id']) { set_flash('error','Akun admin yang sedang digunakan tidak dapat dihapus.'); redirect('admin.php'); }
$stmt=mysqli_prepare($koneksi,'SELECT name, phone FROM users WHERE id=? LIMIT 1'); mysqli_stmt_bind_param($stmt,'i',$id); mysqli_stmt_execute($stmt); $user=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if($user){ $delete=mysqli_prepare($koneksi,'DELETE FROM users WHERE id=?'); mysqli_stmt_bind_param($delete,'i',$id); if(mysqli_stmt_execute($delete)){ send_whatsapp($user['phone'],'Akun telah di hapus dari sistem Ujian ASTS.'); set_flash('success','Akun telah di hapus.'); } else set_flash('error','Akun gagal dihapus.'); }
else set_flash('error','Akun tidak ditemukan.');
redirect('admin.php');

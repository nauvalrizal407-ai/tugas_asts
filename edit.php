<?php
require_once __DIR__ . '/config.php'; require_login();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0); $me = current_user();
if (!$id || (!$me || ($me['role'] !== 'admin' && (int)$me['id'] !== $id))) { set_flash('error', 'Anda tidak boleh mengedit data tersebut.'); redirect('profile.php'); }
$stmt = mysqli_prepare($koneksi, 'SELECT * FROM users WHERE id = ? LIMIT 1'); mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt); $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$data) { set_flash('error', 'Data tidak ditemukan.'); redirect($me['role'] === 'admin' ? 'admin.php' : 'profile.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name=trim($_POST['name']??''); $nisn=trim($_POST['nisn']??''); $ttl=trim($_POST['ttl']??''); $gender=trim($_POST['gender']??''); $email=strtolower(trim($_POST['email']??'')); $phone=normalize_phone($_POST['phone']??''); $address=trim($_POST['address']??''); $newPassword=(string)($_POST['password']??'');
    if ($name===''||$ttl===''||$gender===''||$email===''||$phone===''||$address==='') $error='Semua data wajib diisi.';
    elseif (!preg_match('/^[0-9]{10}$/',$nisn)) $error='NISN harus berisi tepat 10 angka.';
    elseif (!preg_match('/^62[0-9]{8,14}$/',$phone)) $error='Nomor HP tidak valid.';
    elseif ($newPassword !== '' && strlen($newPassword)<6) $error='Password baru minimal 6 karakter.';
    else {
        $check=mysqli_prepare($koneksi,'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1'); mysqli_stmt_bind_param($check,'si',$email,$id); mysqli_stmt_execute($check);
        if (mysqli_num_rows(mysqli_stmt_get_result($check))>0) $error='Email sudah dipakai akun lain.';
        else {
            $changed=[]; $labels=['name'=>'nama','nisn'=>'NISN','ttl'=>'TTL','gender'=>'jenis kelamin','email'=>'email','phone'=>'no. HP','address'=>'alamat'];
            $incoming=['name'=>$name,'nisn'=>$nisn,'ttl'=>$ttl,'gender'=>$gender,'email'=>$email,'phone'=>$phone,'address'=>$address]; foreach($incoming as $field=>$value) if((string)$data[$field]!==$value) $changed[]=$labels[$field]; if($newPassword!=='') $changed[]='password';
            if ($newPassword !== '') { $hash=password_hash($newPassword,PASSWORD_DEFAULT); $update=mysqli_prepare($koneksi,'UPDATE users SET name=?, nisn=?, ttl=?, gender=?, email=?, phone=?, password_hash=?, address=? WHERE id=?'); mysqli_stmt_bind_param($update,'ssssssssi',$name,$nisn,$ttl,$gender,$email,$phone,$hash,$address,$id); }
            else { $update=mysqli_prepare($koneksi,'UPDATE users SET name=?, nisn=?, ttl=?, gender=?, email=?, phone=?, address=? WHERE id=?'); mysqli_stmt_bind_param($update,'sssssssi',$name,$nisn,$ttl,$gender,$email,$phone,$address,$id); }
            if(mysqli_stmt_execute($update)) { if((int)$me['id']===$id) { $_SESSION['role']=$data['role']; } $who=((int)$me['id']===$id)?'Data anda berhasil di rubah':'Data user berhasil di rubah'; $detail=$changed?implode(', ',$changed):'tidak ada perubahan'; set_flash('success',$who.' ('.$detail.').'); send_whatsapp($phone,$who.' ('.$detail.').'); redirect($me['role']==='admin'?'admin.php':'profile.php'); }
            $error='Gagal menyimpan perubahan: '.mysqli_error($koneksi);
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Profil</title><link rel="stylesheet" href="app.css"></head><body><?php require __DIR__ . '/decor.php'; ?><nav class="nav"><a class="brand" href="<?= $me['role']==='admin'?'admin.php':'profile.php' ?>">Ujian ASTS</a><a href="logout.php">Logout</a></nav><main class="wrap"><div class="card"><h1>Edit <?= $me['role']==='admin' && (int)$me['id']!==$id ? 'User' : 'Profil' ?></h1><?php if($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $id ?>"><div class="grid">
<div><label>Nama Lengkap</label><input name="name" required value="<?= e($data['name']) ?>"></div><div><label>NISN</label><input name="nisn" pattern="[0-9]{10}" maxlength="10" required value="<?= e($data['nisn']) ?>"></div><div><label>Tempat, Tanggal Lahir</label><input name="ttl" required value="<?= e($data['ttl']) ?>"></div><div><label>Jenis Kelamin</label><select name="gender" required><option value="MALE" <?= $data['gender']==='MALE'?'selected':'' ?>>Laki-laki</option><option value="FEMALE" <?= $data['gender']==='FEMALE'?'selected':'' ?>>Perempuan</option></select></div><div><label>Email</label><input type="email" name="email" required value="<?= e($data['email']) ?>"></div><div><label>No. HP / WhatsApp</label><input name="phone" required value="<?= e($data['phone']) ?>"></div><div><label>Password baru <span class="muted" style="font-weight:400">(kosongkan jika tidak diubah)</span></label><input type="password" name="password" minlength="6"></div><div><label>Alamat</label><textarea name="address" rows="4" required><?= e($data['address']) ?></textarea></div>
</div><div class="actions"><button class="primary">Simpan perubahan</button><a class="btn secondary" href="<?= $me['role']==='admin'?'admin.php':'profile.php' ?>">Batal</a></div></form></div></main></body></html>

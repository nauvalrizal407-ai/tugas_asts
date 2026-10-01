<?php
require_once __DIR__ . '/config.php'; require_admin();
$me = current_user(); $flash = get_flash();
$users = mysqli_query($koneksi, 'SELECT * FROM users ORDER BY created_at DESC, id DESC');
$countResult = mysqli_query($koneksi, 'SELECT COUNT(*) AS total FROM users'); $total = (int)mysqli_fetch_assoc($countResult)['total'];
$latest = mysqli_query($koneksi, "SELECT * FROM notifications WHERE type = 'user_new' ORDER BY id DESC LIMIT 5");
$latestNotifications = []; while ($row = mysqli_fetch_assoc($latest)) { $latestNotifications[] = $row; }
$maxNotification = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COALESCE(MAX(id), 0) AS max_id FROM notifications'))['max_id'] ?? 0);
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard Admin</title><link rel="stylesheet" href="app.css"></head><body><?php require __DIR__ . '/decor.php'; ?>
<nav class="nav"><a class="brand" href="admin.php">Ujian ASTS · Admin</a><div><span><?= e($me['name'] ?? 'Admin') ?></span><a href="tambah.php">Tambah user</a><a href="logout.php">Logout</a></div></nav>
<main class="wrap"><div id="liveFlash"></div><?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="top"><div><h1>Dashboard Admin</h1><p class="muted">Kelola seluruh akun dan pantau pendaftar baru.</p></div><div class="card" style="padding:14px 22px"><div class="stat"><?= $total ?></div><div class="muted">Total user</div></div></div>
<?php if ($latestNotifications): ?><div class="card" style="margin-bottom:18px"><h3>Notifikasi user baru</h3><?php foreach($latestNotifications as $notice): ?><p style="margin:8px 0"><span class="badge">Baru</span> <?= e($notice['message']) ?> <small class="muted">(<?= e($notice['created_at']) ?>)</small></p><?php endforeach; ?></div><?php endif; ?>
<div class="card"><input class="search" id="search" placeholder="Cari nama, email, no. HP, NISN..." oninput="filterUsers()"><div class="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>No. HP</th><th>NISN</th><th>Role</th><th>Terdaftar</th><th>Aksi</th></tr></thead><tbody id="userRows">
<?php while ($user = mysqli_fetch_assoc($users)): ?><tr><td><?= e($user['name']) ?></td><td><?= e($user['email']) ?></td><td><?= e($user['phone']) ?></td><td><?= e($user['nisn']) ?></td><td><span class="badge <?= $user['role'] === 'admin' ? 'admin' : '' ?>"><?= e($user['role']) ?></span></td><td><?= e($user['created_at']) ?></td><td><a class="btn secondary" href="edit.php?id=<?= (int)$user['id'] ?>">Edit</a><?php if ((int)$user['id'] !== (int)$me['id']): ?><form style="display:inline" method="post" action="hapus.php" onsubmit="return confirm('Yakin ingin menghapus akun ini?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><button class="danger">Hapus</button></form><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></div></main>
<script>
function filterUsers(){const q=document.getElementById('search').value.toLowerCase();document.querySelectorAll('#userRows tr').forEach(r=>r.style.display=r.innerText.toLowerCase().includes(q)?'':'none');}
let lastNotificationId=<?= $maxNotification ?>;
setInterval(()=>fetch('admin_notifications.php?since='+lastNotificationId).then(r=>r.json()).then(items=>items.forEach(item=>{lastNotificationId=Math.max(lastNotificationId,Number(item.id));const box=document.getElementById('liveFlash');box.innerHTML='<div class="flash success">🔔 '+item.message+'</div>';if('Notification' in window&&Notification.permission==='granted')new Notification('User baru', {body:item.message});})),15000);
if('Notification' in window&&Notification.permission==='default')Notification.requestPermission();
</script></body></html>

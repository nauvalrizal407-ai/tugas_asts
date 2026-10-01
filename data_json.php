<?php
require_once __DIR__ . '/config.php'; require_admin(); header('Content-Type: application/json; charset=utf-8');
$query=mysqli_query($koneksi,'SELECT id,name,nisn,ttl,gender,email,phone,address,role,created_at FROM users ORDER BY id ASC'); $data=[]; while($row=mysqli_fetch_assoc($query)) $data[]=$row; echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

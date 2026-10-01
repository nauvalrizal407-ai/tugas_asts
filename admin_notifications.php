<?php
require_once __DIR__ . '/config.php'; require_admin(); header('Content-Type: application/json; charset=utf-8');
$since = max(0, (int)($_GET['since'] ?? 0)); $stmt = mysqli_prepare($koneksi, "SELECT id, message, created_at FROM notifications WHERE type = 'user_new' AND id > ? ORDER BY id ASC"); mysqli_stmt_bind_param($stmt, 'i', $since); mysqli_stmt_execute($stmt); $result = mysqli_stmt_get_result($stmt); $items = []; while($row = mysqli_fetch_assoc($result)) $items[] = $row; echo json_encode($items, JSON_UNESCAPED_UNICODE);

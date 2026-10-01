<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { redirect('login.php'); }
redirect(($_SESSION['role'] ?? '') === 'admin' ? 'admin.php' : 'profile.php');

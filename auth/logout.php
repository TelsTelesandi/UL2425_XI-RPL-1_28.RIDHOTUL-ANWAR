<?php
require_once '../config/functions.php';

// Hapus semua data session
session_unset();
session_destroy();

// Redirect ke halaman login
redirect('../auth/login.php');
?>

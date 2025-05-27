<?php
require_once 'config/functions.php';

// Redirect berdasarkan role
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('user/index.php');
    }
} else {
    redirect('auth/login.php');
}
?>

<?php
session_start();

// Fungsi untuk memeriksa apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fungsi untuk memeriksa apakah user adalah admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'Admin';
}

// Fungsi untuk redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Fungsi untuk menampilkan pesan alert
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Fungsi untuk menampilkan alert
function showAlert() {
    if (isset($_SESSION['alert'])) {
        $type = $_SESSION['alert']['type'];
        $message = $_SESSION['alert']['message'];
        
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        
        unset($_SESSION['alert']);
    }
}

// Fungsi untuk memformat tanggal
function formatDate($date) {
    $timestamp = strtotime($date);
    return date('d F Y', $timestamp);
}

// Fungsi untuk mendapatkan status dengan warna
function getStatusBadge($status) {
    $badges = [
        'menunggu' => 'warning',
        'disetujui' => 'success',
        'ditolak' => 'danger',
        'selesai' => 'info'
    ];
    
    $color = isset($badges[$status]) ? $badges[$status] : 'secondary';
    return "<span class='badge bg-$color'>$status</span>";
}
?>

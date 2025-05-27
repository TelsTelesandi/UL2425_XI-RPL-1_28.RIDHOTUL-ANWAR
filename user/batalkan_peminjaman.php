<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Cek apakah user sudah login
if (!isLoggedIn() || isAdmin()) {
    redirect('../auth/login.php');
}

// Cek ID peminjaman
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('danger', 'ID peminjaman tidak valid');
    redirect('riwayat.php');
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Cek apakah peminjaman milik user dan statusnya menunggu
$query = "SELECT * FROM peminjaman WHERE peminjaman_id = ? AND user_id = ? AND status = 'menunggu'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    setAlert('danger', 'Peminjaman tidak ditemukan, bukan milik Anda, atau tidak dapat dibatalkan');
    redirect('riwayat.php');
}

$stmt->close();

// Hapus peminjaman
$query_delete = "DELETE FROM peminjaman WHERE peminjaman_id = ? AND user_id = ? AND status = 'menunggu'";
$stmt_delete = $conn->prepare($query_delete);
$stmt_delete->bind_param("ii", $id, $user_id);

if ($stmt_delete->execute()) {
    setAlert('success', 'Peminjaman berhasil dibatalkan');
} else {
    setAlert('danger', 'Gagal membatalkan peminjaman: ' . $conn->error);
}

$stmt_delete->close();
redirect('riwayat.php');
?>

<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Cek parameter
if (!isset($_REQUEST['id']) || !isset($_REQUEST['status'])) {
    setAlert('danger', 'Parameter tidak valid');
    redirect('index.php');
}

$id = $_REQUEST['id'];
$status = $_REQUEST['status'];

// Validasi status
$valid_status = ['disetujui', 'ditolak', 'selesai'];
if (!in_array($status, $valid_status)) {
    setAlert('danger', 'Status tidak valid');
    redirect('index.php');
}

// Ambil data peminjaman
$query = "SELECT * FROM peminjaman WHERE peminjaman_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    setAlert('danger', 'Peminjaman tidak ditemukan');
    redirect('index.php');
}

$peminjaman = $result->fetch_assoc();
$stmt->close();

// Set catatan admin berdasarkan status
$catatan_admin = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['catatan_admin'])) {
    $catatan_admin = $_POST['catatan_admin'];
} else {
    switch ($status) {
        case 'disetujui':
            $catatan_admin = 'Peminjaman telah disetujui oleh admin.';
            break;
        case 'ditolak':
            if (empty($catatan_admin)) {
                setAlert('danger', 'Alasan penolakan harus diisi');
                redirect('index.php');
            }
            break;
        case 'selesai':
            $catatan_admin = 'Peminjaman telah selesai dan sarana telah dikembalikan.';
            break;
    }
}

// Update status peminjaman
if ($status == 'selesai' && $peminjaman['status'] == 'menunggu pengembalian') {
    $tanggal_kembali = date('Y-m-d');
    $query = "UPDATE peminjaman SET status = ?, catatan_admin = ?, tanggal_kembali = ? WHERE peminjaman_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $status, $catatan_admin, $tanggal_kembali, $id);
} else {
    $query = "UPDATE peminjaman SET status = ?, catatan_admin = ? WHERE peminjaman_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $status, $catatan_admin, $id);
}

if ($stmt->execute()) {
    // Jika status disetujui, kurangi jumlah tersedia
    if ($status == 'disetujui') {
        $query_update_sarana = "UPDATE sarana SET jumlah_tersedia = jumlah_tersedia - ? WHERE sarana_id = ?";
        $stmt_update = $conn->prepare($query_update_sarana);
        $stmt_update->bind_param("ii", $peminjaman['jumlah_pinjam'], $peminjaman['sarana_id']);
        $stmt_update->execute();
        $stmt_update->close();
    }
    
    // Jika status selesai, tambahkan jumlah tersedia
    if ($status == 'selesai' && $peminjaman['status'] == 'disetujui') {
        $query_update_sarana = "UPDATE sarana SET jumlah_tersedia = jumlah_tersedia + ? WHERE sarana_id = ?";
        $stmt_update = $conn->prepare($query_update_sarana);
        $stmt_update->bind_param("ii", $peminjaman['jumlah_pinjam'], $peminjaman['sarana_id']);
        $stmt_update->execute();
        $stmt_update->close();
    }
    
    setAlert('success', 'Status peminjaman berhasil diperbarui');
} else {
    setAlert('danger', 'Gagal memperbarui status peminjaman: ' . $conn->error);
}

$stmt->close();
redirect('index.php');
?> 
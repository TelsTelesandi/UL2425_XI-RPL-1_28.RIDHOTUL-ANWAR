<?php
$page_title = "Detail Peminjaman";
require_once '../templates/header.php';

// Cek apakah user adalah user biasa
if (isAdmin()) {
    redirect('../admin/index.php');
}

// Cek ID peminjaman
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('danger', 'ID peminjaman tidak valid');
    redirect('riwayat.php');
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Proses ajukan pengembalian
if (isset($_GET['action']) && $_GET['action'] == 'pengembalian') {
    $query = "UPDATE peminjaman SET status = 'menunggu pengembalian', catatan_admin = 'Pengguna mengajukan pengembalian sarana.' WHERE peminjaman_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id, $user_id);
    
    if ($stmt->execute()) {
        setAlert('success', 'Pengajuan pengembalian berhasil dikirim');
    } else {
        setAlert('danger', 'Gagal mengajukan pengembalian: ' . $conn->error);
    }
    
    $stmt->close();
    redirect('detail_peminjaman.php?id=' . $id);
}

// Ambil data peminjaman
$query = "SELECT p.*, s.nama_sarana, s.lokasi 
          FROM peminjaman p 
          JOIN sarana s ON p.sarana_id = s.sarana_id 
          WHERE p.peminjaman_id = ? AND p.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    setAlert('danger', 'Peminjaman tidak ditemukan atau Anda tidak memiliki akses');
    redirect('riwayat.php');
}

$peminjaman = $result->fetch_assoc();
$stmt->close();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detail Peminjaman</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="fw-bold">Informasi Sarana</h6>
                <table class="table table-borderless">
                    <tr>
                        <td width="40%">Nama Sarana</td>
                        <td>: <?php echo $peminjaman['nama_sarana']; ?></td>
                    </tr>
                    <tr>
                        <td>Lokasi</td>
                        <td>: <?php echo $peminjaman['lokasi']; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold">Detail Peminjaman</h6>
                <table class="table table-borderless">
                    <tr>
                        <td width="40%">Tanggal Pinjam</td>
                        <td>: <?php echo $peminjaman['tanggal_pinjam']; ?></td>
                    </tr>
                    <tr>
                        <td>Tanggal Kembali</td>
                        <td>: <?php echo $peminjaman['tanggal_kembali']; ?></td>
                    </tr>
                    <tr>
                        <td>Jumlah</td>
                        <td>: <?php echo $peminjaman['jumlah_pinjam']; ?></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>: <?php echo getStatusBadge($peminjaman['status']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="riwayat.php" class="btn btn-secondary">Kembali</a>
            
            <?php if ($peminjaman['status'] == 'menunggu'): ?>
                <a href="batalkan_peminjaman.php?id=<?php echo $peminjaman['peminjaman_id']; ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin membatalkan peminjaman ini?')">
                    Batalkan Peminjaman
                </a>
            <?php elseif ($peminjaman['status'] == 'disetujui'): ?>
                <a href="detail_peminjaman.php?id=<?php echo $peminjaman['peminjaman_id']; ?>&action=pengembalian" class="btn btn-primary" onclick="return confirm('Apakah Anda yakin ingin mengajukan pengembalian sarana ini?')">
                    <i class="bi bi-box-arrow-left me-2"></i>Ajukan Pengembalian
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>

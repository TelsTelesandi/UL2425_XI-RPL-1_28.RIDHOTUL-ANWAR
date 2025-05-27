<?php
$page_title = "Detail Peminjaman";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Cek ID peminjaman
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('danger', 'ID peminjaman tidak valid');
    redirect('peminjaman.php');
}

$id = $_GET['id'];

// Ambil data peminjaman
$query = "SELECT p.*, u.nama_lengkap, u.jenis_pengguna, s.nama_sarana, s.lokasi 
          FROM peminjaman p 
          JOIN users u ON p.user_id = u.user_id 
          JOIN sarana s ON p.sarana_id = s.sarana_id 
          WHERE p.peminjaman_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    setAlert('danger', 'Peminjaman tidak ditemukan');
    redirect('peminjaman.php');
}

$peminjaman = $result->fetch_assoc();
$stmt->close();

// Proses update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['action'])) {
    $status = '';
    $catatan_admin = '';
    
    // Handle direct action from URL
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'approve':
                $status = 'disetujui';
                $catatan_admin = 'Peminjaman telah disetujui oleh admin.';
                break;
            case 'reject':
                $status = 'ditolak';
                $catatan_admin = 'Peminjaman ditolak oleh admin.';
                break;
            case 'complete':
                $status = 'selesai';
                $catatan_admin = 'Peminjaman telah selesai dan sarana telah dikembalikan.';
                break;
        }
    } else {
        // Handle form submission
        $status = $_POST['status'];
        $catatan_admin = $_POST['catatan_admin'];
    }
    
    if (!empty($status)) {
        // Update status peminjaman
        $query = "UPDATE peminjaman SET status = ?, catatan_admin = ? WHERE peminjaman_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $status, $catatan_admin, $id);
        
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
            redirect('peminjaman.php');
        } else {
            setAlert('danger', 'Gagal memperbarui status peminjaman: ' . $conn->error);
        }
        
        $stmt->close();
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detail Peminjaman</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="fw-bold">Informasi Peminjam</h6>
                <table class="table table-borderless">
                    <tr>
                        <td width="40%">Nama</td>
                        <td>: <?php echo $peminjaman['nama_lengkap']; ?></td>
                    </tr>
                    <tr>
                        <td>Jenis Pengguna</td>
                        <td>: <?php echo $peminjaman['jenis_pengguna']; ?></td>
                    </tr>
                </table>
            </div>
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
        </div>
        
        <div class="row mb-4">
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
            <div class="col-md-6">
                <h6 class="fw-bold">Catatan Admin</h6>
                <p><?php echo !empty($peminjaman['catatan_admin']) ? $peminjaman['catatan_admin'] : 'Tidak ada catatan'; ?></p>
            </div>
        </div>
        
        
            <div class="d-flex justify-content-between">
                <a href="peminjaman.php" class="btn btn-secondary">Kembali</a>
            </div>
        
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>

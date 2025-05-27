<?php
$page_title = "Riwayat Peminjaman";
require_once '../templates/header.php';

// Cek apakah user adalah user biasa
if (isAdmin()) {
    redirect('../admin/index.php');
}

// Filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$user_id = $_SESSION['user_id'];

// Query dasar
$query = "SELECT p.*, s.nama_sarana 
          FROM peminjaman p 
          JOIN sarana s ON p.sarana_id = s.sarana_id 
          WHERE p.user_id = ?";

// Tambahkan filter jika ada
if (!empty($status_filter)) {
    $query .= " AND p.status = ?";
}

$query .= " ORDER BY p.peminjaman_id DESC";
$stmt = $conn->prepare($query);

if (!empty($status_filter)) {
    $stmt->bind_param("is", $user_id, $status_filter);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Riwayat Peminjaman</h5>
            <div>
                <div class="btn-group" role="group">
                    <a href="riwayat.php" class="btn btn-outline-primary <?php echo empty($status_filter) ? 'active' : ''; ?>">Semua</a>
                    <a href="riwayat.php?status=menunggu" class="btn btn-outline-warning <?php echo $status_filter == 'menunggu' ? 'active' : ''; ?>">Menunggu</a>
                    <a href="riwayat.php?status=menunggu pengembalian" class="btn btn-outline-warning <?php echo $status_filter == 'menunggu pengembalian' ? 'active' : ''; ?>">Menunggu Pengembalian</a>
                    <a href="riwayat.php?status=disetujui" class="btn btn-outline-success <?php echo $status_filter == 'disetujui' ? 'active' : ''; ?>">Disetujui</a>
                    <a href="riwayat.php?status=ditolak" class="btn btn-outline-danger <?php echo $status_filter == 'ditolak' ? 'active' : ''; ?>">Ditolak</a>
                    <a href="riwayat.php?status=selesai" class="btn btn-outline-info <?php echo $status_filter == 'selesai' ? 'active' : ''; ?>">Selesai</a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sarana</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['peminjaman_id']; ?></td>
                                <td><?php echo $row['nama_sarana']; ?></td>
                                <td><?php echo $row['tanggal_pinjam']; ?></td>
                                <td><?php echo $row['tanggal_kembali']; ?></td>
                                <td><?php echo $row['jumlah_pinjam']; ?></td>
                                <td><?php echo getStatusBadge($row['status']); ?></td>
                                <td>
                                    <a href="detail_peminjaman.php?id=<?php echo $row['peminjaman_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data peminjaman</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>

<?php
$page_title = "Kelola Peminjaman";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Query dasar
$query = "SELECT p.*, u.nama_lengkap, s.nama_sarana 
          FROM peminjaman p 
          JOIN users u ON p.user_id = u.user_id 
          JOIN sarana s ON p.sarana_id = s.sarana_id";

// Tambahkan filter jika ada
if (!empty($status_filter)) {
    $query .= " WHERE p.status = '$status_filter'";
}

$query .= " ORDER BY p.peminjaman_id DESC";
$result = $conn->query($query);
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Peminjaman</h5>
            <div>
                <div class="btn-group" role="group">
                    <a href="peminjaman.php" class="btn btn-outline-primary <?php echo empty($status_filter) ? 'active' : ''; ?>">Semua</a>
                    <a href="peminjaman.php?status=menunggu" class="btn btn-outline-warning <?php echo $status_filter == 'menunggu' ? 'active' : ''; ?>">Menunggu</a>
                    <a href="peminjaman.php?status=menunggu pengembalian" class="btn btn-outline-warning <?php echo $status_filter == 'menunggu pengembalian' ? 'active' : ''; ?>">Menunggu Pengembalian</a>
                    <a href="peminjaman.php?status=disetujui" class="btn btn-outline-success <?php echo $status_filter == 'disetujui' ? 'active' : ''; ?>">Disetujui</a>
                    <a href="peminjaman.php?status=ditolak" class="btn btn-outline-danger <?php echo $status_filter == 'ditolak' ? 'active' : ''; ?>">Ditolak</a>
                    <a href="peminjaman.php?status=selesai" class="btn btn-outline-info <?php echo $status_filter == 'selesai' ? 'active' : ''; ?>">Selesai</a>
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
                        <th>Peminjam</th>
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
                        <?php $row_number = 1; // Initialize row number counter ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row_number++; // Display row number and increment ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo $row['nama_sarana']; ?></td>
                                <td><?php echo $row['tanggal_pinjam']; ?></td>
                                <td><?php echo $row['tanggal_kembali']; ?></td>
                                <td><?php echo $row['jumlah_pinjam']; ?></td>
                                <td><?php echo getStatusBadge($row['status']); ?></td>
                                <td>
                                    <a href="detail_peminjaman.php?id=<?php echo $row['peminjaman_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data peminjaman</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>

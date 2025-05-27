<?php
$page_title = "Laporan";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Filter tanggal
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Query untuk laporan
$query = "SELECT p.*, u.nama_lengkap, u.jenis_pengguna, s.nama_sarana 
          FROM peminjaman p 
          JOIN users u ON p.user_id = u.user_id 
          JOIN sarana s ON p.sarana_id = s.sarana_id 
          WHERE p.tanggal_pinjam BETWEEN ? AND ?";

// Tambahkan filter status jika ada
if (!empty($status)) {
    $query .= " AND p.status = ?";
}

$query .= " ORDER BY p.peminjaman_id DESC";
$stmt = $conn->prepare($query);

if (!empty($status)) {
    $stmt->bind_param("sss", $tanggal_awal, $tanggal_akhir, $status);
} else {
    $stmt->bind_param("ss", $tanggal_awal, $tanggal_akhir);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filter Laporan</h5>
    </div>
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-4">
                <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" value="<?php echo $tanggal_awal; ?>">
            </div>
            <div class="col-md-4">
                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua Status</option>
                    <option value="disetujui" <?php echo ($status == 'disetujui') ? 'selected' : ''; ?>>Disetujui</option>
                    <option value="selesai" <?php echo ($status == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="cetak_laporan.php?tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>&status=<?php echo $status; ?>" class="btn btn-success" target="_blank">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Laporan Peminjaman</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Peminjam</th>
                        <th>Jenis</th>
                        <th>Sarana</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['peminjaman_id']; ?></td>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo $row['jenis_pengguna']; ?></td>
                                <td><?php echo $row['nama_sarana']; ?></td>
                                <td><?php echo $row['tanggal_pinjam']; ?></td>
                                <td><?php echo $row['tanggal_kembali']; ?></td>
                                <td><?php echo $row['jumlah_pinjam']; ?></td>
                                <td><?php echo getStatusBadge($row['status']); ?></td>
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

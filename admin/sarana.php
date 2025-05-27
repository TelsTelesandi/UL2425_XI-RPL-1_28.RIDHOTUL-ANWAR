<?php
$page_title = "Kelola Sarana";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Proses hapus sarana
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Cek apakah sarana sedang dipinjam
    $check_query = "SELECT COUNT(*) as count FROM peminjaman WHERE sarana_id = ? AND status IN ('menunggu', 'disetujui')";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        setAlert('danger', 'Sarana tidak dapat dihapus karena sedang dipinjam');
    } else {
        // Hapus sarana
        $delete_query = "DELETE FROM sarana WHERE sarana_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            setAlert('success', 'Sarana berhasil dihapus');
        } else {
            setAlert('danger', 'Gagal menghapus sarana: ' . $conn->error);
        }
        
        $delete_stmt->close();
    }
    
    $check_stmt->close();
    redirect('sarana.php');
}

// Ambil data sarana
$query = "SELECT * FROM sarana ORDER BY sarana_id DESC";
$result = $conn->query($query);
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Daftar Sarana</h5>
                <p class="text-muted mb-0">Kelola semua sarana dan prasarana yang tersedia</p>
            </div>
            <a href="tambah_sarana.php" class="btn btn-primary-custom">
                <i class="bi bi-plus-circle me-2"></i> Tambah Sarana
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-custom">
            <div class="card-body-custom p-0">
                <div class="table-responsive">
                    <table class="table table-custom datatable mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Sarana</th>
                                <th>Jumlah Tersedia</th>
                                <th>Lokasi</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['sarana_id']; ?></td>
                                        <td>
                                            <?php 
                                            // Tentukan ikon berdasarkan nama sarana
                                            $icon = 'bi-box-seam';
                                            if (stripos($row['nama_sarana'], 'proyektor') !== false) {
                                                $icon = 'bi-projector';
                                            } elseif (stripos($row['nama_sarana'], 'laptop') !== false) {
                                                $icon = 'bi-laptop';
                                            } elseif (stripos($row['nama_sarana'], 'kamera') !== false) {
                                                $icon = 'bi-camera';
                                            } elseif (stripos($row['nama_sarana'], 'microphone') !== false || stripos($row['nama_sarana'], 'mic') !== false) {
                                                $icon = 'bi-mic';
                                            } elseif (stripos($row['nama_sarana'], 'sound') !== false || stripos($row['nama_sarana'], 'speaker') !== false) {
                                                $icon = 'bi-speaker';
                                            }
                                            ?>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2 p-2 rounded-circle bg-light">
                                                    <i class="bi <?php echo $icon; ?> text-primary"></i>
                                                </div>
                                                <?php echo $row['nama_sarana']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-custom <?php echo $row['jumlah_tersedia'] > 0 ? 'badge-approved' : 'badge-rejected'; ?>">
                                                <?php echo $row['jumlah_tersedia']; ?> unit
                                            </span>
                                        </td>
                                        <td><?php echo $row['lokasi']; ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($row['keterangan'])) {
                                                echo substr($row['keterangan'], 0, 50) . (strlen($row['keterangan']) > 50 ? '...' : '');
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="edit_sarana.php?id=<?php echo $row['sarana_id']; ?>" class="btn btn-icon btn-outline-custom me-2" data-bs-toggle="tooltip" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="sarana.php?action=delete&id=<?php echo $row['sarana_id']; ?>" class="btn btn-icon btn-outline-custom btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus sarana ini?')" data-bs-toggle="tooltip" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">Tidak ada data sarana</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>

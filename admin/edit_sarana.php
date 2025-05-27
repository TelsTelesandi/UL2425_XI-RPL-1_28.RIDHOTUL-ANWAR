<?php
$page_title = "Edit Sarana";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Cek ID sarana
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('danger', 'ID sarana tidak valid');
    redirect('sarana.php');
}

$id = $_GET['id'];

// Ambil data sarana
$query = "SELECT * FROM sarana WHERE sarana_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    setAlert('danger', 'Sarana tidak ditemukan');
    redirect('sarana.php');
}

$sarana = $result->fetch_assoc();
$stmt->close();

// Proses edit sarana
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_sarana = $_POST['nama_sarana'];
    $jumlah_tersedia = $_POST['jumlah_tersedia'];
    $lokasi = $_POST['lokasi'];
    $keterangan = $_POST['keterangan'];
    
    // Validasi input
    if (empty($nama_sarana) || empty($jumlah_tersedia)) {
        setAlert('danger', 'Nama sarana dan jumlah tersedia harus diisi');
    } else {
        // Update ke database
        $query = "UPDATE sarana SET nama_sarana = ?, jumlah_tersedia = ?, lokasi = ?, keterangan = ? WHERE sarana_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sissi", $nama_sarana, $jumlah_tersedia, $lokasi, $keterangan, $id);
        
        if ($stmt->execute()) {
            setAlert('success', 'Sarana berhasil diperbarui');
            redirect('sarana.php');
        } else {
            setAlert('danger', 'Gagal memperbarui sarana: ' . $conn->error);
        }
        
        $stmt->close();
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit Sarana</h5>
    </div>
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label for="nama_sarana" class="form-label">Nama Sarana <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_sarana" name="nama_sarana" value="<?php echo $sarana['nama_sarana']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="jumlah_tersedia" class="form-label">Jumlah Tersedia <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="jumlah_tersedia" name="jumlah_tersedia" min="0" value="<?php echo $sarana['jumlah_tersedia']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="lokasi" class="form-label">Lokasi</label>
                <input type="text" class="form-control" id="lokasi" name="lokasi" value="<?php echo $sarana['lokasi']; ?>">
            </div>
            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?php echo $sarana['keterangan']; ?></textarea>
            </div>
            <div class="d-flex justify-content-between">
                <a href="sarana.php" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>

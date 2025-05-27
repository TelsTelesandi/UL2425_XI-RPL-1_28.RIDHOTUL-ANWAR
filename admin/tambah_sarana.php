<?php
$page_title = "Tambah Sarana";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Proses tambah sarana
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_sarana = $_POST['nama_sarana'];
    $jumlah_tersedia = $_POST['jumlah_tersedia'];
    $lokasi = $_POST['lokasi'];
    $keterangan = $_POST['keterangan'];
    
    // Validasi input
    if (empty($nama_sarana) || empty($jumlah_tersedia)) {
        setAlert('danger', 'Nama sarana dan jumlah tersedia harus diisi');
    } else {
        // Simpan ke database
        $query = "INSERT INTO sarana (nama_sarana, jumlah_tersedia, lokasi, keterangan) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siss", $nama_sarana, $jumlah_tersedia, $lokasi, $keterangan);
        
        if ($stmt->execute()) {
            setAlert('success', 'Sarana berhasil ditambahkan');
            redirect('sarana.php');
        } else {
            setAlert('danger', 'Gagal menambahkan sarana: ' . $conn->error);
        }
        
        $stmt->close();
    }
}
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card card-custom">
            <div class="card-header-custom">
                <h5 class="mb-0">Tambah Sarana Baru</h5>
            </div>
            <div class="card-body-custom">
                <form method="post" action="" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="nama_sarana" class="form-label-custom">
                            <i class="bi bi-box-seam me-2"></i>Nama Sarana <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-custom" id="nama_sarana" name="nama_sarana" placeholder="Masukkan nama sarana" required>
                        <div class="invalid-feedback">
                            Nama sarana harus diisi
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="jumlah_tersedia" class="form-label-custom">
                            <i class="bi bi-123 me-2"></i>Jumlah Tersedia <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control form-control-custom" id="jumlah_tersedia" name="jumlah_tersedia" min="0" placeholder="Masukkan jumlah tersedia" required>
                        <div class="invalid-feedback">
                            Jumlah tersedia harus diisi
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="lokasi" class="form-label-custom">
                            <i class="bi bi-geo-alt me-2"></i>Lokasi
                        </label>
                        <input type="text" class="form-control form-control-custom" id="lokasi" name="lokasi" placeholder="Masukkan lokasi penyimpanan">
                    </div>
                    
                    <div class="mb-4">
                        <label for="keterangan" class="form-label-custom">
                            <i class="bi bi-card-text me-2"></i>Keterangan
                        </label>
                        <textarea class="form-control form-control-custom" id="keterangan" name="keterangan" rows="3" placeholder="Masukkan keterangan tambahan"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="sarana.php" class="btn btn-outline-custom">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="bi bi-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (function () {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

<?php require_once '../templates/footer.php'; ?>

<?php
$page_title = "Tambah User";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Proses form tambah user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_card = $_POST['id_card'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $jenis_pengguna = $_POST['jenis_pengguna'];
    
    // Validasi input
    if (empty($id_card) || empty($nama_lengkap) || empty($username) || empty($password) || empty($role) || empty($jenis_pengguna)) {
        setAlert('danger', 'Semua field harus diisi');
    } else {
        // Cek apakah username atau ID Card sudah ada
        $query_check = "SELECT COUNT(*) as count FROM users WHERE username = ? OR id_card = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("ss", $username, $id_card);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($row_check['count'] > 0) {
            setAlert('danger', 'Username atau ID Card sudah terdaftar.');
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user ke database
            $query_insert = "INSERT INTO users (id_card, nama_lengkap, username, password, role, jenis_pengguna) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($query_insert);
            $stmt_insert->bind_param("ssssss", $id_card, $nama_lengkap, $username, $hashed_password, $role, $jenis_pengguna);
            
            if ($stmt_insert->execute()) {
                setAlert('success', 'User berhasil ditambahkan');
                redirect('users.php');
            } else {
                setAlert('danger', 'Gagal menambahkan user: ' . $conn->error);
            }
            
            $stmt_insert->close();
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card card-custom" style="padding: 2rem 1.5rem;">
            <div class="card-header-custom" style="padding: 2rem 1.5rem 1rem 1.5rem;">
                <h5 class="mb-0">Tambah User Baru</h5>
                <p class="text-muted mb-0">Isi form berikut untuk menambahkan user baru ke sistem.</p>
            </div>
            <div class="card-body-custom" style="padding: 2rem 1.5rem;">
                <form method="post" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="id_card" class="form-label">ID Card</label>
                        <input type="text" class="form-control" id="id_card" name="id_card" required>
                        <div class="invalid-feedback">ID Card harus diisi.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        <div class="invalid-feedback">Nama lengkap harus diisi.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">Username harus diisi.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">Password harus diisi.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                        <div class="invalid-feedback">Role harus dipilih.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="jenis_pengguna" class="form-label">Jenis Pengguna</label>
                        <select class="form-select" id="jenis_pengguna" name="jenis_pengguna" required>
                             <option value="">Pilih Jenis Pengguna</option>
                            <option value="siswa">Siswa/i</option>
                            <option value="guru">Guru</option>
                        </select>
                        <div class="invalid-feedback">Jenis pengguna harus dipilih.</div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                         <a href="users.php" class="btn btn-outline-custom btn-lg">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i> Tambah User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap form validation
(function () {
  'use strict'

  var forms = document.querySelectorAll('.needs-validation')

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
<?php
$page_title = "Edit User";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Cek apakah ada ID user
if (!isset($_GET['id'])) {
    setAlert('danger', 'ID User tidak ditemukan');
    redirect('users.php');
}

$user_id = $_GET['id'];

// Ambil data user
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    setAlert('danger', 'User tidak ditemukan');
    redirect('users.php');
}

$user = $result->fetch_assoc();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit User</h5>
                <a href="users.php" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
            <div class="card-body-custom">
                <form action="update_user.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">ID Card</label>
                                <input type="text" class="form-control" name="id_card" value="<?php echo $user['id_card']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama_lengkap" value="<?php echo $user['nama_lengkap']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                                <small class="text-muted">Kosongkan field ini jika tidak ingin mengubah password</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="Admin" <?php echo $user['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Jenis Pengguna</label>
                                <select class="form-select" name="jenis_pengguna" required>
                                    <option value="siswa" <?php echo $user['jenis_pengguna'] == 'siswa' ? 'selected' : ''; ?>>Siswa/i</option>
                                    <option value="guru" <?php echo $user['jenis_pengguna'] == 'guru' ? 'selected' : ''; ?>>Guru</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mt-4">
                        <a href="users.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 
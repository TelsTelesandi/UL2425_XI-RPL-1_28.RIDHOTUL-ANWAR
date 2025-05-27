<?php
$page_title = "Kelola User";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Process delete user (using action and id parameters)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Check if the user to be deleted is not an admin
    $query = "SELECT role FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user['role'] == 'admin') {
        setAlert('danger', 'Tidak dapat menghapus user admin');
    } else {
        // Delete user
        $query = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            setAlert('success', 'User berhasil dihapus');
        } else {
            setAlert('danger', 'Gagal menghapus user: ' . $conn->error);
        }
    }
    
    redirect('users.php');
}

// Fetch user data
$query = "SELECT * FROM users ORDER BY user_id DESC";
$result = $conn->query($query);
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Daftar User</h5>
                <p class="text-muted mb-0">Kelola semua user sistem</p>
            </div>
            <a href="add_user.php" class="btn btn-primary-custom">
                <i class="bi bi-plus-circle me-2"></i> Tambah User
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
                                <th width="5%">No</th>
                                <th width="15%">ID Card</th>
                                <th width="15%">Username</th>
                                <th width="20%">Nama Lengkap</th>
                                <th width="15%">Role</th>
                                <th width="15%">Jenis Pengguna</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['id_card']; ?></td>
                                    <td><?php echo $row['username']; ?></td>
                                    <td><?php echo $row['nama_lengkap']; ?></td>
                                    <td>
                                        <span class="badge-custom <?php echo $row['role'] == 'admin' ? 'badge-approved' : 'badge-waiting'; ?>">
                                            <?php echo ucfirst($row['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst($row['jenis_pengguna']); ?></td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-icon btn-outline-custom me-2" data-bs-toggle="tooltip" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($row['role'] != 'admin'): ?>
                                                <a href="users.php?action=delete&id=<?php echo $row['user_id']; ?>" class="btn btn-icon btn-outline-custom btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')" data-bs-toggle="tooltip" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 
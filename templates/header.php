<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Dapatkan inisial nama untuk avatar
function getInitials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        return strtoupper(substr($name, 0, 2));
    }
}

$user_initials = getInitials($_SESSION['nama_lengkap']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>SARPRAS'S</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-building me-2"></i>SARPRAS'S
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (isAdmin()): ?>
                        <!-- Menu Admin -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="../admin/index.php">
                                 Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'sarana.php') ? 'active' : ''; ?>" href="../admin/sarana.php">
                                 Kelola Sarana
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" href="../admin/users.php">
                                 Kelola User
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'peminjaman.php') ? 'active' : ''; ?>" href="../admin/peminjaman.php">
                                 Peminjaman
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'laporan.php') ? 'active' : ''; ?>" href="../admin/laporan.php">
                                </i> Laporan
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Menu User -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="../user/index.php">
                                 Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'pinjam.php') ? 'active' : ''; ?>" href="../user/pinjam.php">
                                 Peminjaman
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'riwayat.php') ? 'active' : ''; ?>" href="../user/riwayat.php">
                                Riwayat Peminjaman
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-custom d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="" aria-expanded="false">
                            <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                <?php echo $user_initials; ?>
                            </div>
                            <span class="ms-2 d-none d-md-inline"><?php echo $_SESSION['nama_lengkap']; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="">
                        </ul>
                    </div>
                    <a href="../auth/logout.php" class="btn btn-outline-danger ms-3 d-none d-md-inline"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 fw-bold"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
            </div>
            
            <?php showAlert(); ?>

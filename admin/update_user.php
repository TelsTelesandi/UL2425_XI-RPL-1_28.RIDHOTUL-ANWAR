<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $id_card = $_POST['id_card'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $jenis_pengguna = $_POST['jenis_pengguna'];
    
    // Cek apakah username sudah ada (kecuali untuk user yang sedang diedit)
    $query = "SELECT * FROM users WHERE username = ? AND user_id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        setAlert('danger', 'Username sudah digunakan');
        redirect('users.php');
    }
    
    // Cek apakah ID Card sudah ada (kecuali untuk user yang sedang diedit)
    $query = "SELECT * FROM users WHERE id_card = ? AND user_id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $id_card, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        setAlert('danger', 'ID Card sudah digunakan');
        redirect('users.php');
    }
    
    // Update user
    if (!empty($_POST['password'])) {
        // Jika password diisi, update dengan password baru
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "UPDATE users SET id_card = ?, nama_lengkap = ?, username = ?, password = ?, role = ?, jenis_pengguna = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $id_card, $nama_lengkap, $username, $password, $role, $jenis_pengguna, $user_id);
    } else {
        // Jika password kosong, update tanpa password
        $query = "UPDATE users SET id_card = ?, nama_lengkap = ?, username = ?, role = ?, jenis_pengguna = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $id_card, $nama_lengkap, $username, $role, $jenis_pengguna, $user_id);
    }
    
    if ($stmt->execute()) {
        setAlert('success', 'User berhasil diperbarui');
    } else {
        setAlert('danger', 'Gagal memperbarui user');
    }
    
    redirect('users.php');
}
?> 
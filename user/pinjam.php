<?php
$page_title = "Pinjam Sarana";
require_once '../templates/header.php';

// Cek apakah user adalah user biasa
if (isAdmin()) {
    redirect('../admin/index.php');
}

// Ambil data sarana yang tersedia
$query = "SELECT * FROM sarana WHERE jumlah_tersedia > 0 ORDER BY nama_sarana";
$result = $conn->query($query);

// Process form peminjaman (Cart submission)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_data'])) {
    $cart_data_json = $_POST['cart_data'];
    $borrowingCart = json_decode($cart_data_json, true);
    $user_id = $_SESSION['user_id'];
    
    if (empty($borrowingCart)) {
        setAlert('danger', 'Keranjang peminjaman kosong.');
    } else {
        // Start transaction
        $conn->begin_transaction();
        $success = true;
        $error_message = '';

        foreach ($borrowingCart as $item) {
            // Validate item data structure and values
            if (!isset($item['sarana_id'], $item['jumlah_pinjam'], $item['tanggal_pinjam'], $item['tanggal_kembali'])) {
                $success = false;
                $error_message = 'Data item keranjang tidak valid.';
                break;
            }

            $sarana_id = $item['sarana_id'];
            $jumlah_pinjam = $item['jumlah_pinjam'];
            $tanggal_pinjam = $item['tanggal_pinjam'];
            $tanggal_kembali = $item['tanggal_kembali'];

            // Basic validation
            if ($jumlah_pinjam <= 0 || empty($tanggal_pinjam) || empty($tanggal_kembali) || strtotime($tanggal_pinjam) > strtotime($tanggal_kembali)) {
                 $success = false;
                 $error_message = 'Data peminjaman tidak lengkap atau tanggal tidak valid.';
                 break;
            }

            // Check sarana availability and existence
            $query_check_sarana = "SELECT jumlah_tersedia FROM sarana WHERE sarana_id = ? FOR UPDATE"; // Lock the row
            $stmt_check_sarana = $conn->prepare($query_check_sarana);
            $stmt_check_sarana->bind_param("i", $sarana_id);
            $stmt_check_sarana->execute();
            $result_check_sarana = $stmt_check_sarana->get_result();
            $sarana = $result_check_sarana->fetch_assoc();
            $stmt_check_sarana->close();

            if (!$sarana) {
                $success = false;
                $error_message = 'Sarana tidak ditemukan.';
                break;
            }
        
        if ($jumlah_pinjam > $sarana['jumlah_tersedia']) {
                $success = false;
                $error_message = 'Jumlah pinjam untuk beberapa sarana melebihi stok tersedia.';
                break;
            }
            
            // TODO: Add more advanced validation (e.g., check for overlapping borrowings for the same user and sarana)

            // Insert into peminjaman table
            $query_insert = "INSERT INTO peminjaman (user_id, sarana_id, tanggal_pinjam, tanggal_kembali, jumlah_pinjam, status) VALUES (?, ?, ?, ?, ?, 'menunggu')";
            $stmt_insert = $conn->prepare($query_insert);
            $stmt_insert->bind_param("iissi", $user_id, $sarana_id, $tanggal_pinjam, $tanggal_kembali, $jumlah_pinjam);
            
            if (!$stmt_insert->execute()) {
                $success = false;
                $error_message = 'Gagal menyimpan data peminjaman.' . $conn->error;
                $stmt_insert->close();
                break;
            }
            $stmt_insert->close();

            // Update sarana quantity
            $query_update_sarana = "UPDATE sarana SET jumlah_tersedia = jumlah_tersedia - ? WHERE sarana_id = ?";
            $stmt_update_sarana = $conn->prepare($query_update_sarana);
            $stmt_update_sarana->bind_param("ii", $jumlah_pinjam, $sarana_id);

            if (!$stmt_update_sarana->execute()) {
                 $success = false;
                 $error_message = 'Gagal mengurangi stok sarana.' . $conn->error;
                 $stmt_update_sarana->close();
                 break;
            }
            $stmt_update_sarana->close();
        }

        if ($success) {
            $conn->commit();
            setAlert('success', 'Permintaan peminjaman berhasil diajukan. Silakan tunggu persetujuan admin.');
            redirect('riwayat.php');
        } else {
            $conn->rollback();
            setAlert('danger', 'Gagal mengajukan peminjaman: ' . $error_message);
             // Redirect back to the form or display error on the same page
        }
    }
}

// Ambil data sarana yang tersedia
$query = "SELECT * FROM sarana WHERE jumlah_tersedia > 0 ORDER BY nama_sarana";
$result = $conn->query($query);
?>

<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card card-custom" style="padding: 2rem 1.5rem;">
            <div class="card-header-custom" style="padding: 2rem 1.5rem 1rem 1.5rem;">
                <h5 class="mb-0">Ajukan Peminjaman Sarana</h5>
                <p class="text-muted mb-0">Pilih sarana dan jumlah yang ingin dipinjam, lalu tambahkan ke keranjang.</p>
            </div>
            <div class="card-body-custom" style="padding: 2rem 1.5rem;">
                <div id="add-item-form">
                    <div class="mb-4">
                        <label for="sarana_id" class="form-label-custom mb-2">
                            Pilih Sarana <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg form-select-custom" id="sarana_id" name="sarana_id" required>
                            <option value="">Pilih Sarana</option>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['sarana_id']; ?>" data-nama="<?php echo $row['nama_sarana']; ?>" data-jumlah="<?php echo $row['jumlah_tersedia']; ?>" data-lokasi="<?php echo $row['lokasi']; ?>">
                                        <?php echo $row['nama_sarana']; ?> (Tersedia: <?php echo $row['jumlah_tersedia']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">
                            Silakan pilih sarana yang akan dipinjam
                        </div>
                    </div>
                    
                    <div id="sarana-info" class="mb-4 p-3 rounded bg-light d-none">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <h6 class="mb-0">Informasi Sarana</h6>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Lokasi:</strong> <span id="lokasi-info">-</span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Tersedia:</strong> <span id="tersedia-info">-</span> unit</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="jumlah_pinjam" class="form-label-custom mb-2">
                            Jumlah Pinjam <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control form-control-lg form-control-custom" id="jumlah_pinjam" name="jumlah_pinjam" min="1" value="1" placeholder="Masukkan jumlah yang akan dipinjam" required>
                        <div class="invalid-feedback">
                            Jumlah pinjam harus diisi dan minimal 1
                        </div>
                        <div id="jumlah_tersedia_text" class="form-text"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="tanggal_pinjam" class="form-label-custom mb-2">
                                    Tanggal Pinjam <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-lg form-control-custom" id="tanggal_pinjam" name="tanggal_pinjam" min="<?php echo date('Y-m-d'); ?>" required>
                                <div class="invalid-feedback">
                                    Tanggal pinjam harus diisi
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="tanggal_kembali" class="form-label-custom mb-2">
                                    Tanggal Kembali <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-lg form-control-custom" id="tanggal_kembali" name="tanggal_kembali" min="<?php echo date('Y-m-d'); ?>" required>
                                <div class="invalid-feedback">
                                    Tanggal kembali harus diisi dan setelah tanggal pinjam
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-4">
                        <button type="button" class="btn btn-success btn-lg" id="add-to-cart">
                            <i class="bi bi-cart-plus me-2"></i> Tambahkan ke Keranjang
                        </button>
                            </div>
                        </div>
                
                <!-- Cart Display Area -->
                <div id="borrowing-cart" class="mt-5">
                    <h5 class="mb-3">Keranjang Peminjaman (<span id="cart-count">0</span>)</h5>
                    <div id="cart-items-container">
                        <p class="text-muted text-center">Belum ada sarana di keranjang.</p>
                    </div>
                    
                    <form id="submit-borrow-form" method="post" action="">
                        <input type="hidden" name="cart_data" id="cart-data-input">
                        
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-custom btn-lg">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                            <button type="submit" class="btn btn-warning btn-lg" id="submit-borrow" disabled>
                                <i class="bi bi-box-arrow-in-down me-2"></i> Ajukan Peminjaman
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const saranaSelect = document.getElementById('sarana_id');
        const jumlahPinjamInput = document.getElementById('jumlah_pinjam');
        const tanggalPinjamInput = document.getElementById('tanggal_pinjam');
        const tanggalKembaliInput = document.getElementById('tanggal_kembali');
        const jumlahTersediaText = document.getElementById('jumlah_tersedia_text');
        const saranaInfo = document.getElementById('sarana-info');
        const lokasiInfo = document.getElementById('lokasi-info');
        const tersediaInfo = document.getElementById('tersedia-info');
        const addToCartButton = document.getElementById('add-to-cart');
        const cartItemsContainer = document.getElementById('cart-items-container');
        const cartCountSpan = document.getElementById('cart-count');
        const submitBorrowButton = document.getElementById('submit-borrow');
        const cartDataInput = document.getElementById('cart-data-input');
        
        let borrowingCart = []; // Array to hold cart items
        
        // Function to update the cart display
        function updateCartDisplay() {
            cartItemsContainer.innerHTML = ''; // Clear current display
            cartCountSpan.textContent = borrowingCart.length;
            
            if (borrowingCart.length === 0) {
                cartItemsContainer.innerHTML = '<p class="text-muted text-center">Belum ada sarana di keranjang.</p>';
                submitBorrowButton.disabled = true;
            } else {
                submitBorrowButton.disabled = false;
                const table = document.createElement('table');
                table.classList.add('table', 'table-striped', 'table-hover', 'table-custom', 'mb-0');
                table.innerHTML = `
                    <thead>
                        <tr>
                            <th>Sarana</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-center">Tgl Pinjam</th>
                            <th class="text-center">Tgl Kembali</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                `;
                const tbody = table.querySelector('tbody');
                
                borrowingCart.forEach((item, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.nama_sarana}</td>
                        <td class="text-center">${item.jumlah_pinjam}</td>
                        <td class="text-center">${item.tanggal_pinjam}</td>
                        <td class="text-center">${item.tanggal_kembali}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
                cartItemsContainer.appendChild(table);
            }
            
            // Update hidden input field with cart data (as JSON)
            cartDataInput.value = JSON.stringify(borrowingCart);
        }
        
        // Event listener for Sarana select change
        saranaSelect.addEventListener('change', function() {
            if (saranaSelect.value) {
                const selectedOption = saranaSelect.options[saranaSelect.selectedIndex];
                const maxJumlah = selectedOption.dataset.jumlah;
                const lokasi = selectedOption.dataset.lokasi || 'Tidak tersedia';
                
                jumlahPinjamInput.max = maxJumlah;
                jumlahPinjamInput.value = 1;
                jumlahTersediaText.textContent = `Maksimal peminjaman: ${maxJumlah} unit`;
                
                // Tampilkan info sarana
                lokasiInfo.textContent = lokasi;
                tersediaInfo.textContent = maxJumlah;
                saranaInfo.classList.remove('d-none');
                
                // Enable Add to Cart button if essential fields have values
                checkFormValidity();

            } else {
                saranaInfo.classList.add('d-none');
                jumlahTersediaText.textContent = '';
                jumlahPinjamInput.max = '';
                jumlahPinjamInput.value = '';
                 addToCartButton.disabled = true;
            }
        });

        // Function to check if the add item form is valid
        function checkFormValidity() {
            const saranaValid = saranaSelect.value !== '';
            const jumlahValid = parseInt(jumlahPinjamInput.value) > 0 && parseInt(jumlahPinjamInput.value) <= parseInt(jumlahPinjamInput.max);
            const tanggalPinjamValid = tanggalPinjamInput.value !== '';
            const tanggalKembaliValid = tanggalKembaliInput.value !== '';
            const datesValid = tanggalPinjamValid && tanggalKembaliValid && new Date(tanggalPinjamInput.value) <= new Date(tanggalKembaliInput.value);

            addToCartButton.disabled = !(saranaValid && jumlahValid && tanggalPinjamValid && tanggalKembaliValid && datesValid);
        }

        // Add event listeners for input changes to check form validity
        jumlahPinjamInput.addEventListener('input', checkFormValidity);
        tanggalPinjamInput.addEventListener('input', checkFormValidity);
        tanggalKembaliInput.addEventListener('input', checkFormValidity);

        // Event listener for Add to Cart button
        addToCartButton.addEventListener('click', function() {
            checkFormValidity(); // Re-check before adding
            if (addToCartButton.disabled) {
                return; // Don't add if form is invalid
            }

            const saranaId = saranaSelect.value;
            const saranaName = saranaSelect.options[saranaSelect.selectedIndex].dataset.nama;
            const jumlahPinjam = parseInt(jumlahPinjamInput.value);
            const tanggalPinjam = tanggalPinjamInput.value;
            const tanggalKembali = tanggalKembaliInput.value;
            const available = parseInt(saranaSelect.options[saranaSelect.selectedIndex].dataset.jumlah);

            // Basic validation (more robust validation on server-side)
            if (!saranaId || jumlahPinjam <= 0 || jumlahPinjam > available || !tanggalPinjam || !tanggalKembali || new Date(tanggalPinjam) > new Date(tanggalKembali)) {
                 setAlert('danger', 'Validasi gagal. Pastikan semua field terisi benar dan jumlah tersedia cukup.'); // Will need PHP function equivalent
                 return;
            }
            
            // Check if item is already in cart with same dates
            const existingItemIndex = borrowingCart.findIndex(item => 
                item.sarana_id === saranaId && 
                item.tanggal_pinjam === tanggalPinjam && 
                item.tanggal_kembali === tanggalKembali
            );

            if (existingItemIndex > -1) {
                // If exists, update quantity (client-side only for display)
                borrowingCart[existingItemIndex].jumlah_pinjam += jumlahPinjam;
                 // You might want to add a check here to ensure updated quantity doesn't exceed available
                 if (borrowingCart[existingItemIndex].jumlah_pinjam > available) {
                     borrowingCart[existingItemIndex].jumlah_pinjam = available; // Cap at available
                     setAlert('warning', `Jumlah peminjaman untuk ${saranaName} dibatasi sesuai jumlah tersedia.`); // Need PHP equivalent
                 }
            } else {
                // Add new item to cart
                borrowingCart.push({
                    sarana_id: saranaId,
                    nama_sarana: saranaName,
                    jumlah_pinjam: jumlahPinjam,
                    tanggal_pinjam: tanggalPinjam,
                    tanggal_kembali: tanggalKembali
                });
            }
            
            // Clear form and update display
            saranaSelect.value = '';
            jumlahPinjamInput.value = 1; // Reset quantity
            tanggalPinjamInput.value = '';
            tanggalKembaliInput.value = '';
            saranaInfo.classList.add('d-none');
            jumlahTersediaText.textContent = '';
            addToCartButton.disabled = true; // Disable button after adding

            updateCartDisplay();
             setAlert('success', `${saranaName} ditambahkan ke keranjang.`); // Need PHP equivalent
        });
        
        // Event listener for removing item from cart
        cartItemsContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-item')) {
                const index = event.target.dataset.index;
                const removedItemName = borrowingCart[index].nama_sarana;
                borrowingCart.splice(index, 1);
                updateCartDisplay();
                 setAlert('info', `${removedItemName} dihapus dari keranjang.`); // Need PHP equivalent
            }
        });

        // Initial display update
        updateCartDisplay();

        // Client-side form validation for submit (optional, server-side is crucial)
        const submitForm = document.getElementById('submit-borrow-form');
        submitForm.addEventListener('submit', function(event) {
             if (borrowingCart.length === 0) {
                 setAlert('danger', 'Keranjang peminjaman kosong.'); // Need PHP equivalent
                 event.preventDefault();
             }
            // More comprehensive validation will be needed on the server-side
        });

         // Helper function to display messages (replace with your actual setAlert implementation)
         function setAlert(type, message) {
            console.log(`ALERT (${type}): ${message}`); // Placeholder
            // You should implement your site's alert display logic here
            // e.g., create and show a Bootstrap alert div
             const alertContainer = document.getElementById('alert-container'); // Assuming you have an alert container div
             if (!alertContainer) return;

             const alertDiv = document.createElement('div');
             alertDiv.classList.add('alert', `alert-${type}`, 'alert-dismissible', 'fade', 'show', 'mt-3');
             alertDiv.setAttribute('role', 'alert');
             alertDiv.innerHTML = `
                 ${message}
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             `;
             alertContainer.appendChild(alertDiv);
         }
    });
</script>

<?php require_once '../templates/footer.php'; ?>

<?php require_once '../templates/footer.php'; ?>

<?php
$page_title = "Dashboard User";
require_once '../templates/header.php';

// Cek apakah user adalah user biasa
if (isAdmin()) {
    redirect('../admin/index.php');
}

// Mengambil data untuk dashboard
$user_id = $_SESSION['user_id'];

// Total peminjaman user
$query_total = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ?";
$stmt_total = $conn->prepare($query_total);
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_peminjaman = $result_total->fetch_assoc()['total'];
$stmt_total->close();

// Peminjaman menunggu
$query_menunggu = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'menunggu'";
$stmt_menunggu = $conn->prepare($query_menunggu);
$stmt_menunggu->bind_param("i", $user_id);
$stmt_menunggu->execute();
$result_menunggu = $stmt_menunggu->get_result();
$total_menunggu = $result_menunggu->fetch_assoc()['total'];
$stmt_menunggu->close();

$query_menunggu_pengembalian = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'menunggu pengembalian'";
$stmt_menunggu_pengembalian = $conn->prepare($query_menunggu_pengembalian);
$stmt_menunggu_pengembalian->bind_param("i", $user_id);
$stmt_menunggu_pengembalian->execute();
$result_menunggu_pengembalian = $stmt_menunggu_pengembalian->get_result();
$total_menunggu_pengembalian = $result_menunggu_pengembalian->fetch_assoc()['total'];
$stmt_menunggu_pengembalian->close();

// Peminjaman disetujui
$query_disetujui = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'disetujui'";
$stmt_disetujui = $conn->prepare($query_disetujui);
$stmt_disetujui->bind_param("i", $user_id);
$stmt_disetujui->execute();
$result_disetujui = $stmt_disetujui->get_result();
$total_disetujui = $result_disetujui->fetch_assoc()['total'];
$stmt_disetujui->close();

// Peminjaman selesai
$query_selesai = "SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'selesai'";
$stmt_selesai = $conn->prepare($query_selesai);
$stmt_selesai->bind_param("i", $user_id);
$stmt_selesai->execute();
$result_selesai = $stmt_selesai->get_result();
$total_selesai = $result_selesai->fetch_assoc()['total'];
$stmt_selesai->close();

// Peminjaman terbaru
$query_terbaru = "SELECT p.*, s.nama_sarana, p.catatan_admin 
                 FROM peminjaman p 
                 JOIN sarana s ON p.sarana_id = s.sarana_id 
                 WHERE p.user_id = ? 
                 ORDER BY p.peminjaman_id DESC LIMIT 5";
$stmt_terbaru = $conn->prepare($query_terbaru);
$stmt_terbaru->bind_param("i", $user_id);
$stmt_terbaru->execute();
$result_terbaru = $stmt_terbaru->get_result();
$stmt_terbaru->close();

// Query for ALL transaction count by date for the current month for the current user
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

$query_all_transactions_by_date = "SELECT DATE(tanggal_pinjam) as transaction_date, COUNT(*) as total_transactions 
                                   FROM peminjaman 
                                   WHERE user_id = ? AND tanggal_pinjam BETWEEN ? AND ? 
                                   GROUP BY DATE(tanggal_pinjam) 
                                   ORDER BY transaction_date ASC";
$stmt_all_transactions_by_date = $conn->prepare($query_all_transactions_by_date);
$stmt_all_transactions_by_date->bind_param("iss", $user_id, $current_month_start, $current_month_end);
$stmt_all_transactions_by_date->execute();
$result_all_transactions_by_date = $stmt_all_transactions_by_date->get_result();
$stmt_all_transactions_by_date->close();

// Prepare data for chart - include all dates in the month
$all_transaction_data = [];
while ($row = $result_all_transactions_by_date->fetch_assoc()) {
    $all_transaction_data[$row['transaction_date']] = $row['total_transactions'];
}

$chart_dates = [];
$chart_counts = [];

$start_date = new DateTime($current_month_start);
$end_date = new DateTime($current_month_end);
$interval = new DateInterval('P1D');
$period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

foreach ($period as $date) {
    $formatted_date = $date->format('Y-m-d');
    $chart_dates[] = $formatted_date;
    $chart_counts[] = isset($all_transaction_data[$formatted_date]) ? $all_transaction_data[$formatted_date] : 0;
}
?>

<div class="row">
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-primary">
            <div class="d-flex justify-content-between">
                <div class="stat-content">
                    <div class="stat-title">Total Transaksi</div>
                    <div class="stat-value"><?php echo $total_peminjaman; ?></div>
                    <a href="riwayat.php" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-warning">
            <div class="d-flex justify-content-between">
                <div class="stat-content">
                    <div class="stat-title">Menunggu Persetujuan Peminjaman</div>
                    <div class="stat-value"><?php echo $total_menunggu; ?></div>
                    <a href="riwayat.php?status=menunggu" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-warning">
            <div class="d-flex justify-content-between">
                <div class="stat-content">
                    <div class="stat-title">Menunggu Persetujuan Pengembalian</div>
                    <div class="stat-value"><?php echo $total_menunggu_pengembalian; ?></div>
                    <a href="riwayat.php?status=menunggu pengembalian" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-success">
            <div class="d-flex justify-content-between">
                <div class="stat-content">
                    <div class="stat-title">Peminjaman Aktif</div>
                    <div class="stat-value"><?php echo $total_disetujui; ?></div>
                    <a href="riwayat.php?status=disetujui" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-info">
            <div class="d-flex justify-content-between">
                <div class="stat-content">
                    <div class="stat-title">Peminjaman Selesai</div>
                    <div class="stat-value"><?php echo $total_selesai; ?></div>
                    <a href="riwayat.php?status=selesai" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card card-custom ">
            <div class="card-header-custom">
                <h5 class="mb-0">Grafik Transaksi Selesai Bulan Ini</h5>
            </div>
            <div class="card-body-custom" style="min-height: 300px;">
                <canvas id="transactionsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Peminjaman Terbaru</h5>
                <a href="riwayat.php" class="btn btn-sm btn-outline-custom">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body-custom p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" width="15%">Sarana</th>
                                <th class="text-center" width="15%">Tanggal Pinjam</th>
                                <th class="text-center" width="15%">Tanggal Kembali</th>
                                <th class="text-center" width="15%">Status</th>
                                <th class="text-center" width="15%">Catatan Admin</th>
                                <th class="text-center" width="15%">Detail</th>
                                <th class="text-center" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_terbaru->num_rows > 0): ?>
                                <?php while ($row = $result_terbaru->fetch_assoc()): ?>
                                    <tr>
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
                                        <td class="text-center"><?php echo $row['tanggal_pinjam']; ?></td>
                                        <td class="text-center"><?php echo $row['tanggal_kembali']; ?></td>
                                        <td class="text-center">
                                            <?php 
                                            $status_class = '';
                                            switch($row['status']) {
                                                case 'menunggu':
                                                    $status_class = 'badge-waiting';
                                                    break;
                                                case 'disetujui':
                                                    $status_class = 'badge-approved';
                                                    break;
                                                case 'ditolak':
                                                    $status_class = 'badge-rejected';
                                                    break;
                                                case 'selesai':
                                                    $status_class = 'badge-completed';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge-custom <?php echo $status_class; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?php echo $row['catatan_admin']; ?></td>
                                        <td class="text-center">
                                            <a href="detail_peminjaman.php?id=<?php echo $row['peminjaman_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['status'] == 'disetujui'): ?>
                                                <a href="detail_peminjaman.php?id=<?php echo $row['peminjaman_id']; ?>&action=pengembalian" class="btn btn-sm btn-primary" onclick="return confirm('Apakah Anda yakin ingin mengajukan pengembalian sarana ini?')">
                                                    <i class="bi bi-box-arrow-left"></i> Ajukan Pengembalian
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">Tidak ada data peminjaman</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
                

<?php require_once '../templates/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('transactionsChart').getContext('2d');
        const transactionDates = <?php echo json_encode($chart_dates); ?>;
        const transactionCounts = <?php echo json_encode($chart_counts); ?>;

        new Chart(ctx, {
            type: 'bar', // Keep as bar chart
            data: {
                labels: transactionDates,
                datasets: [{
                    label: 'Jumlah Transaksi Selesai', // Updated label
                    data: transactionCounts,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)', // Match admin chart color
                    borderColor: 'rgba(16, 185, 129, 1)', // Match admin chart color
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Transaksi'
                        },
                        ticks: {
                            precision: 0 // Match admin chart y-axis ticks
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true // Match admin chart legend display
                    },
                    title: {
                        display: true,
                        text: 'Grafik Transaksi Selesai Bulan Ini' // Match admin chart title text
                    }
                },
                 responsive: true,
                 maintainAspectRatio: false
            }
        });
    });
</script>

</body>
</html>

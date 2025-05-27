<?php
$page_title = "Dashboard Admin";
require_once '../templates/header.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Mengambil data untuk dashboard
// Total sarana
$query_sarana = "SELECT COUNT(*) as total FROM sarana";
$result_sarana = $conn->query($query_sarana);
$total_sarana = $result_sarana->fetch_assoc()['total'];

// Total peminjaman
$query_peminjaman = "SELECT COUNT(*) as total FROM peminjaman";
$result_peminjaman = $conn->query($query_peminjaman);
$total_peminjaman = $result_peminjaman->fetch_assoc()['total'];

// Peminjaman menunggu
$query_menunggu = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'menunggu'";
$result_menunggu = $conn->query($query_menunggu);
$total_menunggu = $result_menunggu->fetch_assoc()['total'];

// Peminjaman disetujui
$query_disetujui = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'disetujui'";
$result_disetujui = $conn->query($query_disetujui);
$total_disetujui = $result_disetujui->fetch_assoc()['total'];

// Peminjaman selesai
$query_selesai = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'selesai'";
$result_selesai = $conn->query($query_selesai);
$total_selesai = $result_selesai->fetch_assoc()['total'];

$query_menunggu_pengembalian = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'menunggu pengembalian'";
$result_menununggu_pengembalian = $conn->query($query_menunggu_pengembalian);
$total_menunggu_pengembalian = $result_menununggu_pengembalian->fetch_assoc()['total'];

// Peminjaman terbaru
$query_terbaru = "SELECT p.*, u.nama_lengkap, s.nama_sarana 
                 FROM peminjaman p 
                 JOIN users u ON p.user_id = u.user_id 
                 JOIN sarana s ON p.sarana_id = s.sarana_id 
                 ORDER BY p.peminjaman_id DESC LIMIT 5";
$result_terbaru = $conn->query($query_terbaru);

// Query for completed transaction count by date for the current month
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

$query_completed_transactions_by_date = "SELECT DATE(tanggal_pinjam) as transaction_date, COUNT(*) as total_transactions 
                                         FROM peminjaman 
                                         WHERE status = 'selesai' AND tanggal_pinjam BETWEEN ? AND ? 
                                         GROUP BY DATE(tanggal_pinjam) 
                                         ORDER BY transaction_date ASC";
$stmt_completed_transactions_by_date = $conn->prepare($query_completed_transactions_by_date);
$stmt_completed_transactions_by_date->bind_param("ss", $current_month_start, $current_month_end);
$stmt_completed_transactions_by_date->execute();
$result_completed_transactions_by_date = $stmt_completed_transactions_by_date->get_result();
$stmt_completed_transactions_by_date->close();

// Prepare data for chart - include all dates in the month
$completed_transaction_data = [];
while ($row = $result_completed_transactions_by_date->fetch_assoc()) {
    $completed_transaction_data[$row['transaction_date']] = $row['total_transactions'];
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
    $chart_counts[] = isset($completed_transaction_data[$formatted_date]) ? $completed_transaction_data[$formatted_date] : 0;
}

// Query untuk sarana terpopuler
$query_popular = "SELECT s.sarana_id, s.nama_sarana, COUNT(p.peminjaman_id) as total_pinjam 
                 FROM sarana s 
                 LEFT JOIN peminjaman p ON s.sarana_id = p.sarana_id 
                 GROUP BY s.sarana_id 
                 ORDER BY total_pinjam DESC 
                 LIMIT 4";
$result_popular = $conn->query($query_popular);
?>

<div class="row">
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-primary" style="transition: transform 0.3s ease;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-content">
                    <div class="stat-title">Total Sarana</div>
                    <div class="stat-value"><?php echo $total_sarana; ?></div>
                    <a href="sarana.php" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-success" style="transition: transform 0.3s ease;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-content">
                    <div class="stat-title">Total Peminjaman</div>
                    <div class="stat-value"><?php echo $total_disetujui; ?></div>
                    <a href="peminjaman.php" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-warning" style="transition: transform 0.3s ease;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-content">
                    <div class="stat-title">Menunggu Persetujuan Peminjaman</div>
                    <div class="stat-value"><?php echo $total_menunggu; ?></div>
                    <a href="peminjaman.php?status=menunggu" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-warning" style="transition: transform 0.3s ease;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-content">
                    <div class="stat-title">Menunggu Persetujuan pengembalian</div>
                    <div class="stat-value"><?php echo $total_menunggu_pengembalian; ?></div>
                    <a href="peminjaman.php?status=menunggu pengembalian" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
                
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stat-card stat-card-info" style="transition: transform 0.3s ease;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-content">
                    <div class="stat-title">Peminjaman Selesai</div>
                    <div class="stat-value"><?php echo $total_selesai; ?></div>
                    <a href="peminjaman.php?status=selesai" class="stat-link">
                        Lihat Detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card card-custom">
            <div class="card-header-custom">
                <h5 class="mb-0">Grafik Transaksi Selesai Bulan Ini</h5>
            </div>
            <div class="card-body-custom" style="min-height: 300px;">
                <canvas id="completedTransactionsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Peminjaman Terbaru</h5>
                <a href="peminjaman.php" class="btn btn-sm btn-outline-custom">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body-custom p-0">
                <div class="table-responsive">
                    <table class="table table-custom w-100 mb-0" style="width: 100%">
                        <thead>
                            <tr>
                                <th width="20%">Peminjam</th>
                                <th width="20%">Sarana</th>
                                <th width="15%">Tanggal Pinjam</th>
                                <th width="15%">Status</th>
                                <th width="15%">Detail</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_terbaru->num_rows > 0): ?>
                                <?php while ($row = $result_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.8rem; margin-right: 10px;">
                                                    <?php echo getInitials($row['nama_lengkap']); ?>
                                                </div>
                                                <?php echo $row['nama_lengkap']; ?>
                                            </div>
                                        </td>
                                        <td><?php echo $row['nama_sarana']; ?></td>
                                        <td><?php echo $row['tanggal_pinjam']; ?></td>
                                        <td>
                                            <?php 
                                            $status_class = '';
                                            switch($row['status']) {
                                                case 'menunggu':
                                                    $status_class = 'badge-waiting';
                                                    break;
                                                case 'menunggu pengembalian':
                                                    $status_class = 'badge-waiting-pengembalian';
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
                                        <td>
                                            <a href="detail_peminjaman.php?id=<?php echo $row['peminjaman_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'menunggu'): ?>
                                                <div class="btn-group">
                                                    <a href="update_status.php?id=<?php echo $row['peminjaman_id']; ?>&status=disetujui" class="btn btn-sm btn-success" onclick="return confirm('Apakah Anda yakin ingin menyetujui peminjaman ini?')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </a>
                                                    <!-- Tombol Hapus -->
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="showConfirmBox(<?php echo $row['peminjaman_id']; ?>)">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>

                                                    <div id="confirm-box-<?php echo $row['peminjaman_id']; ?>" class="mt-2" style="display: none; position: absolute; background: white; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 1000;">
                                                        <form action="update_status.php" method="POST" class="rejection-form">
                                                            <input type="hidden" name="id" value="<?php echo $row['peminjaman_id']; ?>">
                                                            <input type="hidden" name="status" value="ditolak">
                                                            <div class="mb-2">
                                                                <input type="text" name="catatan_admin" class="form-control form-control-sm" placeholder="Masukkan alasan" required>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <button type="submit" class="btn btn-sm btn-danger">Tolak</button>
                                                                <button type="button" class="btn btn-sm btn-secondary" onclick="hideConfirmBox(<?php echo $row['peminjaman_id']; ?>)">Batal</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                            <?php elseif ($row['status'] == 'menunggu pengembalian'): ?>
                                                <div class="btn-group">
                                                    <a href="update_status.php?id=<?php echo $row['peminjaman_id']; ?>&status=selesai" class="btn btn-sm btn-success" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengembalian ini?')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">Tidak ada data peminjaman</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
function showConfirmBox(id) {
    console.log('showConfirmBox called for id:', id);
    // Implement your modal/confirm box logic here
}

function hideConfirmBox(id) {
     console.log('hideConfirmBox called for id:', id);
     // Implement your modal/confirm box logic here
}
</script>

<?php require_once '../templates/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('completedTransactionsChart').getContext('2d');
        const chartDates = <?php echo json_encode($chart_dates); ?>;
        const chartCounts = <?php echo json_encode($chart_counts); ?>;

        new Chart(ctx, {
            type: 'bar', // Changed to bar chart
            data: {
                labels: chartDates,
                datasets: [{
                    label: 'Jumlah Transaksi Selesai',
                    data: chartCounts,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)', // Adjusted color and transparency
                    borderColor: 'rgba(16, 185, 129, 1)', // Adjusted color
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
                            precision: 0 // Display whole numbers
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
                        display: true // Show dataset legend
                    },
                    title: {
                        display: true,
                        text: 'Grafik Transaksi Selesai Bulan Ini'
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>

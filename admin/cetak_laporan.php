<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Cek apakah user adalah admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

// Filter tanggal
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Query untuk laporan
$query = "SELECT p.*, u.nama_lengkap, u.jenis_pengguna, s.nama_sarana, u.id_card 
          FROM peminjaman p 
          JOIN users u ON p.user_id = u.user_id 
          JOIN sarana s ON p.sarana_id = s.sarana_id 
          WHERE p.tanggal_pinjam BETWEEN ? AND ?";

// Tambahkan filter status jika ada
if (!empty($status)) {
    $query .= " AND p.status = ?";
}

$query .= " ORDER BY p.peminjaman_id DESC";
$stmt = $conn->prepare($query);

if (!empty($status)) {
    $stmt->bind_param("sss", $tanggal_awal, $tanggal_akhir, $status);
} else {
    $stmt->bind_param("ss", $tanggal_awal, $tanggal_akhir);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Judul laporan

$judul_periode = "Periode: " . date('d/m/Y', strtotime($tanggal_awal)) . " - " . date('d/m/Y', strtotime($tanggal_akhir));

// Require library FPDF
require('../lib/fpdf/fpdf.php');

class PDF extends FPDF {
    // Header
    function Header() {
        // Logo (adjust path, x, y, width as needed)
        $logo_path = '../lib/fpdf/SARPRAS.png'; // Path relative to admin directory
        $logo_x = 10;
        $logo_y = 10;
        $logo_width = 20; // Adjust width as needed
        $this->Image($logo_path, $logo_x, $logo_y, $logo_width);

        // Company Information (positioned to the right of the logo)
        $text_x = $logo_x + $logo_width + 0; // 5mm padding after logo
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY($text_x, $logo_y); // Set position
        $this->Cell(0, 6, 'SARPRASS', 0, 1, 'C'); // Align Left ('L')

        $this->SetFont('Arial', '', 12);
        $this->SetX($text_x); // Set x position for the next lines
        $this->Cell(0, 6, 'Griya Asri 2 Blok.E7 No.29 JL. Nusantara VI, Bekasi, Jawa Barat 17510', 0, 1, 'C');
        $this->SetX($text_x);
        $this->Cell(0, 6, 'Telp: (+62) 1287927510', 0, 1, 'C');
        $this->SetX($text_x);
        $this->Cell(0, 6, 'Email: sarprass@gmail.com', 0, 1, 'C');

        // Line separator
        $line_y = max($this->GetY(), $logo_y + $logo_width * 0.8) + 5; // Position line below the lowest point of text or logo
        $this->SetDrawColor(0, 0, 0);
        $this->Line(10, $line_y, 200, $line_y);
        $this->SetY($line_y + 5); // Move cursor below the line

        // Report Title
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'LAPORAN PEMINJAMAN SARANA', 0, 1, 'C');

        // Report Period
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 6, $GLOBALS['judul_periode'], 0, 1, 'C');

        $this->Ln(5);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Instanciation of inherited class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Header tabel
$pdf->SetFont('Arial', 'B', 10);

$pdf->Cell(10, 10, 'No', 1, 0, 'C');
$pdf->Cell(25, 10, 'ID Card', 1, 0, 'C');
$pdf->Cell(35, 10, 'Peminjam', 1, 0, 'C');
$pdf->Cell(35, 10, 'Sarana', 1, 0, 'C');
$pdf->Cell(25, 10, 'Tgl Pinjam', 1, 0, 'C');
$pdf->Cell(25, 10, 'Tgl Kembali', 1, 0, 'C');
$pdf->Cell(15, 10, 'Jumlah', 1, 0, 'C');
$pdf->Cell(25, 10, 'Status', 1, 1, 'C');

// Isi tabel
$pdf->SetFont('Arial', '', 10);
$no = 1;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(10, 10, $no++, 1, 0, 'C');
        $pdf->Cell(25, 10, $row['id_card'], 1, 0, 'L');
        $pdf->Cell(35, 10, $row['nama_lengkap'], 1, 0, 'L');
        $pdf->Cell(35, 10, $row['nama_sarana'], 1, 0, 'L');
        $pdf->Cell(25, 10, $row['tanggal_pinjam'], 1, 0, 'C');
        $pdf->Cell(25, 10, $row['tanggal_kembali'], 1, 0, 'C');
        $pdf->Cell(15, 10, $row['jumlah_pinjam'], 1, 0, 'C');
        $pdf->Cell(25, 10, $row['status'], 1, 1, 'C');
    }
} else {
    $pdf->Cell(195, 10, 'Tidak ada data peminjaman', 1, 1, 'C');
}

// Tanda tangan hanya di halaman terakhir
$pdf->Ln(20);
$pdf->Cell(130);
$pdf->Cell(15, 10, 'Admin,', 0, 0, 'L');
$pdf->Cell(20, 10, date('d/m/Y'), 0, 1, 'R');
$pdf->Ln(15);
$pdf->Cell(130);
$pdf->Cell(37, 10, $_SESSION['nama_lengkap'], 0, 1, 'C');

// Output PDF
$pdf->Output('laporan_peminjaman.pdf', 'I');
?>

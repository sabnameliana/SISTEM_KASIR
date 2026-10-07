<?php
session_start();
include "../config/koneksi.php";
/** @var mysqli $koneksi */ global $koneksi;

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['role'] != 'admin') {
    echo "<script>
            alert('Akses ditolak! Halaman ini khusus Admin.');
            window.location.href='../index.php';
          </script>";
    exit();
}

// === PROSES BATAL / HAPUS TRANSAKSI LANGSUNG DI SINI ===
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal' && isset($_GET['id'])) {
    $penjualanID = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 1. Ambil data produk dan jumlah yang dibeli untuk dikembalikan stoknya
    $q_detail = mysqli_query($koneksi, "SELECT produkID, jumlahproduk FROM detail_penjualan WHERE penjualanID = '$penjualanID'");
    
    while ($row = mysqli_fetch_assoc($q_detail)) {
        $prodID = $row['produkID'];
        $qtyBeli = $row['jumlahproduk'];

        // Kembalikan stok produk ke database
        mysqli_query($koneksi, "UPDATE produk SET stok = stok + $qtyBeli WHERE produkID = '$prodID'");
    }

    // 2. Hapus data dari detail_penjualan terlebih dahulu
    mysqli_query($koneksi, "DELETE FROM detail_penjualan WHERE penjualanID = '$penjualanID'");

    // 3. Hapus data utama dari tabel penjualan
    $hapus_penjualan = mysqli_query($koneksi, "DELETE FROM penjualan WHERE penjualanID = '$penjualanID'");

    if ($hapus_penjualan) {
        echo "<script>
            alert('Transaksi berhasil dibatalkan dan stok produk telah dikembalikan!');
            window.location.href='laporan.php'; 
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Gagal membatalkan transaksi: " . mysqli_error($koneksi) . "');
            window.location.href='laporan.php'; 
        </script>";
        exit();
    }
}

$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$role = $_SESSION['role'] ?? '';

$q_laporan = mysqli_query($koneksi, "
    SELECT p.*, pl.namapelanggan, pl.status, u.nama AS nama_kasir
    FROM penjualan p
    LEFT JOIN pelanggan pl ON p.pelangganID = pl.pelangganID
    LEFT JOIN user u ON p.userID = u.userID
    WHERE p.tgl_penjualan BETWEEN '$tgl_mulai 00:00:00' AND '$tgl_selesai 23:59:59'
    ORDER BY p.penjualanID DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">

            <!-- SIDEBAR -->
            <div class="col-md-2 bg-dark min-vh-100 p-3 text-white d-print-none">
                <h5>SISTEM KASIR</h5>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="../index.php" class="nav-link text-white">Dashboard</a></li>
                    <li class="nav-item mb-2"><a href="../pelanggan/pelanggan.php" class="nav-link text-white">Pelanggan</a></li>
                    <li class="nav-item mb-2"><a href="../produk/produk.php" class="nav-link text-white">Produk</a></li>
                    <li class="nav-item mb-2"><a href="../penjualan/penjualan.php" class="nav-link text-white">Penjualan</a></li>
                    <li class="nav-item mb-2"><a href="../user/user.php" class="nav-link text-white">User</a></li>
                    <li class="nav-item mb-2"><a href="laporan.php" class="nav-link text-white bg-secondary rounded">Laporan</a></li>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">

                <!-- KOP / JUDUL KHUSUS CETAK -->
                <div class="text-center mb-4 d-none d-print-block">
                    <h3 class="fw-bold m-0">LAPORAN PENJUALAN SISTEM KASIR</h3>
                    <p class="text-muted m-0">Periode Tanggal: <?= date('d/m/Y', strtotime($tgl_mulai)); ?> s.d <?= date('d/m/Y', strtotime($tgl_selesai)); ?></p>
                    <hr>
                </div>

                <!-- HEADER MONITOR -->
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2 d-print-none">
                    <h4>Laporan Penjualan</h4>
                     <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <!-- FILTER TANGGAL -->
                <div class="card shadow-sm mb-4 d-print-none">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Dari Tanggal</label>
                                <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Sampai Tanggal</label>
                                <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai); ?>" required>
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-filter"></i> Tampilkan</button>
                                <button type="button" onclick="cetakLaporan()" class="btn btn-success flex-fill"><i class="bi bi-printer"></i> Cetak</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TABEL LAPORAN -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div id="areaTabel" class="table-responsive" style="max-height: 450px;">
                            <table class="table table-bordered table-striped align-middle small m-0">
                                <thead class="table-dark text-center sticky-top">
                                    <tr>
                                        <th>No</th>
                                        <th>ID Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Kasir</th>
                                        <th>Pelanggan</th>
                                        <th>Rincian Produk & Pembayaran</th>
                                        <th class="d-print-none">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $grand_total = 0;
                                    if ($q_laporan && mysqli_num_rows($q_laporan) > 0):
                                        while ($p = mysqli_fetch_assoc($q_laporan)):
                                            $id = $p['penjualanID'];
                                            $tot = $p['total_harga'] ?? 0;
                                            $grand_total += $tot;

                                            $q_det = mysqli_query($koneksi, "SELECT dp.*, pr.namaproduk, pr.harga FROM detail_penjualan dp LEFT JOIN produk pr ON dp.produkID = pr.produkID WHERE dp.penjualanID = '$id'");
                                    ?>
                                            <tr>
                                                <td class="text-center"><?= $no++; ?></td>
                                                <td class="text-center"><span class="badge bg-secondary">#TRX<?= $id; ?></span></td>
                                                <td class="text-center"><?= date('d/m/Y H:i', strtotime($p['tgl_penjualan'])); ?></td>
                                                <td><?= htmlspecialchars($p['nama_kasir'] ?? 'Admin'); ?></td>
                                                <td>
                                                    <?php if (!empty($p['namapelanggan']) && strtolower($p['status']) == 'member'): ?>
                                                        <b><?= htmlspecialchars($p['namapelanggan']); ?></b><br>
                                                        <span class="badge bg-info text-dark" style="font-size: 9px;">Member</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Pelanggan Umum</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <ul class="ps-3 m-0">
                                                        <?php
                                                        $subtotal_kotor = 0;
                                                        while ($d = mysqli_fetch_assoc($q_det)):
                                                            $jumlah = $d['jumlahproduk'];
                                                            $subtotal_item = $d['subtotal'];
                                                            $subtotal_kotor += $subtotal_item;
                                                            $harga_satuan = $jumlah > 0 ? ($subtotal_item / $jumlah) : 0;
                                                        ?>
                                                            <li class="mb-1">
                                                                <b><?= htmlspecialchars($d['namaproduk']); ?></b><br>
                                                                <span class="text-muted" style="font-size: 11px;">
                                                                    <?= $jumlah; ?> pcs × Rp <?= number_format($harga_satuan, 0, ',', '.'); ?> = Rp <?= number_format($subtotal_item, 0, ',', '.'); ?>
                                                                </span>
                                                            </li>
                                                        <?php endwhile; ?>
                                                    </ul>

                                                    <hr class="my-1">

                                                    <div style="font-size: 11px;" class="px-2">
                                                        <?php
                                                        $is_member = (!empty($p['namapelanggan']) && strtolower($p['status']) == 'member');
                                                        $subtotal_kotor = $subtotal_kotor ?? $tot;
                                                        $diskon = $is_member ? ($subtotal_kotor * 0.10) : 0;
                                                        ?>

                                                        <?php if ($is_member): ?>
                                                            <div class="d-flex justify-content-between">
                                                                <span>Subtotal:</span>
                                                                <span>Rp <?= number_format($subtotal_kotor, 0, ',', '.'); ?></span>
                                                            </div>
                                                            <div class="d-flex justify-content-between text-danger">
                                                                <span>Diskon (10%):</span>
                                                                <span>-Rp <?= number_format($diskon, 0, ',', '.'); ?></span>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="d-flex justify-content-between fw-bold border-top pt-1 mt-1">
                                                            <span>Total Akhir:</span>
                                                            <span class="text-success">Rp <?= number_format($tot, 0, ',', '.'); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <a href="../penjualan/nota.php?id=<?= $id; ?>" target="_blank" class="btn btn-success btn-sm px-2 py-1" title="Cetak Struk">
                                                            🖨️
                                                        </a>
                                                        <a href="laporan.php?aksi=batal&id=<?= $id; ?>" class="btn btn-danger btn-sm px-2 py-1" title="Batal Transaksi" onclick="return confirm('Yakin ingin membatalkan transaksi #TRX<?= $id; ?>? Stok produk akan otomatis dikembalikan.')">
                                                            ❌ Batal
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php
                                        endwhile;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">Tidak ada data transaksi pada rentang tanggal tersebut.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- TOTAL KESELURUHAN -->
                        <?php if (mysqli_num_rows($q_laporan) > 0): ?>
                            <div class="alert alert-dark mt-3 d-flex justify-content-between align-items-center mb-0">
                                <h5 class="m-0 fw-bold">TOTAL PENDAPATAN PERIODE INI:</h5>
                                <h4 class="m-0 fw-bold text-success">Rp <?= number_format($grand_total, 0, ',', '.'); ?></h4>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function cetakLaporan() {
            const area = document.getElementById('areaTabel');
            area.style.maxHeight = 'none';
            area.style.overflow = 'visible';

            window.print();

            area.style.maxHeight = '450px';
            area.style.overflow = 'auto';
        }
    </script>
</body>
</html>
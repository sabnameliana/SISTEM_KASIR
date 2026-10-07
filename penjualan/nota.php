<?php
session_start();
include "../config/koneksi.php";
/** @var mysqli $koneksi */ global $koneksi;

$penjualanID = $_GET['id'] ?? exit("ID Transaksi tidak valid.");

$sql = mysqli_query($koneksi, "SELECT p.*, pl.namapelanggan, u.nama AS nama_user FROM penjualan p 
    LEFT JOIN pelanggan pl ON p.pelangganID = pl.pelangganID 
    LEFT JOIN user u ON p.userID = u.userID WHERE p.penjualanID = '$penjualanID'");
$t = mysqli_fetch_array($sql) or exit("Data transaksi tidak ditemukan.");
$nama_kasir = $t['nama_user'] ?? $_SESSION['nama'] ?? 'Kasir';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota #<?= $penjualanID; ?></title>
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
</head>

<body class="bg-light font-monospace" onload="window.print()">

    <!-- CONTAINER NOTA -->
    <div class="bg-white border p-3 mx-auto mt-3 shadow-sm" style="max-width: 300px;">

        <!-- HEADER -->
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">SISTEM KASIR</h6>
            <small class="text-muted d-block" style="font-size: 10px;">Toko Kosmetik</small>
        </div>

        <!-- INFO TRANSAKSI -->
        <div class="border-top border-bottom py-1 my-2" style="font-size: 11px;">
            <div>No Nota : #<?= $penjualanID; ?></div>
            <div>Tanggal : <?= date('d/m/Y H:i', strtotime($t['tgl_penjualan'])); ?></div>
            <div>Kasir &nbsp;&nbsp;: <?= htmlspecialchars($nama_kasir); ?></div>
            <div>Pelanggan: <?= !empty($t['namapelanggan']) ? $t['namapelanggan'] . " (Member)" : "Non-Member"; ?></div>
        </div>

        <!-- ITEM PRODUK -->
        <div class="border-bottom pb-1 mb-2" style="font-size: 11px;">
            <?php
            $sql_det = mysqli_query($koneksi, "SELECT d.*, pr.namaproduk FROM detail_penjualan d JOIN produk pr ON d.produkID = pr.produkID WHERE d.penjualanID = '$penjualanID'");
            $subtotal_kotor = 0;
            while ($d = mysqli_fetch_array($sql_det)) {
                $subtotal_kotor += $d['subtotal'];
                $harga_satuan = $d['jumlahproduk'] > 0 ? $d['subtotal'] / $d['jumlahproduk'] : 0;
            ?>
                <div class="fw-bold"><?= $d['namaproduk']; ?></div>
                <div class="d-flex justify-content-between text-secondary">
                    <span><?= $d['jumlahproduk']; ?> x Rp <?= number_format($harga_satuan, 0, ',', '.'); ?></span>
                    <span><?= number_format($d['subtotal'], 0, ',', '.'); ?></span>
                </div>
            <?php } ?>
        </div>

        <!-- PERHITUNGAN & TOTAL -->
        <div class="mb-2" style="font-size: 11px;">
            <?php
            $total_akhir = $t['total_harga'] ?? 0;
            $is_member = !empty($t['namapelanggan']);
            $bayar_tunai = floatval($_GET['bayar'] ?? $total_akhir);
            $kembalian = max(0, $bayar_tunai - $total_akhir);
            ?>

            <?php if ($is_member): ?>
                <div class="d-flex justify-content-between"><span>Subtotal:</span><span>Rp <?= number_format($subtotal_kotor, 0, ',', '.'); ?></span></div>
                <div class="d-flex justify-content-between text-danger"><span>Diskon (10%):</span><span>-Rp <?= number_format($subtotal_kotor * 0.1, 0, ',', '.'); ?></span></div>
            <?php endif; ?>

            <div class="d-flex justify-content-between fw-bold border-top pt-1 mt-1"><span>Total Akhir:</span><span>Rp <?= number_format($total_akhir, 0, ',', '.'); ?></span></div>
            <div class="d-flex justify-content-between"><span>Bayar:</span><span>Rp <?= number_format($bayar_tunai, 0, ',', '.'); ?></span></div>
            <div class="d-flex justify-content-between"><span>Kembali:</span><span>Rp <?= number_format($kembalian, 0, ',', '.'); ?></span></div>
        </div>

        <!-- FOOTER & TOMBOL -->
        <div class="border-top pt-2 text-center" style="font-size: 10px;">
            <p class="m-0 fw-bold">Terima Kasih!</p>
            <span class="text-muted d-block mb-2">Barang yang sudah di bayar tidak dapat ditukar.</span>

            <div class="d-print-none pt-1">
                <button onclick="window.print()" class="btn btn-primary btn-sm py-0"><i class="bi bi-printer"></i> Cetak</button>
                <button onclick="kembaliKeAsal()" class="btn btn-secondary btn-sm py-0"><i class="bi bi-arrow-left"></i> Kembali</button>
            </div>

            <script>
                function kembaliKeAsal() {
                    // Cek jika halaman sebelumnya dari laporan, kembali ke laporan. Jika tidak, ke penjualan.
                    if (document.referrer.includes('laporan')) {
                        window.location.href = '../laporan/laporan.php';
                    } else {
                        window.location.href = 'penjualan.php';
                    }
                }
            </script>
        </div>

    </div>

</body>

</html>
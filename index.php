<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php?pesan=belum_login");
    exit();
}
$tgl = date('Y-m-d');
$role = $_SESSION['role'] ?? '';

$p = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM pelanggan"))['t'] ?? 0;
$pr = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM produk"))['t'] ?? 0;
$j = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as t FROM penjualan WHERE DATE(tgl_penjualan)='$tgl'"))['t'] ?? 0;
$u = ($role == 'admin') ? (mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM user"))['t'] ?? 0) : 0;

$q_tr = mysqli_query($koneksi, "SELECT p.*, pl.namapelanggan, u.nama AS nama_kasir 
    FROM penjualan p 
    LEFT JOIN pelanggan pl ON p.pelangganID = pl.pelangganID 
    LEFT JOIN user u ON p.userID = u.userID 
    WHERE DATE(p.tgl_penjualan) = '$tgl' ORDER BY p.penjualanID DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-2 bg-dark min-vh-100 p-3 text-white">
                <h5>SISTEM KASIR</h5><hr>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="index.php" class="nav-link text-white active bg-primary rounded">Dashboard</a></li>
                    <li class="nav-item mb-2"><a href="pelanggan/pelanggan.php" class="nav-link text-white">Pelanggan</a></li>
                    <li class="nav-item mb-2"><a href="produk/produk.php" class="nav-link text-white">Produk</a></li>
                    <li class="nav-item mb-2"><a href="penjualan/penjualan.php" class="nav-link text-white">Penjualan</a></li>
                    <?php if ($role == 'admin'): ?>
                        <li class="nav-item mb-2"><a href="user/user.php" class="nav-link text-white">User</a></li>
                        <li class="nav-item mb-2"><a href="laporan/laporan.php" class="nav-link text-white">Laporan</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- KONTEN -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Dashboard</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <!-- STATISTIK -->
                <div class="row g-3 mb-4">
                    <div class="<?= ($role == 'admin') ? 'col-md-3' : 'col-md-4'; ?>">
                        <div class="card border-0 shadow-sm border-start border-primary border-4 py-2">
                            <div class="card-body"><small class="text-muted fw-bold">TOTAL PELANGGAN</small><h3 class="fw-bold mt-1"><?= $p; ?></h3></div>
                        </div>
                    </div>
                    <div class="<?= ($role == 'admin') ? 'col-md-3' : 'col-md-4'; ?>">
                        <div class="card border-0 shadow-sm border-start border-success border-4 py-2">
                            <div class="card-body"><small class="text-muted fw-bold">TOTAL PRODUK</small><h3 class="fw-bold mt-1"><?= $pr; ?></h3></div>
                        </div>
                    </div>
                    <div class="<?= ($role == 'admin') ? 'col-md-3' : 'col-md-4'; ?>">
                        <div class="card border-0 shadow-sm border-start border-warning border-4 py-2">
                            <div class="card-body"><small class="text-muted fw-bold">PENJUALAN HARI INI</small><h3 class="fw-bold mt-1">Rp <?= number_format($j, 0, ',', '.'); ?></h3></div>
                        </div>
                    </div>
                    <?php if ($role == 'admin'): ?>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm border-start border-danger border-4 py-2">
                                <div class="card-body"><small class="text-muted fw-bold">TOTAL USER</small><h3 class="fw-bold mt-1"><?= $u; ?></h3></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TRANSAKSI -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Daftar Transaksi Hari Ini</h5>

                        <div style="max-height: 450px; overflow-y: auto;" class="pe-2">
                            <?php if (mysqli_num_rows($q_tr) > 0): $no = 1; while ($r = mysqli_fetch_assoc($q_tr)): $id_penjualan = $r['penjualanID']; ?>
                                <div class="card mb-3 border shadow-sm">
                                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                                        <div>
                                            <strong>#<?= $no++; ?> - ID: <?= $id_penjualan; ?></strong> | 
                                            <span class="text-warning"><?= date('d/m/Y H:i', strtotime($r['tgl_penjualan'])); ?></span>
                                        </div>
                                        <div>
                                            Pelanggan: <b><?= $r['namapelanggan'] ?? 'Umum'; ?></b> | 
                                            Kasir: <span class="badge bg-info text-dark"><?= $r['nama_kasir']; ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body bg-white p-3">
                                        <div class="table-responsive mb-2">
                                            <table class="table table-sm table-bordered mb-2 align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nama Produk</th>
                                                        <th>Qty</th>
                                                        <th>Harga Satuan</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $subtotal_hitung = 0;
                                                    $q_detail = mysqli_query($koneksi, "SELECT d.*, pr.namaproduk FROM detail_penjualan d JOIN produk pr ON d.produkID = pr.produkID WHERE d.penjualanID = '$id_penjualan'");
                                                    while ($d = mysqli_fetch_assoc($q_detail)) {
                                                        $hs = $d['jumlahproduk'] > 0 ? $d['subtotal'] / $d['jumlahproduk'] : 0;
                                                        $subtotal_hitung += $d['subtotal'];
                                                    ?>
                                                        <tr>
                                                            <td><?= $d['namaproduk']; ?></td>
                                                            <td><?= $d['jumlahproduk']; ?> pcs</td>
                                                            <td>Rp <?= number_format($hs, 0, ',', '.'); ?></td>
                                                            <td>Rp <?= number_format($d['subtotal'], 0, ',', '.'); ?></td>
                                                        </tr>
                                                    <?php } 
                                                    $diskon = $subtotal_hitung - $r['total_harga'];
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="d-flex justify-content-between text-muted small pt-1">
                                            <span>Subtotal:</span><span>Rp <?= number_format($subtotal_hitung, 0, ',', '.'); ?></span>
                                        </div>
                                        <?php if ($diskon > 0): ?>
                                            <div class="d-flex justify-content-between text-danger small">
                                                <span>Diskon:</span><span>-Rp <?= number_format($diskon, 0, ',', '.'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between fw-bold text-dark mt-1 border-top pt-1">
                                            <span>Total Akhir:</span><span class="text-success">Rp <?= number_format($r['total_harga'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; else: ?>
                                <div class="alert alert-secondary text-center">Belum ada transaksi hari ini.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
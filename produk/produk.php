<?php
session_start();
include "../config/koneksi.php";
/** @var mysqli $koneksi */ global $koneksi;

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../login.php");
    exit();
}
$sukses = $error = "";
$role = $_SESSION['role'] ?? '';

// Proses Tambah Produk (Khusus Admin)
if (isset($_POST['tambah'])) {
    if ($_SESSION['role'] != 'admin') {
        echo "<script>alert('Akses ditolak!'); window.location.href='produk.php';</script>";
        exit();
    }
    $namaproduk = $_POST['namaproduk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    if (!empty($namaproduk) && !empty($harga) && !empty($stok)) {
        mysqli_query($koneksi, "INSERT INTO produk (namaproduk, harga, stok) VALUES ('$namaproduk', '$harga', '$stok')") ? $sukses = "Produk berhasil ditambahkan!" : $error = "Gagal menyimpan!";
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}

// Proses Edit Produk (Khusus Admin)
if (isset($_POST['edit'])) {
    if ($_SESSION['role'] != 'admin') {
        echo "<script>alert('Akses ditolak!'); window.location.href='produk.php';</script>";
        exit();
    }
    $id = $_POST['id'];
    $namaproduk = $_POST['namaproduk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    mysqli_query($koneksi, "UPDATE produk SET namaproduk='$namaproduk', harga='$harga', stok='$stok' WHERE produkID='$id'") ? $sukses = "Produk berhasil diperbarui!" : $error = "Gagal update!";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Produk</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-2 bg-dark min-vh-100 p-3 text-white">
                <h5>SISTEM KASIR</h5>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="../index.php" class="nav-link text-white">Dashboard</a></li>
                    <li class="nav-item mb-2"><a href="../pelanggan/pelanggan.php" class="nav-link text-white">Pelanggan</a></li>
                    <li class="nav-item mb-2"><a href="produk.php" class="nav-link text-white active bg-warning rounded">Produk</a></li>
                    <li class="nav-item mb-2"><a href="../penjualan/penjualan.php" class="nav-link text-white">Penjualan</a></li>

                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item mb-2"><a href="../user/user.php" class="nav-link text-white">User</a></li>
                        <li class="nav-item mb-2"><a href="../laporan/laporan.php" class="nav-link text-white">Laporan</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Data Produk</h4>
                   <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <?php if ($sukses): ?><div class="alert alert-success alert-dismissible fade show"><?= $sukses; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

                <!-- TOMBOL TAMBAH (Hanya Admin) -->
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <button class="btn btn-success btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#tambahModal">+ Tambah Produk</button>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Daftar Produk / Barang</h5>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" id="cariProduk" class="form-control" placeholder="Cari nama/ID..." onkeyup="filterProduk()">
                            </div>
                        </div>

                        <div class="overflow-auto border rounded p-2 bg-white" style="max-height: 450px;">
                            <table class="table table-striped align-middle m-0" id="tabelProduk">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>No</th>
                                        <th>ID Produk</th>
                                        <th>Nama Produk</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <?php if ($_SESSION['role'] == 'admin'): ?>
                                            <th class="text-center">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    $q = mysqli_query($koneksi, "SELECT * FROM produk");
                                    if (mysqli_num_rows($q) > 0): while ($r = mysqli_fetch_assoc($q)): ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><span class="badge bg-secondary">#PRD-<?= $r['produkID']; ?></span></td>
                                                <td class="fw-bold"><?= $r['namaproduk']; ?></td>
                                                <td>Rp <?= number_format($r['harga'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <?php if ($r['stok'] > 0): ?>
                                                        <span class="badge bg-primary"><?= $r['stok']; ?> pcs</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Habis</span>
                                                    <?php endif; ?>
                                                </td>

                                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['produkID']; ?>">Edit</button>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>

                                            <!-- MODAL EDIT (Hanya Admin) -->
                                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                                <div class="modal fade" id="editModal<?= $r['produkID']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header bg-warning">
                                                                    <h5 class="modal-title">Edit Data Produk</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body text-start">
                                                                    <input type="hidden" name="id" value="<?= $r['produkID']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Nama Produk</label>
                                                                        <input type="text" name="namaproduk" class="form-control" value="<?= $r['namaproduk']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Harga (Rp)</label>
                                                                        <input type="number" name="harga" class="form-control" value="<?= $r['harga']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Stok</label>
                                                                        <input type="number" name="stok" class="form-control" value="<?= $r['stok']; ?>" required>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" name="edit" class="btn btn-warning">Simpan Perubahan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                        <?php endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Belum ada data produk.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH PRODUK (Hanya Admin) -->
    <?php if ($_SESSION['role'] == 'admin'): ?>
        <div class="modal fade" id="tambahModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Tambah Produk Baru</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Produk</label>
                                <input type="text" name="namaproduk" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harga (Rp)</label>
                                <input type="number" name="harga" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Stok</label>
                                <input type="number" name="stok" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="../asset/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterProduk() {
            let input = document.getElementById('cariProduk');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('tabelProduk');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let tdId = tr[i].getElementsByTagName('td')[1];
                let tdNama = tr[i].getElementsByTagName('td')[2];
                if (tdId || tdNama) {
                    let textId = tdId ? tdId.textContent || tdId.innerText : "";
                    let textNama = tdNama ? tdNama.textContent || tdNama.innerText : "";
                    if (textId.toLowerCase().indexOf(filter) > -1 || textNama.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>

</html>
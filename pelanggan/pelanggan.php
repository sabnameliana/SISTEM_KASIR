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

// Proses Tambah Member
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $telp = $_POST['telp'];

    if (!empty($nama) && !empty($telp)) {
        if (strlen($telp) < 9 || strlen($telp) > 13) {
            $error = "Nomor telepon harus di antara 9 sampai 13 digit!";
        } else {
            mysqli_query($koneksi, "INSERT INTO pelanggan (namapelanggan, alamat, nomer_telepon, status) VALUES ('$nama', '$alamat', '$telp', 'member')") ? $sukses = "Member berhasil ditambahkan!" : $error = "Gagal menyimpan!";
        }
    } else {
        $error = "Nama dan No Telepon wajib diisi!";
    }
}

// Proses Edit Member
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $telp = $_POST['notlp']; 

    if (strlen($telp) < 9 || strlen($telp) > 13) {
        $error = "Nomor telepon harus di antara 9 sampai 13 digit!";
    } else {
        mysqli_query($koneksi, "UPDATE pelanggan SET namapelanggan='$nama', alamat='$alamat', nomer_telepon='$telp' WHERE pelangganID='$id'") ? $sukses = "Member berhasil diperbarui!" : $error = "Gagal update!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Pelanggan</title>
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
                    <li class="nav-item mb-2"><a href="pelanggan.php" class="nav-link text-white active bg-danger rounded">Pelanggan</a></li>
                    <li class="nav-item mb-2"><a href="../produk/produk.php" class="nav-link text-white">Produk</a></li>
                    <li class="nav-item mb-2"><a href="../penjualan/penjualan.php" class="nav-link text-white">Penjualan</a></li>
                    
                    <!-- Menu khusus Admin -->
                    <?php if ($role == 'admin'): ?>
                        <li class="nav-item mb-2"><a href="../user/user.php" class="nav-link text-white">User</a></li>
                        <li class="nav-item mb-2"><a href="../laporan/laporan.php" class="nav-link text-white">Laporan</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">
                <!-- HEADER -->
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Data Pelanggan</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <!-- ALERT NOTIFIKASI -->
                <?php if ($sukses): ?><div class="alert alert-success alert-dismissible fade show"><?= $sukses; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

                <!-- TOMBOL TAMBAH -->
                <button class="btn btn-success btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#tambahModal">+ Tambah Member</button>

                <!-- TABEL & PENCARIAN -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Daftar Member</h5>

                        <!-- KOTAK PENCARIAN -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" id="cariMember" class="form-control" placeholder="Cari nama/ID..." onkeyup="filterMember()">
                            </div>
                        </div>

                        <!-- BUNGKUS SCROLL -->
                        <div class="overflow-auto border rounded p-2 bg-white" style="max-height: 450px;">
                            <table class="table table-striped align-middle m-0" id="tabelMember">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>No</th>
                                        <th>ID Pelanggan</th>
                                        <th>Nama Member</th>
                                        <th>Alamat</th>
                                        <th>No. Telepon</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    $q = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE status = 'member'");
                                    if (mysqli_num_rows($q) > 0): while ($r = mysqli_fetch_assoc($q)): ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><span class="badge bg-secondary">#PLG-<?= $r['pelangganID']; ?></span></td>
                                                <td class="fw-bold"><?= $r['namapelanggan']; ?></td>
                                                <td><?= $r['alamat']; ?></td>
                                                <td><?= $r['nomer_telepon']; ?></td>
                                                <td><span class="badge bg-success">Member</span></td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['pelangganID']; ?>">Edit</button>
                                                </td>
                                            </tr>

                                            <!-- MODAL EDIT -->
                                            <div class="modal fade" id="editModal<?= $r['pelangganID']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header bg-warning">
                                                                <h5 class="modal-title">Edit Data Member</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <input type="hidden" name="id" value="<?= $r['pelangganID']; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nama Member</label>
                                                                    <input type="text" name="nama" class="form-control" value="<?= $r['namapelanggan']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Alamat</label>
                                                                    <textarea name="alamat" class="form-control"><?= $r['alamat']; ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">No Telepon</label>
                                                                    <input type="text" name="notlp" minlength="9" maxlength="13"
                                                                        value="<?= $r['nomer_telepon']; ?>" class="form-control"
                                                                        oninput="this.value=this.value.replace(/\D/g,'')" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" name="edit" class="btn btn-warning">Simpan Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Belum ada data member.</td>
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

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="tambahModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Tambah Member Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Member</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No Telepon</label>
                            <input type="text" name="telp" minlength="9" maxlength="13"
                                class="form-control" oninput="this.value=this.value.replace(/\D/g,'')" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../asset/js/bootstrap.bundle.min.js"></script>

    <!-- SCRIPT JAVASCRIPT PENCARIAN MEMBER -->
    <script>
        function filterMember() {
            let input = document.getElementById('cariMember');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('tabelMember');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let tdId = tr[i].getElementsByTagName('td')[1];
                let tdNama = tr[i].getElementsByTagName('td')[2];

                if (tdId || tdNama) {
                    let textId = tdId ? tdId.textContent || tdId.innerText : "";
                    let textNama = tdNama ? tdNama.textContent || tdNama.innerText : "";

                    if (textId.toLowerCase().indexOf(filter) > -1 ||
                        textNama.toLowerCase().indexOf(filter) > -1) {
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
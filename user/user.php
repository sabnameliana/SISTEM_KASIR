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

$sukses = $error = "";
$role = $_SESSION['role'] ?? '';

// 1. PROSES TAMBAH / EDIT
if (isset($_POST['simpan'])) {
    $id   = $_POST['userID'];
    $nama = $_POST['nama'];
    $user = $_POST['username'];
    $password_input = $_POST['password'];
    
    if (empty($id)) {
        // Tambah user baru
        if (!empty($nama) && !empty($user) && !empty($password_input)) {
            $password = md5($password_input);
            $q = mysqli_query($koneksi, "INSERT INTO user (nama, username, password) VALUES ('$nama', '$user', '$password')");
            $q ? $sukses = "User berhasil ditambahkan!" : $error = "Gagal menyimpan!";
        } else {
            $error = "Semua kolom termasuk password wajib diisi!";
        }
    } else {
        // Edit user
        if (!empty($nama) && !empty($user)) {
            if (!empty($password_input)) {
                $password = md5($password_input);
                $q = mysqli_query($koneksi, "UPDATE user SET nama='$nama', username='$user', password='$password' WHERE userID='$id'");
            } else {
                $q = mysqli_query($koneksi, "UPDATE user SET nama='$nama', username='$user' WHERE userID='$id'");
            }
            $q ? $sukses = "User berhasil diperbarui!" : $error = "Gagal update!";
        } else {
            $error = "Nama dan Username wajib diisi!";
        }
    }
}

// 2. PROSES HAPUS
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    if (mysqli_query($koneksi, "DELETE FROM user WHERE userID='$id_hapus'")) {
        $sukses = "User berhasil dihapus!";
    } else {
        $error = "Gagal menghapus user!";
    }
}

$query = mysqli_query($koneksi, "SELECT * FROM user ORDER BY userID DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data User</title>
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
                    <li class="nav-item mb-2"><a href="../produk/produk.php" class="nav-link text-white">Produk</a></li>
                    <li class="nav-item mb-2"><a href="../penjualan/penjualan.php" class="nav-link text-white">Penjualan</a></li>
                    <li class="nav-item mb-2"><a href="user.php" class="nav-link text-white active bg-info rounded">User</a></li>
                    <li class="nav-item mb-2"><a href="../laporan/laporan.php" class="nav-link text-white">Laporan</a></li>
                </ul>
            </div>

            <div class="col-md-10 p-4">
                <!-- HEADER -->
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Data User</h4>
                   <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <?php if ($sukses): ?><div class="alert alert-success alert-dismissible fade show"><?= $sukses; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

                <!-- TOMBOL TAMBAH -->
                <button class="btn btn-success btn-sm text-white mb-3" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="setModal('', '', '')">+ Tambah User</button>

                <!-- TABEL -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Daftar User / Kasir</h5>
                        
                        <!-- PENCARIAN -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" id="cariUser" class="form-control" placeholder="Cari nama/ID/username..." onkeyup="filterUser()">
                            </div>
                        </div>

                        <!-- SCROLL -->
                        <div class="overflow-auto border rounded p-2 bg-white" style="max-height: 450px;">
                            <table class="table table-striped align-middle m-0" id="tabelUser">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>No</th>
                                        <th>ID User</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    $q = mysqli_query($koneksi, "SELECT * FROM user ORDER BY userID DESC");
                                    if (mysqli_num_rows($q) > 0): while ($u = mysqli_fetch_assoc($q)): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><span class="badge bg-secondary">#USR-<?= $u['userID']; ?></span></td>
                                            <td class="fw-bold"><?= $u['nama']; ?></td>
                                            <td><?= $u['username']; ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="setModal('<?= $u['userID']; ?>', '<?= $u['nama']; ?>', '<?= $u['username']; ?>')">Edit</button>
                                                <a href="user.php?hapus=<?= $u['userID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus user?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Belum ada data user.</td>
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

    <!-- MODAL FORM -->
    <div class="modal fade" id="modalUser" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="mTitle">Tambah User Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <input type="hidden" name="userID" id="mID">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" id="mNama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="mUser" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="mPass" class="form-control">
                            <small class="text-muted" id="passInfo">Kosongkan jika tidak ingin mengubah password saat edit.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="simpan" class="btn btn-success ">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../asset/js/bootstrap.bundle.min.js"></script>

    <!-- SCRIPT MODAL & PENCARIAN USER -->
    <script>
    function setModal(id, nama, user) {
        document.getElementById('mTitle').innerText = id ? 'Edit User' : 'Tambah User Baru';
        document.getElementById('mID').value = id;
        document.getElementById('mNama').value = nama;
        document.getElementById('mUser').value = user;
        document.getElementById('mPass').value = '';
        
        // Atur agar password wajib diisi saat tambah, tapi opsional saat edit
        let passInput = document.getElementById('mPass');
        let passInfo = document.getElementById('passInfo');
        if(!id) {
            passInput.required = true;
            passInfo.style.display = 'none';
        } else {
            passInput.required = false;
            passInfo.style.display = 'block';
        }
    }

    function filterUser() {
        let input = document.getElementById('cariUser');
        let filter = input.value.toLowerCase();
        let table = document.getElementById('tabelUser');
        let tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            let tdId = tr[i].getElementsByTagName('td')[1];     // Kolom ID User
            let tdNama = tr[i].getElementsByTagName('td')[2];   // Kolom Nama Lengkap
            let tdUser = tr[i].getElementsByTagName('td')[3];   // Kolom Username

            if (tdId || tdNama || tdUser) {
                let textId = tdId ? tdId.textContent || tdId.innerText : "";
                let textNama = tdNama ? tdNama.textContent || tdNama.innerText : "";
                let textUser = tdUser ? tdUser.textContent || tdUser.innerText : "";

                if (textId.toLowerCase().indexOf(filter) > -1 || 
                    textNama.toLowerCase().indexOf(filter) > -1 || 
                    textUser.toLowerCase().indexOf(filter) > -1) {
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
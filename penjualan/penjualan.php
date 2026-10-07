<?php
session_start();
include "../config/koneksi.php";
/** @var mysqli $koneksi */ global $koneksi;

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../login.php");
    exit();
}

$sukses = $error = "";
$role = $_SESSION['role'] ?? '';

// PROSES CHECKOUT TRANSAKSI
if (isset($_POST['checkout'])) {
    $tanggal     =$_POST['tanggal_penjualan'];
    $pel_input   =$_POST['pelanggan_input'];
    $total_harga =$_POST['total_harga'];
    $bayar       =$_POST['bayar'];

    if ($bayar < $total_harga) {
        echo "<script>
            alert('Transaksi gagal! Uang pembayaran kurang dari total belanja.');
            window.history.back();
        </script>";
        exit();
    }

    $_SESSION['bayar_sementara']     =$bayar;
    $_SESSION['kembalian_sementara'] = $bayar -$total_harga;

    // Ambil ID User
    $userID =$_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['userid'] ?? 1;
    if (!isset($_SESSION['user_id']) && isset($_SESSION['nama'])) {$nm = mysqli_real_escape_string($koneksi,$_SESSION['nama']);
        $q_user = mysqli_query($koneksi, "SELECT userID FROM user WHERE nama = '$nm' LIMIT 1");
        if ($u = mysqli_fetch_array($q_user)) $userID =$u['userID'];
    }

    // Ambil ID Pelanggan
    $pelangganID = "NULL";
    if (!empty($pel_input)) {
        $id_pilih = trim(explode(' - ',$pel_input)[0]);
        $cek_pel = mysqli_query($koneksi, "SELECT pelangganID FROM pelanggan WHERE pelangganID = '$id_pilih'");
        if (mysqli_num_rows($cek_pel) > 0) $pelangganID = "'$id_pilih'";
    }

    // Insert Penjualan Utama (Tanggal dari form + Jam saat ini secara real-time)
    $waktu_sekarang = date('H:i:s');$tgl_dengan_waktu = $tanggal . ' ' .$waktu_sekarang;

    $q_penjualan = "INSERT INTO penjualan (tgl_penjualan, total_harga, pelangganID, userID) VALUES ('$tgl_dengan_waktu', '$total_harga', $pelangganID, '$userID')";

    if (mysqli_query($koneksi,$q_penjualan)) {
        $penjualanID = mysqli_insert_id($koneksi);
        $p_ids   =$_POST['produk_id'];
        $qtys    =$_POST['jumlah_produk'];
        $hargas  =$_POST['harga'];

        for ($i = 0; $i < count($p_ids); $i++) {$qty = intval($qtys[$i]);
            if ($qty > 0) {
                $sub =$qty * $hargas[$i];
                mysqli_query($koneksi, "INSERT INTO detail_penjualan (penjualanID, produkID, jumlahproduk, subtotal) VALUES ('$penjualanID', '{$p_ids[$i]}', '$qty', '$sub')");
                mysqli_query($koneksi, "UPDATE produk SET stok = stok - $qty WHERE produkID = '{$p_ids[$i]}'");
            }
        }
        echo "<script>alert('Transaksi Berhasil!'); window.location.href='nota.php?id=$penjualanID&bayar=$bayar';</script>";
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($koneksi) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Transaksi Penjualan</title>
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
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
                    <li class="nav-item mb-2"><a href="penjualan.php" class="nav-link text-white bg-success rounded">Penjualan</a></li>
                    <?php if (isset($_SESSION['role']) &&$_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item mb-2"><a href="../user/user.php" class="nav-link text-white">User</a></li>
                        <li class="nav-item mb-2"><a href="../laporan/laporan.php" class="nav-link text-white">Laporan</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Penjualan</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <form method="POST" id="formKasir" autocomplete="off">
                    <div class="row">
                        <!-- KIRI -->
                        <div class="col-md-8">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-dark text-white p-3">
                                    <h4 class="fw-bold m-0"><i class="bi bi-cart-plus"></i> Form Transaksi Kasir</h4>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label>Tanggal Transaksi</label>
                                            <input type="date" name="tanggal_penjualan" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-8 mb-3">
                                            <label>Pilih Pelanggan (Member)</label>
                                            <input type="text" id="pelanggan_input" name="pelanggan_input" list="daftar_pelanggan" class="form-control" placeholder="Ketik Nama/ID (Kosongkan jika Non-Member)" oninput="hitungTotal()">
                                            <datalist id="daftar_pelanggan">
                                                <?php
                                                $pel = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE status IN ('member', 'Member')");
                                                while ($p = mysqli_fetch_array($pel)) {
                                                    echo "<option value='{$p['pelangganID']} - {$p['namapelanggan']}'></option>";
                                                }
                                                ?>
                                            </datalist>
                                        </div>
                                    </div>
                                    <hr>

                                    <!-- KOTAK KERANJANG / PRODUK YANG DIPILIH -->
                                    <div class="mb-4">
                                        <h5 class="fw-bold text-success"><i class="bi bi-cart-check"></i> Produk yang Dibeli:</h5>
                                        <div class="table-responsive border rounded bg-white p-2" style="max-height: 180px; overflow-y: auto;">
                                            <table class="table table-sm table-striped m-0">
                                                <thead>
                                                    <tr class="table-success">
                                                        <th>Nama Produk</th>
                                                        <th>Harga Satuan</th>
                                                        <th>Qty</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="keranjangBody">
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Belum ada produk yang dipilih.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="input-group mb-3">
                                        <span class="input-group-text bg-dark text-white"><i class="bi bi-search"></i> Cari</span>
                                        <input type="text" id="inputPencarian" class="form-control" placeholder="Cari nama produk..." onkeyup="filterProduk()">
                                    </div>
                                    <h4 class="fw-bold mb-3">Pilih Produk:</h4>

                                    <!-- KOTAK SCROLL DAFTAR PRODUK -->
                                    <div class="overflow-auto border rounded p-2 bg-white" style="max-height: 280px;">
                                        <div class="table-responsive m-0">
                                            <table class="table table-bordered table-hover align-middle m-0" id="tabelProduk">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Nama Produk</th>
                                                        <th>Harga</th>
                                                        <th>Stok</th>
                                                        <th width="20%">Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $prod = mysqli_query($koneksi, "SELECT * FROM produk WHERE stok > 0");
                                                    if (mysqli_num_rows($prod) > 0) {
                                                        while ($pr = mysqli_fetch_array($prod)) {
                                                            echo "<tr>
                                                                <td><b>{$pr['namaproduk']}</b></td>
                                                                <td>Rp. " . number_format($pr['harga'], 0, ',', '.') . "</td>
                                                                <td><span class='badge bg-success'>{$pr['stok']} pcs</span></td>
                                                                <td>
                                                                    <input type='hidden' name='produk_id[]' value='{$pr['produkID']}'>
                                                                    <input type='hidden' class='nama-item' value='{$pr['namaproduk']}'>
                                                                    <input type='hidden' class='harga-item' name='harga[]' value='{$pr['harga']}'>
                                                                    <input type='number' name='jumlah_produk[]' class='form-control qty-item' min='0' max='{$pr['stok']}' value='0' oninput='hitungTotal()'>
                                                                </td>
                                                            </tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='4' class='text-center text-danger'>Stok kosong!</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- KANAN (RINGKASAN) -->
                        <div class="col-md-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-dark text-white p-3">
                                    <h4 class="fw-bold m-0"><i class="bi bi-calculator"></i> Ringkasan</h4>
                                </div>
                                <div class="card-body p-3 bg-light">
                                    <div class="bg-dark text-success p-3 text-end rounded mb-3 font-monospace">
                                        <small class="text-light">TOTAL BAYAR</small>
                                        <h2 class="m-0 fw-bold" id="display_total_bayar">Rp 0</h2>
                                    </div>
                                    <div class="mb-3">
                                        <label>Subtotal</label>
                                        <input type="text" id="subtotal_text" class="form-control" value="Rp 0" readonly>
                                        <input type="hidden" id="subtotal_val" name="subtotal" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label>Diskon Member (10%)</label>
                                        <input type="text" id="diskon_text" class="form-control text-danger fw-bold" value="Rp 0" readonly>
                                        <input type="hidden" id="diskon_val" name="diskon" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label>Total Akhir</label>
                                        <input type="text" id="total_akhir_text" class="form-control fw-bold" value="Rp 0" readonly>
                                        <input type="hidden" id="total_akhir_val" name="total_harga" value="0">
                                    </div>
                                    <hr>
                                    <div class="mb-3">
                                        <label>Uang Diterima (Bayar)</label>
                                        <input type="number" id="bayar_val" name="bayar" class="form-control form-control-lg" placeholder="Nominal" oninput="hitungKembalian()" required>
                                        <small id="pesan_uang" class="fw-bold"></small>
                                    </div>
                                    <div class="mb-3">
                                        <label>Kembalian</label>
                                        <input type="text" id="kembalian_text" class="form-control form-control-lg fw-bold" value="Rp 0" readonly>
                                    </div>
                                    <button type="submit" name="checkout" class="btn btn-success btn-lg w-100 fw-bold">
                                        <i class="bi bi-printer"></i> PROSES TRANSAKSI
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- JAVASCRIPT -->
                <script>
                    function filterProduk() {
                        let filter = document.getElementById('inputPencarian').value.toLowerCase();
                        let tr = document.getElementById('tabelProduk').getElementsByTagName('tr');
                        for (let i = 1; i < tr.length; i++) {
                            let td = tr[i].getElementsByTagName('td')[0];
                            if (td) {
                                let text = td.textContent || td.innerText;
                                tr[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? "" : "none";
                            }
                        }
                    }

                    function hitungTotal() {
                        let hargas = document.querySelectorAll('.harga-item');
                        let qtys = document.querySelectorAll('.qty-item');
                        let namas = document.querySelectorAll('.nama-item');
                        
                        let subtotal = 0;
                        let keranjangHtml = "";
                        let adaItem = false;

                        for (let i = 0; i < qtys.length; i++) {
                            let qtyVal = parseFloat(qtys[i].value) || 0;
                            if (qtyVal > 0) {
                                adaItem = true;
                                let hargaVal = parseFloat(hargas[i].value) || 0;
                                let namaVal = namas[i].value;
                                let subItem = qtyVal * hargaVal;
                                subtotal += subItem;

                                keranjangHtml += `<tr>
                                    <td><b>${namaVal}</b></td>
                                    <td>Rp ${hargaVal.toLocaleString('id-ID')}</td>
                                    <td>${qtyVal}</td>
                                    <td>Rp ${subItem.toLocaleString('id-ID')}</td>
                                </tr>`;
                            }
                        }

                        let tbody = document.getElementById('keranjangBody');
                        if (adaItem) {
                            tbody.innerHTML = keranjangHtml;
                        } else {
                            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Belum ada produk yang dipilih.</td></tr>`;
                        }

                        let isMember = document.getElementById('pelanggan_input').value.trim() !== "";
                        let diskon = isMember ? subtotal * 0.10 : 0;
                        let totalAkhir = subtotal - diskon;

                        document.getElementById('subtotal_val').value = subtotal;
                        document.getElementById('subtotal_text').value = "Rp " + subtotal.toLocaleString('id-ID');
                        document.getElementById('diskon_val').value = diskon;
                        document.getElementById('diskon_text').value = "- Rp " + diskon.toLocaleString('id-ID');
                        document.getElementById('total_akhir_val').value = totalAkhir;
                        document.getElementById('total_akhir_text').value = "Rp " + totalAkhir.toLocaleString('id-ID');
                        document.getElementById('display_total_bayar').innerText = "Rp " + totalAkhir.toLocaleString('id-ID');

                        hitungKembalian();
                    }

                    function hitungKembalian() {
                        let total = parseFloat(document.getElementById('total_akhir_val').value) || 0;
                        let bayar = parseFloat(document.getElementById('bayar_val').value) || 0;
                        let kembalian = bayar - total;
                        let el = document.getElementById('kembalian_text');
                        let pesanUang = document.getElementById('pesan_uang');

                        el.classList.remove('text-success', 'text-danger', 'text-dark');
                        pesanUang.textContent = ""; // Reset teks pesan

                        if (bayar === 0 || isNaN(bayar)) {
                            el.value = "Rp 0";
                            el.classList.add('text-dark');
                        } else if (kembalian < 0) {
                            el.value = "Uang Kurang!";
                            el.classList.add('text-danger');
                            pesanUang.textContent = "⚠️ Uang pembayaran kurang!";
                            pesanUang.className = "text-danger fw-bold";
                        } else {
                            el.value = "Rp " + kembalian.toLocaleString('id-ID');
                            el.classList.add('text-success');
                            pesanUang.textContent = "✔ Uang cukup";
                            pesanUang.className = "text-success fw-bold";
                        }
                    }
                </script>
            </div>
        </div>
    </div>
</body>

</html>
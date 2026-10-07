<?php
session_start();
include "../config/koneksi.php";
/** @var mysqli $koneksi */ global $koneksi;

// Cek apakah user sudah login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:../login.php");
    exit();
}

// Cek apakah ada parameter ID penjualan yang dikirim lewat URL
if (isset($_GET['id'])) {
    $penjualanID = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 1. Ambil data produk dan jumlah yang dibeli pada transaksi ini untuk dikembalikan stoknya
    $q_detail = mysqli_query($koneksi, "SELECT produkID, jumlahproduk FROM detail_penjualan WHERE penjualanID = '$penjualanID'");
    
    while ($row = mysqli_fetch_array($q_detail)) {
        $prodID = $row['produkID'];
        $qtyBeli = $row['jumlahproduk'];

        // Kembalikan stok produk ke database (Stok saat ini + Qty yang tadinya dibeli)
        mysqli_query($koneksi, "UPDATE produk SET stok = stok + $qtyBeli WHERE produkID = '$prodID'");
    }

    // 2. Hapus data dari tabel detail_penjualan terlebih dahulu (menghindari error relasi foreign key)
    mysqli_query($koneksi, "DELETE FROM detail_penjualan WHERE penjualanID = '$penjualanID'");

    // 3. Hapus data utama dari tabel penjualan
    $hapus_penjualan = mysqli_query($koneksi, "DELETE FROM penjualan WHERE penjualanID = '$penjualanID'");

    // Cek apakah proses hapus berhasil dan kembalikan ke halaman laporan
    if ($hapus_penjualan) {
        echo "<script>
            alert('Transaksi berhasil dibatalkan dan stok produk telah dikembalikan!');
            window.location.href='../laporan/laporan.php'; 
        </script>";
    } else {
        echo "<script>
            alert('Gagal membatalkan transaksi: " . mysqli_error($koneksi) . "');
            window.location.href='../laporan/laporan.php'; 
        </script>";
    }
} else {
    header("location:../laporan/laporan.php");
}
?>
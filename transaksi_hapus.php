<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    $trx = $stmt->fetch();

    if ($trx) {
        // Hapus file bukti fisik jika ada
        if (!empty($trx['bukti_transaksi'])) {
            $filePath = __DIR__ . '/uploads/' . $trx['bukti_transaksi'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $stmtDelete = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
        $stmtDelete->execute([$id]);

        set_flash('success', "Transaksi <strong>{$trx['kode_transaksi']}</strong> berhasil dihapus.");
    } else {
        set_flash('danger', 'Data transaksi tidak ditemukan.');
    }
}

header('Location: transaksi.php');
exit;


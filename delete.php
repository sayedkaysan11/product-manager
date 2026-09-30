<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// Delete hanya boleh lewat POST, bukan link biasa, supaya tidak terpicu
// tidak sengaja (misalnya lewat prefetch browser atau crawler).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrfValid($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Sesi form sudah tidak valid, silakan coba lagi.');
    header('Location: index.php');
    exit;
}

$id  = (int) ($_POST['id'] ?? 0);
$pdo = getPDO();

// Syarat query: DELETE -> $pdo->prepare(...)
$stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);

if ($stmt->rowCount() > 0) {
    setFlash('success', 'Produk berhasil dihapus.');
} else {
    setFlash('error', 'Produk tidak ditemukan atau sudah dihapus.');
}

header('Location: index.php');
exit;

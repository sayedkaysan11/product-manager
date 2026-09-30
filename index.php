<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pdo = getPDO();

// ── Proses form tambah produk (Create) ──────────────────────────────
// Pola Post-Redirect-Get: setelah POST diproses, selalu redirect ke
// index.php. Ini yang mencegah data ganda kalau halaman di-refresh,
// karena browser tidak akan mengirim ulang form saat reload.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Sesi form sudah tidak valid, silakan coba lagi.');
        header('Location: index.php');
        exit;
    }

    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = trim($_POST['price'] ?? '');
    $stock    = trim($_POST['stock'] ?? '');

    $errors = validateProduct($pdo, $name, $category, $price, $stock);

    if (empty($errors)) {
        // Syarat query: INSERT -> $pdo->prepare(...)
        $stmt = $pdo->prepare(
            'INSERT INTO products (name, category, price, stock) VALUES (:name, :category, :price, :stock)'
        );
        $stmt->execute([
            'name'     => $name,
            'category' => $category,
            'price'    => (float) $price,
            'stock'    => (int) $stock,
        ]);

        setFlash('success', 'Produk "' . $name . '" berhasil ditambahkan.');
    } else {
        $_SESSION['old_input']  = compact('name', 'category', 'price', 'stock');
        $_SESSION['old_errors'] = $errors;
        setFlash('error', 'Produk gagal disimpan, periksa lagi isian form.');
    }

    header('Location: index.php');
    exit;
}

// Ambil data lama (kalau form sebelumnya gagal validasi) untuk mengisi ulang form
$old    = $_SESSION['old_input'] ?? ['name' => '', 'category' => '', 'price' => '', 'stock' => ''];
$errors = $_SESSION['old_errors'] ?? [];
unset($_SESSION['old_input'], $_SESSION['old_errors']);

$flash = getFlash();

// Syarat query: SELECT semua produk, urut dari yang terbaru
$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();

$totalNilai = 0;
foreach ($products as $p) {
    $totalNilai += $p['price'] * $p['stock'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Product Manager</title>
<style>
    :root {
        --ink: #16232e;
        --ink-muted: #5c6b77;
        --garis: #d6dde2;
        --kertas: #ffffff;
        --latar: #eceff1;
        --aksen: #2f6fed;
        --aksen-hover: #2559c4;
        --bahaya: #b23b3b;
        --sukses: #1c7a4d;
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        padding: 32px 20px 64px;
        background: var(--latar);
        color: var(--ink);
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        line-height: 1.55;
    }

    .wadah { max-width: 1080px; margin: 0 auto; }

    h1 { font-size: 1.75rem; font-weight: 650; margin: 0 0 4px; }
    .keterangan { color: var(--ink-muted); margin: 0 0 24px; max-width: 62ch; }

    .ringkasan {
        display: flex; flex-wrap: wrap; gap: 1px;
        background: var(--garis); border: 1px solid var(--garis);
        margin-bottom: 24px;
    }
    .ringkasan div { flex: 1 1 200px; background: var(--kertas); padding: 14px 18px; }
    .ringkasan dt { font-size: 0.82rem; color: var(--ink-muted); margin-bottom: 4px; }
    .ringkasan dd { margin: 0; font-size: 1.25rem; font-weight: 600; }

    .flash {
        padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;
        font-size: 0.92rem; font-weight: 500;
    }
    .flash.success { background: #e6f4ec; color: var(--sukses); border: 1px solid #b9e3c8; }
    .flash.error   { background: #fbeaea; color: var(--bahaya); border: 1px solid #f0c1c1; }

    .panel {
        background: var(--kertas); border: 1px solid var(--garis);
        border-radius: 8px; padding: 20px 22px; margin-bottom: 28px;
    }
    .panel h2 { font-size: 1.05rem; margin: 0 0 16px; }

    .grid-form {
        display: grid; gap: 14px;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        align-items: start;
    }

    label { display: block; font-size: 0.85rem; color: var(--ink-muted); margin-bottom: 5px; }
    input, select {
        width: 100%; padding: 9px 10px; border: 1px solid var(--garis);
        border-radius: 5px; font-size: 0.95rem; font-family: inherit;
    }
    input:focus, select:focus { outline: 2px solid var(--aksen); outline-offset: 1px; }
    .field-error { color: var(--bahaya); font-size: 0.8rem; margin-top: 4px; }

    .btn {
        display: inline-block; padding: 9px 18px; border-radius: 5px;
        border: none; font-size: 0.92rem; font-weight: 600; cursor: pointer;
        text-decoration: none;
    }
    .btn-primary { background: var(--aksen); color: #fff; }
    .btn-primary:hover { background: var(--aksen-hover); }
    .btn-row { margin-top: 4px; }

    .cards {
        display: grid; gap: 16px;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
    .card {
        background: var(--kertas); border: 1px solid var(--garis);
        border-radius: 8px; padding: 16px 18px; position: relative;
    }
    .card.is-kritis { border-left: 4px solid #d97a2e; }
    .card.is-habis  { border-left: 4px solid var(--bahaya); }

    .card .kategori {
        display: inline-block; font-size: 0.75rem; color: var(--ink-muted);
        background: #f1f3f5; padding: 2px 8px; border-radius: 999px; margin-bottom: 8px;
    }
    .card h3 { margin: 0 0 6px; font-size: 1.02rem; }
    .card .harga { font-weight: 600; margin-bottom: 4px; }
    .card .stok { font-size: 0.88rem; color: var(--ink-muted); margin-bottom: 14px; }
    .card .stok.kritis { color: #b1651f; font-weight: 600; }
    .card .stok.habis  { color: var(--bahaya); font-weight: 600; }

    .card-aksi { display: flex; gap: 8px; }
    .card-aksi a, .card-aksi button {
        font-size: 0.83rem; padding: 6px 12px; border-radius: 5px;
        border: 1px solid var(--garis); background: #fff; cursor: pointer;
        text-decoration: none; color: var(--ink);
    }
    .card-aksi .btn-hapus { color: var(--bahaya); border-color: #f0c1c1; }
    .card-aksi .btn-hapus:hover { background: #fbeaea; }
    .card-aksi a:hover { background: #f4f6f7; }

    .kosong { color: var(--ink-muted); padding: 20px 0; text-align: center; }
</style>
</head>
<body>
<div class="wadah">

    <h1>Product Manager</h1>
    <p class="keterangan">
        Kelola data produk: tambah, lihat, ubah, dan hapus. Data tersimpan di database MySQL.
    </p>

    <?php if ($flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <dl class="ringkasan">
        <div><dt>Jumlah produk</dt><dd><?= count($products) ?></dd></div>
        <div><dt>Total nilai stok</dt><dd><?= formatRupiah($totalNilai) ?></dd></div>
    </dl>

    <div class="panel">
        <h2>Tambah produk baru</h2>
        <form method="post" action="index.php" novalidate>
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="grid-form">
                <div>
                    <label for="name">Nama produk</label>
                    <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" maxlength="100">
                    <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
                </div>
                <div>
                    <label for="category">Kategori</label>
                    <select id="category" name="category">
                        <option value="">— pilih —</option>
                        <?php foreach (KATEGORI_PRODUK as $kat): ?>
                            <option value="<?= e($kat) ?>" <?= $old['category'] === $kat ? 'selected' : '' ?>><?= e($kat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['category'])): ?><div class="field-error"><?= e($errors['category']) ?></div><?php endif; ?>
                </div>
                <div>
                    <label for="price">Harga (Rp)</label>
                    <input type="number" id="price" name="price" value="<?= e($old['price']) ?>" min="1" step="1">
                    <?php if (!empty($errors['price'])): ?><div class="field-error"><?= e($errors['price']) ?></div><?php endif; ?>
                </div>
                <div>
                    <label for="stock">Stok</label>
                    <input type="number" id="stock" name="stock" value="<?= e($old['stock']) ?>" min="0" step="1">
                    <?php if (!empty($errors['stock'])): ?><div class="field-error"><?= e($errors['stock']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary">Simpan produk</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>Daftar produk</h2>

        <?php if (empty($products)): ?>
            <p class="kosong">Belum ada produk. Tambahkan lewat form di atas.</p>
        <?php else: ?>
            <div class="cards">
                <?php foreach ($products as $p):
                    $stok = (int) $p['stock'];
                    $kelasKartu = $stok === 0 ? 'is-habis' : ($stok < 3 ? 'is-kritis' : '');
                    $kelasStok  = $stok === 0 ? 'habis' : ($stok < 3 ? 'kritis' : '');
                    $labelStok  = $stok === 0 ? 'Stok habis' : ($stok < 3 ? 'Stok menipis' : 'Stok aman');
                ?>
                <div class="card <?= $kelasKartu ?>">
                    <span class="kategori"><?= e($p['category']) ?></span>
                    <h3><?= e($p['name']) ?></h3>
                    <div class="harga"><?= formatRupiah($p['price']) ?></div>
                    <div class="stok <?= $kelasStok ?>"><?= $stok ?> unit &middot; <?= $labelStok ?></div>

                    <div class="card-aksi">
                        <a href="edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
                        <form method="post" action="delete.php" onsubmit="return confirm('Hapus produk ini?');">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <button type="submit" class="btn-hapus">Hapus</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>

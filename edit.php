<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pdo = getPDO();

// ── Proses form update ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id = (int) ($_POST['id'] ?? 0);

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Sesi form sudah tidak valid, silakan coba lagi.');
        header('Location: index.php');
        exit;
    }

    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price    = trim($_POST['price'] ?? '');
    $stock    = trim($_POST['stock'] ?? '');

    $errors = validateProduct($pdo, $name, $category, $price, $stock, $id);

    if (empty($errors)) {
        // Syarat query: UPDATE -> $pdo->prepare(...)
        $stmt = $pdo->prepare(
            'UPDATE products SET name = :name, category = :category, price = :price, stock = :stock WHERE id = :id'
        );
        $stmt->execute([
            'name'     => $name,
            'category' => $category,
            'price'    => (float) $price,
            'stock'    => (int) $stock,
            'id'       => $id,
        ]);

        setFlash('success', 'Produk "' . $name . '" berhasil diperbarui.');
        header('Location: index.php');
        exit;
    }

    // Kalau gagal validasi, tetap di halaman edit dan tampilkan errornya
    $_SESSION['old_input']  = compact('name', 'category', 'price', 'stock');
    $_SESSION['old_errors'] = $errors;
    header('Location: edit.php?id=' . $id);
    exit;
}

// ── Ambil data produk yang mau diedit ───────────────────────────────
$id = (int) ($_GET['id'] ?? 0);

// Syarat query: SELECT by ID -> $pdo->prepare(...)
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Produk tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$old    = $_SESSION['old_input'] ?? $product;
$errors = $_SESSION['old_errors'] ?? [];
unset($_SESSION['old_input'], $_SESSION['old_errors']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Produk - Product Manager</title>
<style>
    :root {
        --ink: #16232e; --ink-muted: #5c6b77; --garis: #d6dde2;
        --kertas: #ffffff; --latar: #eceff1;
        --aksen: #2f6fed; --aksen-hover: #2559c4; --bahaya: #b23b3b;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0; padding: 32px 20px 64px; background: var(--latar);
        color: var(--ink); font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        line-height: 1.55;
    }
    .wadah { max-width: 560px; margin: 0 auto; }
    h1 { font-size: 1.5rem; margin: 0 0 20px; }
    .balik { color: var(--aksen); text-decoration: none; font-size: 0.9rem; }
    .panel {
        background: var(--kertas); border: 1px solid var(--garis);
        border-radius: 8px; padding: 22px; margin-top: 16px;
    }
    label { display: block; font-size: 0.85rem; color: var(--ink-muted); margin: 14px 0 5px; }
    label:first-of-type { margin-top: 0; }
    input, select {
        width: 100%; padding: 9px 10px; border: 1px solid var(--garis);
        border-radius: 5px; font-size: 0.95rem; font-family: inherit;
    }
    input:focus, select:focus { outline: 2px solid var(--aksen); outline-offset: 1px; }
    .field-error { color: var(--bahaya); font-size: 0.8rem; margin-top: 4px; }
    .btn-row { margin-top: 20px; display: flex; gap: 10px; }
    .btn {
        display: inline-block; padding: 9px 18px; border-radius: 5px;
        border: 1px solid var(--garis); font-size: 0.92rem; font-weight: 600;
        cursor: pointer; text-decoration: none; color: var(--ink); background: #fff;
    }
    .btn-primary { background: var(--aksen); color: #fff; border-color: var(--aksen); }
    .btn-primary:hover { background: var(--aksen-hover); }
</style>
</head>
<body>
<div class="wadah">
    <a href="index.php" class="balik">&larr; Kembali ke daftar produk</a>
    <h1>Edit produk</h1>

    <div class="panel">
        <form method="post" action="edit.php" novalidate>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <label for="name">Nama produk</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" maxlength="100">
            <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>

            <label for="category">Kategori</label>
            <select id="category" name="category">
                <?php foreach (KATEGORI_PRODUK as $kat): ?>
                    <option value="<?= e($kat) ?>" <?= $old['category'] === $kat ? 'selected' : '' ?>><?= e($kat) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['category'])): ?><div class="field-error"><?= e($errors['category']) ?></div><?php endif; ?>

            <label for="price">Harga (Rp)</label>
            <input type="number" id="price" name="price" value="<?= e((string) $old['price']) ?>" min="1" step="1">
            <?php if (!empty($errors['price'])): ?><div class="field-error"><?= e($errors['price']) ?></div><?php endif; ?>

            <label for="stock">Stok</label>
            <input type="number" id="stock" name="stock" value="<?= e((string) $old['stock']) ?>" min="0" step="1">
            <?php if (!empty($errors['stock'])): ?><div class="field-error"><?= e($errors['stock']) ?></div><?php endif; ?>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                <a href="index.php" class="btn">Batal</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>

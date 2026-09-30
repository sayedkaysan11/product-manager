<?php
/**
 * Kumpulan fungsi bantu: validasi input, proteksi CSRF, dan formatting.
 */

const KATEGORI_PRODUK = ['Elektronik', 'Makanan', 'Minuman', 'Pakaian', 'Alat Tulis', 'Lainnya'];

/**
 * Membungkus htmlspecialchars supaya konsisten dipakai di semua output.
 * Sesuai syarat: htmlspecialchars($value, ENT_QUOTES, "UTF-8")
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/**
 * Membuat token CSRF sekali per sesi dan menyimpannya di $_SESSION.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Mengecek token CSRF yang dikirim form sama dengan yang ada di sesi.
 */
function csrfValid(?string $token): bool
{
    return !empty($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Menyimpan pesan satu kali (flash message) untuk ditampilkan setelah redirect.
 * Ini bagian dari pola Post-Redirect-Get supaya refresh tidak mengirim ulang form.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Validasi data produk. Mengembalikan array pesan error (kosong berarti valid).
 *
 * Aturan:
 * - nama minimal 3 karakter
 * - nama harus unik (kecuali saat edit produk itu sendiri, makanya ada $excludeId)
 * - harga harus lebih dari 0
 * - stok tidak boleh negatif
 */
function validateProduct(PDO $pdo, string $name, string $category, string $price, string $stock, ?int $excludeId = null): array
{
    $errors = [];

    $name = trim($name);

    if (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama produk minimal 3 karakter.';
    } elseif (namaSudahDipakai($pdo, $name, $excludeId)) {
        $errors['name'] = 'Nama produk ini sudah dipakai, pakai nama lain.';
    }

    if (!in_array($category, KATEGORI_PRODUK, true)) {
        $errors['category'] = 'Kategori tidak valid.';
    }

    if (!is_numeric($price) || (float) $price <= 0) {
        $errors['price'] = 'Harga harus lebih dari 0.';
    }

    if (!is_numeric($stock) || (int) $stock < 0 || str_contains($stock, '.')) {
        $errors['stock'] = 'Stok harus angka bulat, minimal 0.';
    }

    return $errors;
}

function namaSudahDipakai(PDO $pdo, string $name, ?int $excludeId = null): bool
{
    if ($excludeId !== null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE name = :name AND id != :id');
        $stmt->execute(['name' => $name, 'id' => $excludeId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE name = :name');
        $stmt->execute(['name' => $name]);
    }

    return (int) $stmt->fetchColumn() > 0;
}

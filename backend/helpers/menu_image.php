<?php
function saveMenuImage(?array $upload): ?string
{
    if ($upload === null || !isset($upload['error'])) {
        return null;
    }
    if ($upload['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gagal mengunggah file gambar.');
    }
    if ($upload['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Ukuran gambar maksimal 5 MB.');
    }

    $temporaryFile = $upload['tmp_name'] ?? '';
    if ($temporaryFile === '' || !is_uploaded_file($temporaryFile)) {
        throw new RuntimeException('File gambar yang diunggah tidak valid.');
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
    if (!isset($extensions[$mimeType]) || getimagesize($temporaryFile) === false) {
        throw new InvalidArgumentException(
            'Format gambar harus JPG, PNG, GIF, atau WebP.'
        );
    }

    $directory = __DIR__ . '/../uploads/menu_images';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan gambar tidak dapat dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    if (!move_uploaded_file($temporaryFile, $directory . '/' . $filename)) {
        throw new RuntimeException('Gambar gagal disimpan ke server.');
    }

    return '/Kantin-Sekolah/backend/uploads/menu_images/' . $filename;
}

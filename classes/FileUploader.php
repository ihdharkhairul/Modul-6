<?php
/**
 * Class FileUploader
 * Mengelola proses upload file (foto) ke server.
 */
class FileUploader {
    private array  $allowedTypes;
    private int    $maxSize;
    private string $uploadDir;
    private string $prefix;

    public function __construct(
        string $uploadDir,
        array  $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
        int    $maxSize      = 2 * 1024 * 1024, // 2 MB
        string $prefix       = 'file_'
    ) {
        $this->uploadDir    = rtrim($uploadDir, '/') . '/';
        $this->allowedTypes = $allowedTypes;
        $this->maxSize      = $maxSize;
        $this->prefix       = $prefix;
    }

    /**
     * Memproses upload file dari array $_FILES.
     * @param array $file  Elemen dari $_FILES (contoh: $_FILES['foto'])
     * @return string|null Nama file yang disimpan, atau null jika gagal / tidak ada file.
     */
    public function upload(array $file): ?string {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        if (!in_array($file['type'], $this->allowedTypes)) return null;
        if ($file['size'] > $this->maxSize) return null;

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid($this->prefix, true) . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $this->uploadDir . $filename)) {
            return $filename;
        }
        return null;
    }

    /**
     * Menghapus file yang tersimpan (misalnya saat data dihapus atau diganti).
     */
    public function delete(string $filename): bool {
        $path = $this->uploadDir . $filename;
        if ($filename && file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Mengembalikan ukuran maksimal dalam format MB.
     */
    public function getMaxSizeMB(): float {
        return $this->maxSize / (1024 * 1024);
    }
}

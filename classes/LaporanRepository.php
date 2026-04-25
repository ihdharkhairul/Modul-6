<?php
/**
 * Class LaporanRepository
 * Mengelola operasi CRUD pada tabel laporan_lingkungan.
 * Menerapkan prinsip OOP: Encapsulation, Single Responsibility.
 */
class LaporanRepository {
    private mysqli $conn;

    // Daftar kategori dan status yang valid
    public const KATEGORI_LIST = ['Polusi Udara', 'Sampah Liar', 'Banjir', 'Kerusakan Pohon', 'Lainnya'];
    public const STATUS_LIST   = ['Pending', 'Diproses', 'Selesai'];
    public const STATUS_COLOR  = [
        'Pending'  => 'amber',
        'Diproses' => 'blue',
        'Selesai'  => 'green',
    ];

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    // ── CREATE ────────────────────────────────────────────────
    /**
     * Menyimpan laporan baru ke database.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function create(int $userId, string $judul, string $kategori, string $deskripsi, string $lokasi, ?string $foto): bool {
        $stmt = $this->conn->prepare(
            'INSERT INTO laporan_lingkungan (user_id, judul, kategori, deskripsi, lokasi, foto)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->bind_param('isssss', $userId, $judul, $kategori, $deskripsi, $lokasi, $foto);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ── READ (all / filtered) ─────────────────────────────────
    /**
     * Mengambil semua laporan sesuai filter dan role pengguna.
     */
    public function findAll(array $user, string $search = '', string $filterKategori = ''): array {
        if ($user['role'] === 'citizen') {
            $sql    = 'SELECT l.*, u.name AS pelapor FROM laporan_lingkungan l JOIN users u ON u.id=l.user_id WHERE l.user_id=?';
            $params = [$user['id']];
            $types  = 'i';
        } else {
            $sql    = 'SELECT l.*, u.name AS pelapor FROM laporan_lingkungan l JOIN users u ON u.id=l.user_id WHERE 1=1';
            $params = [];
            $types  = '';
        }

        if ($search) {
            $sql   .= ' AND (l.judul LIKE ? OR l.lokasi LIKE ?)';
            $like   = "%$search%";
            $params = array_merge($params, [$like, $like]);
            $types .= 'ss';
        }
        if ($filterKategori) {
            $sql   .= ' AND l.kategori=?';
            $params[] = $filterKategori;
            $types .= 's';
        }
        $sql .= ' ORDER BY l.created_at DESC';

        $stmt = $this->conn->prepare($sql);
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    // ── READ (single) ─────────────────────────────────────────
    /**
     * Mengambil satu laporan berdasarkan ID.
     */
    public function findById(int $id): ?array {
        $stmt = $this->conn->prepare('SELECT * FROM laporan_lingkungan WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    // ── UPDATE ────────────────────────────────────────────────
    /**
     * Memperbarui data laporan.
     * Citizen hanya bisa edit laporan milik sendiri.
     */
    public function update(int $id, array $user, string $judul, string $kategori, string $deskripsi, string $lokasi, string $status, ?string $foto): bool {
        if ($user['role'] === 'citizen') {
            $stmt = $this->conn->prepare(
                'UPDATE laporan_lingkungan SET judul=?,kategori=?,deskripsi=?,lokasi=?,status=?,foto=?
                 WHERE id=? AND user_id=?'
            );
            $stmt->bind_param('ssssssii', $judul, $kategori, $deskripsi, $lokasi, $status, $foto, $id, $user['id']);
        } else {
            $stmt = $this->conn->prepare(
                'UPDATE laporan_lingkungan SET judul=?,kategori=?,deskripsi=?,lokasi=?,status=?,foto=?
                 WHERE id=?'
            );
            $stmt->bind_param('ssssssi', $judul, $kategori, $deskripsi, $lokasi, $status, $foto, $id);
        }
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ── DELETE ────────────────────────────────────────────────
    /**
     * Menghapus laporan berdasarkan ID.
     * Citizen hanya bisa hapus laporan milik sendiri.
     * @return string|null Nama foto yang dihapus (untuk menghapus file), null jika tidak ada atau gagal.
     */
    public function delete(int $id, array $user): ?string {
        // Ambil nama foto dahulu
        $sf = $this->conn->prepare('SELECT foto FROM laporan_lingkungan WHERE id=? LIMIT 1');
        $sf->bind_param('i', $id);
        $sf->execute();
        $row = $sf->get_result()->fetch_assoc();
        $sf->close();

        if ($user['role'] === 'citizen') {
            $stmt = $this->conn->prepare('DELETE FROM laporan_lingkungan WHERE id=? AND user_id=?');
            $stmt->bind_param('ii', $id, $user['id']);
        } else {
            $stmt = $this->conn->prepare('DELETE FROM laporan_lingkungan WHERE id=?');
            $stmt->bind_param('i', $id);
        }
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();

        return ($ok && !empty($row['foto'])) ? $row['foto'] : null;
    }

    // ── STATS ─────────────────────────────────────────────────
    /**
     * Menghitung statistik laporan (total, pending, diproses, selesai).
     */
    public function getStats(array $user): array {
        $wh  = ($user['role'] === 'citizen') ? " WHERE user_id={$user['id']}" : '';
        $res = $this->conn->query(
            "SELECT COUNT(*) total,
                    SUM(status='Pending')  pending,
                    SUM(status='Diproses') diproses,
                    SUM(status='Selesai')  selesai
             FROM laporan_lingkungan$wh"
        );
        return $res->fetch_assoc() ?? ['total' => 0, 'pending' => 0, 'diproses' => 0, 'selesai' => 0];
    }
}

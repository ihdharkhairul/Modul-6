<?php
/**
 * Class User
 * Mengelola data pengguna: autentikasi, registrasi, dan sesi.
 */
class User {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Login: verifikasi email & password, simpan ke sesi jika berhasil.
     * @return array|null Data user jika sukses, null jika gagal.
     */
    public function login(string $email, string $password): ?array {
        $stmt = $this->conn->prepare(
            'SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            return $_SESSION['user'];
        }
        return null;
    }

    /**
     * Registrasi pengguna baru.
     * @return string|true Pesan error (string) atau true jika sukses.
     */
    public function register(string $name, string $email, string $password, string $role): string|bool {
        // Validasi role
        if (!in_array($role, ['citizen', 'officer'])) {
            return 'Role tidak valid.';
        }

        // Cek email sudah ada
        $check = $this->conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            return 'Email sudah terdaftar.';
        }
        $check->close();

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $ins = $this->conn->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)'
        );
        $ins->bind_param('ssss', $name, $email, $hashed, $role);
        $result = $ins->execute();
        $ins->close();

        return $result ? true : 'Gagal menyimpan pengguna.';
    }

    /**
     * Logout: hapus sesi.
     */
    public function logout(): void {
        session_unset();
        session_destroy();
    }

    /**
     * Mengambil data user yang sedang login dari sesi.
     */
    public static function currentUser(): array {
        return $_SESSION['user'] ?? [];
    }

    /**
     * Memeriksa apakah user sudah login; redirect jika belum.
     */
    public static function requireLogin(string $redirect = ''): void {
        if (empty($_SESSION['user'])) {
            // Hitung base URL secara dinamis agar tidak terpengaruh nama folder
            $base = rtrim(str_replace('/pages', '', dirname($_SERVER['SCRIPT_NAME'])), '/\\');
            $target = $redirect ?: $base . '/login.php';
            header('Location: ' . $target);
            exit;
        }
    }

    /**
     * Redirect berdasarkan role user.
     */
    public static function redirectByRole(string $role): void {
        // Hitung base URL dari posisi script saat ini
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $base = rtrim(str_replace('/pages', '', $scriptDir), '/\\');

        $map = [
            'admin'   => $base . '/pages/dashboard.php',
            'officer' => $base . '/pages/dashboard.php',
            'citizen' => $base . '/pages/dashboard.php',
        ];
        header('Location: ' . ($map[$role] ?? $base . '/login.php'));
        exit;
    }
}

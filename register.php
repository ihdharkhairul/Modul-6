<?php
/**
 * Register GaiaCity
 * Menggunakan class User untuk registrasi (OOP).
 */
require_once 'config/database.php';
require_once 'config/auth.php';

if (!empty($_SESSION['user'])) redirectByRole($_SESSION['user']['role']);

$error = ''; $success = ''; $old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'  => trim(filter_input(INPUT_POST, 'name',  FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
        'email' => trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? ''),
        'role'  => trim(filter_input(INPUT_POST, 'role',  FILTER_SANITIZE_SPECIAL_CHARS) ?? 'citizen'),
    ];
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (empty($old['name']) || empty($old['email']) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Menggunakan class User untuk proses registrasi (OOP)
        $db      = Database::getInstance()->getConnection();
        $userObj = new User($db);
        $result  = $userObj->register($old['name'], $old['email'], $password, $old['role']);
        Database::getInstance()->close();

        if ($result === true) {
            $success = 'Registrasi berhasil! Silakan masuk.';
            $old = [];
        } else {
            $error = $result; // pesan error dari class User
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GaiaCity – Daftar</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    * { font-family: 'Inter', sans-serif; }
    .gradient-bg { background: linear-gradient(135deg,#0f766e 0%,#134e4a 50%,#064e3b 100%); }
    @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
    .fade-up { animation: fadeUp .55s ease-out both; }
  </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
<div class="w-full max-w-md fade-up">

  <div class="text-center mb-8">
    <a href="#" class="inline-flex items-center gap-3">
      <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <span class="text-2xl font-bold text-white">GaiaCity</span>
    </a>
    <p class="text-white/70 mt-2 text-sm">Platform Smart City Terintegrasi</p>
  </div>

  <div class="bg-white rounded-2xl p-8 shadow-2xl">
    <h2 class="text-2xl font-bold text-gray-900 mb-1">Buat Akun Baru</h2>
    <p class="text-gray-500 text-sm mb-6">Bergabung dengan komunitas peduli lingkungan</p>

    <?php if ($error): ?>
    <div class="mb-5 flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="mb-5 flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
      </svg>
      <?php echo htmlspecialchars($success); ?>
      <a href="login.php" class="ml-auto font-semibold text-teal-600 hover:underline">Masuk →</a>
    </div>
    <?php endif; ?>

    <form method="POST" action="register.php" novalidate>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
        <input name="name" type="text" required
          value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition"
          placeholder="John Doe"/>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
        <input name="email" type="email" required
          value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition"
          placeholder="email@example.com"/>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
        <div class="relative">
          <input id="pw1" name="password" type="password" required minlength="8"
            class="w-full px-4 py-3 pr-11 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition"
            placeholder="Min. 8 karakter"/>
          <button type="button" onclick="togglePw('pw1',this)"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Password</label>
        <div class="relative">
          <input id="pw2" name="confirm" type="password" required
            class="w-full px-4 py-3 pr-11 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition"
            placeholder="Ulangi password"/>
          <button type="button" onclick="togglePw('pw2',this)"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
        <p id="matchMsg" class="mt-1 text-xs hidden"></p>
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
        <select name="role"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
          <option value="citizen" <?php echo (($old['role']??'citizen')==='citizen')?'selected':''; ?>>Citizen (Warga)</option>
          <option value="officer" <?php echo (($old['role']??'')==='officer')?'selected':''; ?>>Field Officer (Petugas Lapangan)</option>
        </select>
      </div>

      <button type="submit"
        class="w-full py-3 bg-teal-600 text-white rounded-lg font-semibold text-sm hover:bg-teal-700 transition shadow-lg">
        Daftar Sekarang
      </button>

      <p class="text-center mt-5 text-sm text-gray-500">
        Sudah punya akun?
        <a href="login.php" class="text-teal-600 font-medium hover:underline">Masuk</a>
      </p>
    </form>
  </div>
</div>
<script>
function togglePw(id, btn) {
  const el = document.getElementById(id);
  const show = el.type === 'password';
  el.type = show ? 'text' : 'password';
  btn.innerHTML = show
    ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>`
    : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
}
const pw1 = document.getElementById('pw1');
const pw2 = document.getElementById('pw2');
const msg = document.getElementById('matchMsg');
pw2.addEventListener('input', () => {
  if (!pw2.value) { msg.classList.add('hidden'); return; }
  const ok = pw1.value === pw2.value;
  msg.classList.remove('hidden','text-red-500','text-green-600');
  msg.classList.add(ok ? 'text-green-600' : 'text-red-500');
  msg.textContent = ok ? '✓ Password cocok' : '✗ Password tidak cocok';
});
document.querySelector('form').addEventListener('submit', e => {
  if (pw1.value !== pw2.value) { e.preventDefault(); pw2.focus(); }
});
</script>
</body>
</html>

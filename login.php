<?php
/**
 * Login GaiaCity
 * Menggunakan class User untuk autentikasi (OOP).
 */
require_once 'config/database.php';
require_once 'config/auth.php';

if (!empty($_SESSION['user'])) redirectByRole($_SESSION['user']['role']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        // Menggunakan class User untuk proses login (OOP)
        $db   = Database::getInstance()->getConnection();
        $userObj = new User($db);
        $result  = $userObj->login($email, $password);
        Database::getInstance()->close();

        if ($result) {
            redirectByRole($result['role']);
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GaiaCity – Masuk</title>
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
    <h2 class="text-2xl font-bold text-gray-900 mb-1">Selamat Datang Kembali</h2>
    <p class="text-gray-500 text-sm mb-6">Masuk ke akun GaiaCity Anda</p>

    <?php if ($error): ?>
    <div class="mb-5 flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
        <input name="email" type="email" required
          value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition"
          placeholder="email@example.com"/>
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
        <div class="relative">
          <input id="pw" name="password" type="password" required
            class="w-full px-4 py-3 pr-11 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition"
            placeholder="••••••••"/>
          <button type="button" onclick="togglePw('pw',this)"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit"
        class="w-full py-3 bg-teal-600 text-white rounded-lg font-semibold text-sm hover:bg-teal-700 transition shadow-lg">
        Masuk
      </button>

      <p class="text-center mt-5 text-sm text-gray-500">
        Belum punya akun?
        <a href="register.php" class="text-teal-600 font-medium hover:underline">Daftar Sekarang</a>
      </p>
    </form>

    <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
      <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Akun Demo</p>
      <div class="space-y-1 text-xs text-gray-600">
        <div class="flex justify-between"><span>Admin</span><span class="font-mono">admin@gaiacity.id / Password123</span></div>
        <div class="flex justify-between"><span>Officer</span><span class="font-mono">officer@gaiacity.id / Password123</span></div>
        <div class="flex justify-between"><span>Citizen</span><span class="font-mono">citizen@gaiacity.id / Password123</span></div>
      </div>
    </div>
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
</script>
</body>
</html>

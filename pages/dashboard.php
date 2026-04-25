<?php
/**
 * Dashboard GaiaCity
 * Halaman utama yang hanya bisa diakses setelah login berhasil.
 *
 * OOP Classes yang digunakan:
 *  - Database          : Singleton koneksi database
 *  - User              : Autentikasi & manajemen sesi
 *  - LaporanRepository : CRUD laporan_lingkungan
 *  - FileUploader      : Upload & hapus file foto
 */
require_once '../config/database.php';
require_once '../config/auth.php';

// ── Autentikasi (hanya bisa diakses setelah login) ───────────
requireLogin('../login.php');

$user = currentUser();

// ── Inisialisasi objek OOP ───────────────────────────────────
$db       = Database::getInstance()->getConnection();
$lapRepo  = new LaporanRepository($db);
$uploader = new FileUploader(
    __DIR__ . '/../uploads/',
    ['image/jpeg','image/jpg','image/png','image/webp'],
    2 * 1024 * 1024,
    'foto_'
);

$message = '';
$msgType = 'success';

// ── Handler CRUD (satu tombol "Simpan" = CRUD + Upload) ──────
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── CREATE ──────────────────────────────────────────────
    if ($action === 'create') {
        $judul     = trim(filter_input(INPUT_POST, 'judul',     FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $kategori  = trim(filter_input(INPUT_POST, 'kategori',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $deskripsi = trim(filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $lokasi    = trim(filter_input(INPUT_POST, 'lokasi',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $foto      = null;

        if (empty($judul) || empty($kategori) || empty($deskripsi) || empty($lokasi)) {
            $message = 'Semua field wajib diisi.'; $msgType = 'error';
        } elseif (!in_array($kategori, LaporanRepository::KATEGORI_LIST)) {
            $message = 'Kategori tidak valid.'; $msgType = 'error';
        } else {
            // FileUploader menangani upload dalam satu submit yang sama
            if (!empty($_FILES['foto']['name'])) {
                $foto = $uploader->upload($_FILES['foto']);
                if ($foto === null) {
                    $message = 'Gagal upload foto. Pastikan format JPG/PNG/WEBP dan ukuran maks 2MB.';
                    $msgType = 'error';
                }
            }
            if (empty($message)) {
                if ($lapRepo->create((int)$user['id'], $judul, $kategori, $deskripsi, $lokasi, $foto)) {
                    $message = 'Laporan berhasil disimpan!';
                } else {
                    $message = 'Gagal menyimpan laporan.'; $msgType = 'error';
                }
            }
        }
    }

    // ── UPDATE ──────────────────────────────────────────────
    elseif ($action === 'update') {
        $id        = (int) ($_POST['id'] ?? 0);
        $judul     = trim(filter_input(INPUT_POST, 'judul',     FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $kategori  = trim(filter_input(INPUT_POST, 'kategori',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $deskripsi = trim(filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $lokasi    = trim(filter_input(INPUT_POST, 'lokasi',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $status    = trim(filter_input(INPUT_POST, 'status',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $fotoLama  = trim(filter_input(INPUT_POST, 'foto_lama', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        $foto = $fotoLama;

        // FileUploader menangani upload & hapus file lama dalam satu submit
        if (!empty($_FILES['foto']['name'])) {
            $fotoBaru = $uploader->upload($_FILES['foto']);
            if ($fotoBaru) {
                if ($fotoLama) $uploader->delete($fotoLama);
                $foto = $fotoBaru;
            }
        }

        if ($lapRepo->update($id, $user, $judul, $kategori, $deskripsi, $lokasi, $status, $foto)) {
            $message = 'Laporan berhasil diperbarui!';
        } else {
            $message = 'Gagal memperbarui laporan.'; $msgType = 'error';
        }
    }

    // ── DELETE ──────────────────────────────────────────────
    elseif ($action === 'delete') {
        $id       = (int) ($_POST['id'] ?? 0);
        $fotoName = $lapRepo->delete($id, $user);
        if ($fotoName) $uploader->delete($fotoName);
        $message = 'Laporan berhasil dihapus.';
    }
}

// ── Ambil data untuk tampilan ────────────────────────────────
$search    = trim(filter_input(INPUT_GET, 'search',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$filterKat = trim(filter_input(INPUT_GET, 'kategori', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

$laporan = $lapRepo->findAll($user, $search, $filterKat);
$stats   = $lapRepo->getStats($user);

Database::getInstance()->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <title>GaiaCity – Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    * { font-family: 'Inter', sans-serif; }
    .sidebar { width:260px; }
    @media(max-width:768px){ .sidebar{display:none;} }
    .modal-bg { background:rgba(0,0,0,.5); backdrop-filter:blur(4px); }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex">

<!-- SIDEBAR -->
<aside class="sidebar bg-gray-900 text-white flex flex-col fixed h-full z-30">
  <div class="p-6 border-b border-gray-700">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-teal-600 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <span class="font-bold text-lg">GaiaCity</span>
    </div>
  </div>
  <nav class="flex-1 p-4 space-y-1">
    <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-teal-600/20 text-teal-400 font-medium text-sm">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      Dashboard
    </a>
  </nav>
  <div class="p-4 border-t border-gray-700">
    <div class="flex items-center gap-3 mb-3">
      <div class="w-9 h-9 bg-teal-600 rounded-full flex items-center justify-center text-sm font-bold">
        <?php echo strtoupper(substr($user['name'],0,1)); ?>
      </div>
      <div>
        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($user['name']); ?></div>
        <div class="text-xs text-gray-400 capitalize"><?php echo $user['role']; ?></div>
      </div>
    </div>
    <a href="../logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-red-400 hover:bg-red-500/10 text-sm transition">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      Keluar
    </a>
  </div>
</aside>

<!-- MAIN -->
<main class="flex-1 ml-0 md:ml-[260px] min-h-screen">

  <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between sticky top-0 z-20">
    <div>
      <h1 class="text-lg font-bold text-gray-900">Dashboard Laporan Lingkungan</h1>
      <p class="text-xs text-gray-500"><?php echo date('l, d F Y'); ?></p>
    </div>
    <button onclick="openModal('createModal')"
      class="flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 transition shadow">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      Tambah Laporan
    </button>
  </header>

  <div class="p-6 space-y-6">

    <?php if ($message): ?>
    <div class="flex items-center gap-3 px-5 py-3 rounded-xl text-sm font-medium
      <?php echo $msgType==='error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'; ?>">
      <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Stats cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach([
        ['label'=>'Total',    'val'=>$stats['total'],    'color'=>'teal'],
        ['label'=>'Pending',  'val'=>$stats['pending'],  'color'=>'amber'],
        ['label'=>'Diproses', 'val'=>$stats['diproses'], 'color'=>'blue'],
        ['label'=>'Selesai',  'val'=>$stats['selesai'],  'color'=>'green'],
      ] as $c): ?>
      <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
        <div class="text-2xl font-bold text-<?php echo $c['color']; ?>-600"><?php echo $c['val']??0; ?></div>
        <div class="text-xs text-gray-500 mt-1"><?php echo $c['label']; ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
      <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" placeholder="Cari judul / lokasi..."
          value="<?php echo htmlspecialchars($search); ?>"
          class="flex-1 min-w-[160px] px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"/>
        <select name="kategori"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
          <option value="">Semua Kategori</option>
          <?php foreach(LaporanRepository::KATEGORI_LIST as $k): ?>
          <option value="<?php echo $k; ?>" <?php echo $filterKat===$k?'selected':''; ?>><?php echo $k; ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="px-5 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 transition">Cari</button>
        <?php if($search||$filterKat): ?>
        <a href="dashboard.php" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">Reset</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Tabel Laporan -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-900">Daftar Laporan <span class="text-xs text-gray-400 font-normal">(<?php echo count($laporan); ?> data)</span></h2>
      </div>
      <?php if(empty($laporan)): ?>
      <div class="py-16 text-center text-gray-400">
        <p class="font-medium">Belum ada laporan</p>
        <p class="text-sm mt-1">Klik "Tambah Laporan" untuk membuat laporan baru</p>
      </div>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
            <tr>
              <th class="px-4 py-3 text-left">#</th>
              <th class="px-4 py-3 text-left">Foto</th>
              <th class="px-4 py-3 text-left">Judul</th>
              <th class="px-4 py-3 text-left">Kategori</th>
              <th class="px-4 py-3 text-left">Lokasi</th>
              <?php if($user['role']!=='citizen'): ?>
              <th class="px-4 py-3 text-left">Pelapor</th>
              <?php endif; ?>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Tanggal</th>
              <th class="px-4 py-3 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach($laporan as $i => $row):
              $sc = LaporanRepository::STATUS_COLOR[$row['status']] ?? 'gray'; ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="px-4 py-3 text-gray-400"><?php echo $i+1; ?></td>
              <td class="px-4 py-3">
                <?php if($row['foto']): ?>
                <img src="../uploads/<?php echo htmlspecialchars($row['foto']); ?>"
                  class="w-12 h-12 object-cover rounded-lg cursor-pointer border border-gray-200"
                  onclick="showFoto('../uploads/<?php echo htmlspecialchars($row['foto']); ?>')" alt="foto"/>
                <?php else: ?>
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 font-medium text-gray-900 max-w-[150px] truncate"><?php echo htmlspecialchars($row['judul']); ?></td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 bg-teal-50 text-teal-700 rounded-full text-xs"><?php echo htmlspecialchars($row['kategori']); ?></span>
              </td>
              <td class="px-4 py-3 text-gray-600 max-w-[130px] truncate"><?php echo htmlspecialchars($row['lokasi']); ?></td>
              <?php if($user['role']!=='citizen'): ?>
              <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($row['pelapor']); ?></td>
              <?php endif; ?>
              <td class="px-4 py-3">
                <span class="px-2 py-1 bg-<?php echo $sc; ?>-50 text-<?php echo $sc; ?>-700 rounded-full text-xs font-semibold"><?php echo $row['status']; ?></span>
              </td>
              <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                  <button onclick='openEdit(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>)'
                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                  <form method="POST" onsubmit="return confirm('Hapus laporan ini?')">
                    <input type="hidden" name="action" value="delete"/>
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>"/>
                    <button type="submit"
                      class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition" title="Hapus">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- MODAL CREATE -->
<div id="createModal" class="hidden fixed inset-0 modal-bg z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
      <h3 class="font-bold text-gray-900 text-lg">Tambah Laporan Baru</h3>
      <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600">✕</button>
    </div>
    <!-- Satu tombol "Simpan" = Create + Upload dalam satu submit -->
    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
      <input type="hidden" name="action" value="create"/>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Laporan</label>
        <input name="judul" type="text" required placeholder="Judul laporan"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
        <select name="kategori" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
          <?php foreach(LaporanRepository::KATEGORI_LIST as $k): ?>
          <option value="<?php echo $k; ?>"><?php echo $k; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Lokasi</label>
        <input name="lokasi" type="text" required placeholder="Alamat / lokasi kejadian"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
        <textarea name="deskripsi" rows="3" required placeholder="Jelaskan masalah lingkungan..."
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Foto Laporan <span class="text-gray-400 font-normal">(opsional, maks 2MB)</span>
        </label>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-teal-400 transition cursor-pointer"
          onclick="document.getElementById('fotoCreate').click()">
          <input id="fotoCreate" name="foto" type="file" accept="image/*" class="hidden"
            onchange="previewFoto(this,'prevCreate')"/>
          <img id="prevCreate" src="#" alt="preview" class="hidden mx-auto mb-2 max-h-32 rounded-lg object-cover"/>
          <div id="fotoCreateLabel">
            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm text-gray-400">Klik untuk upload foto</p>
            <p class="text-xs text-gray-300 mt-1">JPG, PNG, WEBP</p>
          </div>
        </div>
      </div>
      <!-- Satu tombol Simpan: menjalankan Create + Upload sekaligus -->
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="closeModal('createModal')"
          class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
          Batal
        </button>
        <button type="submit"
          class="flex-1 py-2.5 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700 transition">
          <i class="fa-solid fa-floppy-disk"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT -->
<div id="editModal" class="hidden fixed inset-0 modal-bg z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
      <h3 class="font-bold text-gray-900 text-lg">Edit Laporan</h3>
      <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600">✕</button>
    </div>
    <!-- Satu tombol "Simpan" = Update + Upload dalam satu submit -->
    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="id" id="edit_id"/>
      <input type="hidden" name="foto_lama" id="edit_foto_lama"/>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Laporan</label>
        <input name="judul" id="edit_judul" type="text" required
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
        <select name="kategori" id="edit_kategori" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
          <?php foreach(LaporanRepository::KATEGORI_LIST as $k): ?>
          <option value="<?php echo $k; ?>"><?php echo $k; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Lokasi</label>
        <input name="lokasi" id="edit_lokasi" type="text" required
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
        <textarea name="deskripsi" id="edit_deskripsi" rows="3" required
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
        <select name="status" id="edit_status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
          <?php foreach(LaporanRepository::STATUS_LIST as $s): ?>
          <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Ganti Foto <span class="text-gray-400 font-normal">(kosongkan jika tidak diganti)</span>
        </label>
        <div id="fotoEditPreviewWrap" class="mb-2 hidden">
          <img id="fotoEditCurrent" src="#" alt="foto saat ini"
            class="max-h-28 rounded-lg object-cover border border-gray-200"/>
          <p class="text-xs text-gray-400 mt-1">Foto saat ini</p>
        </div>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-teal-400 transition cursor-pointer"
          onclick="document.getElementById('fotoEdit').click()">
          <input id="fotoEdit" name="foto" type="file" accept="image/*" class="hidden"
            onchange="previewFoto(this,'prevEdit')"/>
          <img id="prevEdit" src="#" alt="preview baru" class="hidden mx-auto mb-2 max-h-28 rounded-lg object-cover"/>
          <p class="text-sm text-gray-400">Klik untuk ganti foto</p>
        </div>
      </div>
      <!-- Satu tombol Simpan: menjalankan Update + Upload sekaligus -->
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="closeModal('editModal')"
          class="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
          Batal
        </button>
        <button type="submit"
          class="flex-1 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
          <i class="fa-solid fa-floppy-disk"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL FOTO PREVIEW -->
<div id="fotoModal" class="hidden fixed inset-0 modal-bg z-50 flex items-center justify-center p-4">
  <div class="relative">
    <button onclick="closeModal('fotoModal')"
      class="absolute -top-3 -right-3 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-gray-600 hover:text-gray-900 z-10">✕</button>
    <img id="fotoModalImg" src="#" alt="foto" class="max-w-[90vw] max-h-[80vh] rounded-2xl object-contain shadow-2xl"/>
  </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.add('hidden');    document.body.style.overflow=''; }

['createModal','editModal','fotoModal'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e){ if(e.target===this) closeModal(id); });
});

function openEdit(row) {
  document.getElementById('edit_id').value        = row.id;
  document.getElementById('edit_judul').value     = row.judul;
  document.getElementById('edit_lokasi').value    = row.lokasi;
  document.getElementById('edit_deskripsi').value = row.deskripsi;
  document.getElementById('edit_foto_lama').value = row.foto || '';

  ['edit_kategori','edit_status'].forEach(sid => {
    const sel = document.getElementById(sid);
    const key = sid.replace('edit_','');
    for(let o of sel.options) o.selected = (o.value === row[key]);
  });

  const wrap = document.getElementById('fotoEditPreviewWrap');
  const img  = document.getElementById('fotoEditCurrent');
  if (row.foto) { img.src = '../uploads/' + row.foto; wrap.classList.remove('hidden'); }
  else { wrap.classList.add('hidden'); }

  document.getElementById('prevEdit').classList.add('hidden');
  document.getElementById('fotoEdit').value = '';
  openModal('editModal');
}

function previewFoto(input, previewId) {
  const preview = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.classList.remove('hidden'); };
    reader.readAsDataURL(input.files[0]);
    const label = document.getElementById('fotoCreateLabel');
    if (label) label.classList.add('hidden');
  }
}

function showFoto(src) {
  document.getElementById('fotoModalImg').src = src;
  openModal('fotoModal');
}
</script>
</body>
</html>

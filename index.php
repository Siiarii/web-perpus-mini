<?php
require_once __DIR__ . '/includes/helpers.php';

$data = bacaData();
$flash = getFlash();

// Parameter GET untuk navigasi tab, pencarian, filter, dan mode edit
$tab = isset($_GET['tab']) ? cleanInput($_GET['tab']) : 'buku';
if ($tab !== 'buku' && $tab !== 'anggota' && $tab !== 'peminjaman') {
    $tab = 'buku';
}

$q        = isset($_GET['q']) ? cleanInput($_GET['q']) : '';
$kategori = isset($_GET['kategori']) ? cleanInput($_GET['kategori']) : '';
$status   = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$editId   = isset($_GET['edit']) ? cleanInput($_GET['edit']) : '';

// Hitung statistik perpustakaan untuk 4 kartu ringkasan
$totalJudulBuku = count($data['buku']);
$totalEksemplar = 0;
foreach ($data['buku'] as $b) {
    $totalEksemplar += (int) $b['stok'];
}

$totalAnggotaAktif = 0;
foreach ($data['anggota'] as $a) {
    if ($a['status'] === 'Aktif') {
        $totalAnggotaAktif++;
    }
}

$totalPeminjamanAktif = 0;
foreach ($data['peminjaman'] as $p) {
    if ($p['status'] === 'Dipinjam') {
        $totalPeminjamanAktif++;
    }
}

// Data untuk mode edit (Buku / Anggota)
$editBuku = null;
$editAnggota = null;
if ($tab === 'buku' && $editId !== '') {
    $editBuku = cariBukuById($data['buku'], $editId);
}
if ($tab === 'anggota' && $editId !== '') {
    $editAnggota = cariAnggotaById($data['anggota'], $editId);
}

// Filter data sesuai parameter GET
$daftarBukuTampil = array();
foreach ($data['buku'] as $item) {
    $cocokCari = ($q === '')
        || (stripos($item['judul'], $q) !== false)
        || (stripos($item['kode'], $q) !== false)
        || (stripos($item['penulis'], $q) !== false);

    $cocokKategori = ($kategori === '') || ($item['kategori'] === $kategori);

    $cocokStatus = true;
    if ($status === 'tersedia') {
        $cocokStatus = ((int) $item['stok'] > 1);
    } elseif ($status === 'terbatas') {
        $cocokStatus = ((int) $item['stok'] === 1);
    } elseif ($status === 'habis') {
        $cocokStatus = ((int) $item['stok'] === 0);
    }

    if ($cocokCari && $cocokKategori && $cocokStatus) {
        $daftarBukuTampil[] = $item;
    }
}

$daftarAnggotaTampil = array();
foreach ($data['anggota'] as $item) {
    $cocokCari = ($q === '')
        || (stripos($item['nama'], $q) !== false)
        || (stripos($item['kode'], $q) !== false)
        || (stripos($item['nim'], $q) !== false)
        || (stripos($item['kelas'], $q) !== false);

    $cocokStatus = ($status === '') || ($item['status'] === $status);

    if ($cocokCari && $cocokStatus) {
        $daftarAnggotaTampil[] = $item;
    }
}

$daftarPeminjamanTampil = array();
foreach ($data['peminjaman'] as $item) {
    $agt = cariAnggotaById($data['anggota'], $item['anggota_id']);
    $bk  = cariBukuById($data['buku'], $item['buku_id']);
    $namaAnggota = $agt ? $agt['nama'] : $item['anggota_id'];
    $judulBuku   = $bk ? $bk['judul'] : $item['buku_id'];

    $cocokCari = ($q === '')
        || (stripos($item['kode'], $q) !== false)
        || (stripos($namaAnggota, $q) !== false)
        || (stripos($judulBuku, $q) !== false);

    $cocokStatus = ($status === '') || ($item['status'] === $status);

    if ($cocokCari && $cocokStatus) {
        $item['nama_anggota'] = $namaAnggota;
        $item['judul_buku']   = $judulBuku;
        $daftarPeminjamanTampil[] = $item;
    }
}
$daftarPeminjamanTampil = array_reverse($daftarPeminjamanTampil);

$kodeBukuSaran    = buatKodeOtomatis($data['buku'], 'BK');
$kodeAnggotaSaran = buatKodeOtomatis($data['anggota'], 'AGT');
$hariIni          = date('Y-m-d');
$jatuhTempoSaran  = date('Y-m-d', strtotime('+7 days'));

include_once __DIR__ . '/includes/header.php';
?>

<main class="container main-content">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?php echo e($flash['type']); ?>" role="status" aria-live="polite">
            <span class="alert-text"><?php echo e($flash['message']); ?></span>
            <button type="button" class="alert-close" aria-label="Tutup notifikasi">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Ringkasan Statistik Perpustakaan (CSS Grid 4 Kartu) -->
    <section class="stats-section" aria-label="Ringkasan statistik perpustakaan">
        <article class="stat-card">
            <div class="stat-icon stat-icon-indigo" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div class="stat-body">
                <p class="stat-label">Total Judul Buku</p>
                <p class="stat-value"><?php echo e($totalJudulBuku); ?></p>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon stat-icon-emerald" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </div>
            <div class="stat-body">
                <p class="stat-label">Eksemplar Tersedia</p>
                <p class="stat-value"><?php echo e($totalEksemplar); ?></p>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon stat-icon-sky" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-body">
                <p class="stat-label">Anggota Aktif</p>
                <p class="stat-value"><?php echo e($totalAnggotaAktif); ?></p>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon stat-icon-amber" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-body">
                <p class="stat-label">Peminjaman Berjalan</p>
                <p class="stat-value"><?php echo e($totalPeminjamanAktif); ?></p>
            </div>
        </article>
    </section>

    <!-- Konten Utama: Grid 2 Kolom (Form Input + Daftar Data) -->
    <div class="workspace-grid">

        <?php if ($tab === 'buku'): ?>
            <!-- ================= MODUL DATA BUKU ================= -->
            <section class="card form-panel" aria-labelledby="judulFormBuku">
                <div class="card-header">
                    <h2 id="judulFormBuku" class="card-title">
                        <?php echo $editBuku ? 'Edit Data Buku' : 'Tambah Buku Baru'; ?>
                    </h2>
                    <p class="card-desc">Kelola katalog koleksi buku perpustakaan.</p>
                </div>

                <form id="formBuku" class="data-form" method="POST" action="proses.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                    <input type="hidden" name="aksi" value="<?php echo $editBuku ? 'edit_buku' : 'tambah_buku'; ?>">
                    <?php if ($editBuku): ?>
                        <input type="hidden" name="id" value="<?php echo e($editBuku['id']); ?>">
                    <?php endif; ?>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="bukuKode">Kode Buku <span class="req">*</span></label>
                            <input type="text" id="bukuKode" name="kode" required
                                placeholder="Contoh: <?php echo e($kodeBukuSaran); ?>"
                                value="<?php echo e(getOld('kode', $editBuku ? $editBuku['kode'] : $kodeBukuSaran)); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>

                        <div class="form-group">
                            <label for="bukuRak">Lokasi Rak <span class="req">*</span></label>
                            <input type="text" id="bukuRak" name="rak" required
                                placeholder="Contoh: A-01"
                                value="<?php echo e(getOld('rak', $editBuku ? $editBuku['rak'] : '')); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bukuJudul">Judul Buku <span class="req">*</span></label>
                        <input type="text" id="bukuJudul" name="judul" required
                            placeholder="Masukkan judul lengkap buku"
                            value="<?php echo e(getOld('judul', $editBuku ? $editBuku['judul'] : '')); ?>">
                        <small class="error-msg" aria-live="polite"></small>
                    </div>

                    <div class="form-group">
                        <label for="bukuPenulis">Penulis <span class="req">*</span></label>
                        <input type="text" id="bukuPenulis" name="penulis" required
                            placeholder="Nama pengarang / penulis"
                            value="<?php echo e(getOld('penulis', $editBuku ? $editBuku['penulis'] : '')); ?>">
                        <small class="error-msg" aria-live="polite"></small>
                    </div>

                    <div class="form-group">
                        <label for="bukuKategori">Kategori <span class="req">*</span></label>
                        <?php $katDipilih = getOld('kategori', $editBuku ? $editBuku['kategori'] : ''); ?>
                        <select id="bukuKategori" name="kategori" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach (daftarKategoriBuku() as $kat): ?>
                                <option value="<?php echo e($kat); ?>" <?php echo $katDipilih === $kat ? 'selected' : ''; ?>>
                                    <?php echo e($kat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="error-msg" aria-live="polite"></small>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="bukuTahun">Tahun Terbit <span class="req">*</span></label>
                            <input type="number" id="bukuTahun" name="tahun" min="1900" max="<?php echo date('Y') + 1; ?>" required
                                placeholder="2024"
                                value="<?php echo e(getOld('tahun', $editBuku ? $editBuku['tahun'] : date('Y'))); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>

                        <div class="form-group">
                            <label for="bukuStok">Jumlah Stok <span class="req">*</span></label>
                            <input type="number" id="bukuStok" name="stok" min="0" required
                                placeholder="0"
                                value="<?php echo e(getOld('stok', $editBuku ? $editBuku['stok'] : '1')); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editBuku ? 'Simpan Perubahan' : 'Tambah Buku'; ?>
                        </button>
                        <?php if ($editBuku): ?>
                            <a href="index.php?tab=buku" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="card list-panel" aria-labelledby="judulDaftarBuku">
                <div class="card-header list-header">
                    <div>
                        <h2 id="judulDaftarBuku" class="card-title">Daftar Katalog Buku</h2>
                        <p class="card-desc">Menampilkan <?php echo count($daftarBukuTampil); ?> dari <?php echo $totalJudulBuku; ?> judul buku.</p>
                    </div>
                </div>

                <!-- Filter & Pencarian Metode GET -->
                <form class="filter-bar" method="GET" action="index.php">
                    <input type="hidden" name="tab" value="buku">
                    <div class="filter-search">
                        <label for="cariBuku" class="sr-only">Cari buku</label>
                        <input type="search" id="cariBuku" name="q" placeholder="Cari kode, judul, atau penulis..." value="<?php echo e($q); ?>">
                    </div>
                    <div class="filter-selects">
                        <label for="filterKategori" class="sr-only">Filter kategori</label>
                        <select id="filterKategori" name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php foreach (daftarKategoriBuku() as $kat): ?>
                                <option value="<?php echo e($kat); ?>" <?php echo $kategori === $kat ? 'selected' : ''; ?>>
                                    <?php echo e($kat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="filterStatusBuku" class="sr-only">Filter ketersediaan</label>
                        <select id="filterStatusBuku" name="status">
                            <option value="">Semua Stok</option>
                            <option value="tersedia" <?php echo $status === 'tersedia' ? 'selected' : ''; ?>>Tersedia (&gt;1)</option>
                            <option value="terbatas" <?php echo $status === 'terbatas' ? 'selected' : ''; ?>>Terbatas (1)</option>
                            <option value="habis" <?php echo $status === 'habis' ? 'selected' : ''; ?>>Habis (0)</option>
                        </select>

                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        <?php if ($q !== '' || $kategori !== '' || $status !== ''): ?>
                            <a href="index.php?tab=buku" class="btn btn-ghost btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (empty($daftarBukuTampil)): ?>
                    <div class="empty-state">
                        <p>Tidak ada data buku yang sesuai dengan pencarian.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul &amp; Penulis</th>
                                    <th>Kategori</th>
                                    <th>Rak</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftarBukuTampil as $buku): ?>
                                    <?php
                                    if ((int) $buku['stok'] === 0) {
                                        $badgeClass = 'badge-danger';
                                        $badgeText  = 'Habis (0)';
                                    } elseif ((int) $buku['stok'] === 1) {
                                        $badgeClass = 'badge-warning';
                                        $badgeText  = 'Terbatas (1)';
                                    } else {
                                        $badgeClass = 'badge-success';
                                        $badgeText  = 'Tersedia (' . $buku['stok'] . ')';
                                    }
                                    ?>
                                    <tr>
                                        <td data-label="Kode"><span class="code-pill"><?php echo e($buku['kode']); ?></span></td>
                                        <td data-label="Judul & Penulis">
                                            <div class="cell-title"><?php echo e($buku['judul']); ?></div>
                                            <div class="cell-sub"><?php echo e($buku['penulis']); ?> &bull; <?php echo e($buku['tahun']); ?></div>
                                        </td>
                                        <td data-label="Kategori"><span class="tag-pill"><?php echo e($buku['kategori']); ?></span></td>
                                        <td data-label="Rak"><?php echo e($buku['rak']); ?></td>
                                        <td data-label="Stok"><span class="badge <?php echo $badgeClass; ?>"><?php echo e($badgeText); ?></span></td>
                                        <td data-label="Aksi">
                                            <div class="action-group">
                                                <a href="index.php?tab=buku&amp;edit=<?php echo urlencode($buku['id']); ?>" class="btn btn-outline btn-xs">Edit</a>
                                                <form method="POST" action="proses.php" class="inline-form js-confirm" data-confirm="Yakin ingin menghapus buku &quot;<?php echo e($buku['judul']); ?>&quot;?">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                                                    <input type="hidden" name="aksi" value="hapus_buku">
                                                    <input type="hidden" name="id" value="<?php echo e($buku['id']); ?>">
                                                    <button type="submit" class="btn btn-danger btn-xs">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        <?php elseif ($tab === 'anggota'): ?>
            <!-- ================= MODUL DATA ANGGOTA ================= -->
            <section class="card form-panel" aria-labelledby="judulFormAnggota">
                <div class="card-header">
                    <h2 id="judulFormAnggota" class="card-title">
                        <?php echo $editAnggota ? 'Edit Data Anggota' : 'Daftar Anggota Baru'; ?>
                    </h2>
                    <p class="card-desc">Kelola keanggotaan peminjam perpustakaan.</p>
                </div>

                <form id="formAnggota" class="data-form" method="POST" action="proses.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                    <input type="hidden" name="aksi" value="<?php echo $editAnggota ? 'edit_anggota' : 'tambah_anggota'; ?>">
                    <?php if ($editAnggota): ?>
                        <input type="hidden" name="id" value="<?php echo e($editAnggota['id']); ?>">
                    <?php endif; ?>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="anggotaKode">ID Anggota <span class="req">*</span></label>
                            <input type="text" id="anggotaKode" name="kode" required
                                placeholder="Contoh: <?php echo e($kodeAnggotaSaran); ?>"
                                value="<?php echo e(getOld('kode', $editAnggota ? $editAnggota['kode'] : $kodeAnggotaSaran)); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>

                        <div class="form-group">
                            <label for="anggotaNim">NIM / No. Identitas <span class="req">*</span></label>
                            <input type="text" id="anggotaNim" name="nim" required
                                placeholder="Contoh: 17243002"
                                value="<?php echo e(getOld('nim', $editAnggota ? $editAnggota['nim'] : '')); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="anggotaNama">Nama Lengkap <span class="req">*</span></label>
                        <input type="text" id="anggotaNama" name="nama" required
                            placeholder="Masukkan nama lengkap anggota"
                            value="<?php echo e(getOld('nama', $editAnggota ? $editAnggota['nama'] : '')); ?>">
                        <small class="error-msg" aria-live="polite"></small>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="anggotaKelas">Kelas / Prodi <span class="req">*</span></label>
                            <input type="text" id="anggotaKelas" name="kelas" required
                                placeholder="Contoh: TI 5.A"
                                value="<?php echo e(getOld('kelas', $editAnggota ? $editAnggota['kelas'] : '')); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>

                        <div class="form-group">
                            <label for="anggotaStatus">Status <span class="req">*</span></label>
                            <?php $stAnggota = getOld('status', $editAnggota ? $editAnggota['status'] : 'Aktif'); ?>
                            <select id="anggotaStatus" name="status" required>
                                <option value="Aktif" <?php echo $stAnggota === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="Nonaktif" <?php echo $stAnggota === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                            </select>
                            <small class="error-msg" aria-live="polite"></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="anggotaKontak">No. HP / Email <span class="req">*</span></label>
                        <input type="text" id="anggotaKontak" name="kontak" required
                            placeholder="Contoh: 0812-3456-7890"
                            value="<?php echo e(getOld('kontak', $editAnggota ? $editAnggota['kontak'] : '')); ?>">
                        <small class="error-msg" aria-live="polite"></small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editAnggota ? 'Simpan Perubahan' : 'Daftarkan Anggota'; ?>
                        </button>
                        <?php if ($editAnggota): ?>
                            <a href="index.php?tab=anggota" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="card list-panel" aria-labelledby="judulDaftarAnggota">
                <div class="card-header list-header">
                    <div>
                        <h2 id="judulDaftarAnggota" class="card-title">Daftar Anggota Perpustakaan</h2>
                        <p class="card-desc">Menampilkan <?php echo count($daftarAnggotaTampil); ?> dari <?php echo count($data['anggota']); ?> anggota terdaftar.</p>
                    </div>
                </div>

                <!-- Filter & Pencarian Metode GET -->
                <form class="filter-bar" method="GET" action="index.php">
                    <input type="hidden" name="tab" value="anggota">
                    <div class="filter-search">
                        <label for="cariAnggota" class="sr-only">Cari anggota</label>
                        <input type="search" id="cariAnggota" name="q" placeholder="Cari ID, nama, NIM, atau kelas..." value="<?php echo e($q); ?>">
                    </div>
                    <div class="filter-selects">
                        <label for="filterStatusAnggota" class="sr-only">Filter status anggota</label>
                        <select id="filterStatusAnggota" name="status">
                            <option value="">Semua Status</option>
                            <option value="Aktif" <?php echo $status === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="Nonaktif" <?php echo $status === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        <?php if ($q !== '' || $status !== ''): ?>
                            <a href="index.php?tab=anggota" class="btn btn-ghost btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (empty($daftarAnggotaTampil)): ?>
                    <div class="empty-state">
                        <p>Tidak ada data anggota yang ditemukan.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama &amp; NIM</th>
                                    <th>Kelas</th>
                                    <th>Kontak</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftarAnggotaTampil as $agt): ?>
                                    <tr>
                                        <td data-label="ID"><span class="code-pill"><?php echo e($agt['kode']); ?></span></td>
                                        <td data-label="Nama & NIM">
                                            <div class="cell-title"><?php echo e($agt['nama']); ?></div>
                                            <div class="cell-sub">NIM: <?php echo e($agt['nim']); ?></div>
                                        </td>
                                        <td data-label="Kelas"><?php echo e($agt['kelas']); ?></td>
                                        <td data-label="Kontak"><?php echo e($agt['kontak']); ?></td>
                                        <td data-label="Status">
                                            <span class="badge <?php echo $agt['status'] === 'Aktif' ? 'badge-success' : 'badge-muted'; ?>">
                                                <?php echo e($agt['status']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Aksi">
                                            <div class="action-group">
                                                <a href="index.php?tab=anggota&amp;edit=<?php echo urlencode($agt['id']); ?>" class="btn btn-outline btn-xs">Edit</a>
                                                <form method="POST" action="proses.php" class="inline-form js-confirm" data-confirm="Yakin ingin menghapus anggota &quot;<?php echo e($agt['nama']); ?>&quot;?">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                                                    <input type="hidden" name="aksi" value="hapus_anggota">
                                                    <input type="hidden" name="id" value="<?php echo e($agt['id']); ?>">
                                                    <button type="submit" class="btn btn-danger btn-xs">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            <!-- ================= MODUL DATA PEMINJAMAN ================= -->
            <section class="card form-panel" aria-labelledby="judulFormPinjam">
                <div class="card-header">
                    <h2 id="judulFormPinjam" class="card-title">Catat Peminjaman Buku</h2>
                    <p class="card-desc">Stok buku otomatis berkurang saat dipinjam dan bertambah saat dikembalikan.</p>
                </div>

                <form id="formPeminjaman" class="data-form" method="POST" action="proses.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                    <input type="hidden" name="aksi" value="tambah_peminjaman">

                    <div class="form-group">
                        <label for="pinjamAnggota">Anggota Peminjam <span class="req">*</span></label>
                        <?php $oldAnggota = getOld('anggota_id', ''); ?>
                        <select id="pinjamAnggota" name="anggota_id" required>
                            <option value="">-- Pilih Anggota Aktif --</option>
                            <?php foreach ($data['anggota'] as $agt): ?>
                                <?php if ($agt['status'] === 'Aktif'): ?>
                                    <option value="<?php echo e($agt['id']); ?>" <?php echo $oldAnggota === $agt['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($agt['kode'] . ' — ' . $agt['nama'] . ' (' . $agt['kelas'] . ')'); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <small class="error-msg" aria-live="polite"></small>
                    </div>

                    <div class="form-group">
                        <label for="pinjamBuku">Buku yang Dipinjam <span class="req">*</span></label>
                        <?php $oldBuku = getOld('buku_id', ''); ?>
                        <select id="pinjamBuku" name="buku_id" required>
                            <option value="">-- Pilih Buku Tersedia --</option>
                            <?php foreach ($data['buku'] as $bk): ?>
                                <option value="<?php echo e($bk['id']); ?>"
                                    data-stok="<?php echo e($bk['stok']); ?>"
                                    <?php echo ((int) $bk['stok'] <= 0) ? 'disabled' : ''; ?>
                                    <?php echo $oldBuku === $bk['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($bk['kode'] . ' — ' . $bk['judul'] . ' (Stok: ' . $bk['stok'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="error-msg" aria-live="polite"></small>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="pinjamJumlah">Jumlah Pinjam <span class="req">*</span></label>
                            <input type="number" id="pinjamJumlah" name="jumlah" min="1" max="5" required
                                value="<?php echo e(getOld('jumlah', '1')); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>

                        <div class="form-group">
                            <label for="pinjamTanggal">Tanggal Pinjam <span class="req">*</span></label>
                            <input type="date" id="pinjamTanggal" name="tanggal_pinjam" required
                                value="<?php echo e(getOld('tanggal_pinjam', $hariIni)); ?>">
                            <small class="error-msg" aria-live="polite"></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pinjamTempo">Tanggal Jatuh Tempo <span class="req">*</span></label>
                        <input type="date" id="pinjamTempo" name="tanggal_jatuh_tempo" required
                            value="<?php echo e(getOld('tanggal_jatuh_tempo', $jatuhTempoSaran)); ?>">
                        <small class="helper-text">Otomatis disarankan +7 hari dari tanggal pinjam.</small>
                        <small class="error-msg" aria-live="polite"></small>
                    </div>

                    <!-- Pratinjau Ringkasan Peminjaman (Manipulasi DOM JS) -->
                    <div id="previewPeminjaman" class="preview-box" aria-live="polite">
                        <div class="preview-title">Ringkasan Transaksi</div>
                        <p id="previewText" class="preview-text">Pilih anggota dan buku untuk melihat ringkasan peminjaman serta durasi pinjam.</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Catat Peminjaman</button>
                    </div>
                </form>
            </section>

            <section class="card list-panel" aria-labelledby="judulDaftarPinjam">
                <div class="card-header list-header">
                    <div>
                        <h2 id="judulDaftarPinjam" class="card-title">Riwayat &amp; Peminjaman Aktif</h2>
                        <p class="card-desc">Menampilkan <?php echo count($daftarPeminjamanTampil); ?> dari <?php echo count($data['peminjaman']); ?> transaksi.</p>
                    </div>
                </div>

                <!-- Filter & Pencarian Metode GET -->
                <form class="filter-bar" method="GET" action="index.php">
                    <input type="hidden" name="tab" value="peminjaman">
                    <div class="filter-search">
                        <label for="cariPinjam" class="sr-only">Cari peminjaman</label>
                        <input type="search" id="cariPinjam" name="q" placeholder="Cari kode, nama peminjam, atau judul buku..." value="<?php echo e($q); ?>">
                    </div>
                    <div class="filter-selects">
                        <label for="filterStatusPinjam" class="sr-only">Filter status peminjaman</label>
                        <select id="filterStatusPinjam" name="status">
                            <option value="">Semua Status</option>
                            <option value="Dipinjam" <?php echo $status === 'Dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                            <option value="Dikembalikan" <?php echo $status === 'Dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        <?php if ($q !== '' || $status !== ''): ?>
                            <a href="index.php?tab=peminjaman" class="btn btn-ghost btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (empty($daftarPeminjamanTampil)): ?>
                    <div class="empty-state">
                        <p>Belum ada data transaksi peminjaman yang sesuai.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Peminjam &amp; Buku</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftarPeminjamanTampil as $pjm): ?>
                                    <tr>
                                        <td data-label="Kode"><span class="code-pill"><?php echo e($pjm['kode']); ?></span></td>
                                        <td data-label="Peminjam & Buku">
                                            <div class="cell-title"><?php echo e($pjm['nama_anggota']); ?></div>
                                            <div class="cell-sub"><?php echo e($pjm['judul_buku']); ?> (<?php echo e($pjm['jumlah']); ?> eks.)</div>
                                        </td>
                                        <td data-label="Tanggal">
                                            <div class="cell-date">Pinjam: <?php echo e(formatTanggal($pjm['tanggal_pinjam'])); ?></div>
                                            <div class="cell-sub">
                                                <?php if ($pjm['status'] === 'Dikembalikan'): ?>
                                                    Kembali: <?php echo e(formatTanggal($pjm['tanggal_kembali'])); ?>
                                                <?php else: ?>
                                                    Tempo: <?php echo e(formatTanggal($pjm['tanggal_jatuh_tempo'])); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge <?php echo $pjm['status'] === 'Dipinjam' ? 'badge-warning' : 'badge-success'; ?>">
                                                <?php echo e($pjm['status']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Aksi">
                                            <div class="action-group">
                                                <?php if ($pjm['status'] === 'Dipinjam'): ?>
                                                    <form method="POST" action="proses.php" class="inline-form js-confirm" data-confirm="Konfirmasi pengembalian buku untuk transaksi <?php echo e($pjm['kode']); ?>?">
                                                        <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                                                        <input type="hidden" name="aksi" value="kembalikan_buku">
                                                        <input type="hidden" name="id" value="<?php echo e($pjm['id']); ?>">
                                                        <button type="submit" class="btn btn-success btn-xs">Kembalikan</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" action="proses.php" class="inline-form js-confirm" data-confirm="Yakin ingin menghapus riwayat transaksi <?php echo e($pjm['kode']); ?>?">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">
                                                    <input type="hidden" name="aksi" value="hapus_peminjaman">
                                                    <input type="hidden" name="id" value="<?php echo e($pjm['id']); ?>">
                                                    <button type="submit" class="btn btn-danger btn-xs">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </div>
</main>

<?php
clearOld();
include_once __DIR__ . '/includes/footer.php';
?>

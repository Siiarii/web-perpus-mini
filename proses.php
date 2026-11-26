<?php
require_once __DIR__ . '/includes/helpers.php';

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWith('index.php', 'error', 'Akses tidak valid. Gunakan formulir yang tersedia.');
}

// Validasi CSRF Token sederhana berbasis session_id()
$token = isset($_POST['csrf_token']) ? cleanInput($_POST['csrf_token']) : '';
if ($token === '' || $token !== getCsrfToken()) {
    redirectWith('index.php', 'error', 'Sesi formulir tidak valid atau telah kedaluwarsa. Silakan coba lagi.');
}

$aksi = isset($_POST['aksi']) ? cleanInput($_POST['aksi']) : '';
$data = bacaData();

/* ==========================================================================
   MODUL 1: DATA BUKU (tambah_buku, edit_buku, hapus_buku)
   ========================================================================== */
if ($aksi === 'tambah_buku' || $aksi === 'edit_buku') {
    $idLama   = isset($_POST['id']) ? cleanInput($_POST['id']) : '';
    $kode     = strtoupper(cleanInput(isset($_POST['kode']) ? $_POST['kode'] : ''));
    $judul    = cleanInput(isset($_POST['judul']) ? $_POST['judul'] : '');
    $penulis  = cleanInput(isset($_POST['penulis']) ? $_POST['penulis'] : '');
    $kategori = cleanInput(isset($_POST['kategori']) ? $_POST['kategori'] : '');
    $tahun    = cleanInput(isset($_POST['tahun']) ? $_POST['tahun'] : '');
    $stok     = cleanInput(isset($_POST['stok']) ? $_POST['stok'] : '');
    $rak      = strtoupper(cleanInput(isset($_POST['rak']) ? $_POST['rak'] : ''));

    $oldInput = array(
        'kode'     => $kode,
        'judul'    => $judul,
        'penulis'  => $penulis,
        'kategori' => $kategori,
        'tahun'    => $tahun,
        'stok'     => $stok,
        'rak'      => $rak
    );

    $tujuanGagal = ($aksi === 'edit_buku')
        ? 'index.php?tab=buku&edit=' . urlencode($idLama)
        : 'index.php?tab=buku';

    if (empty($kode) || empty($judul) || empty($penulis) || empty($kategori) || empty($rak)) {
        redirectWith($tujuanGagal, 'error', 'Semua kolom wajib buku harus diisi.', $oldInput);
    }

    if (!is_numeric($tahun) || (int) $tahun < 1900 || (int) $tahun > (int) date('Y') + 1) {
        redirectWith($tujuanGagal, 'error', 'Tahun terbit harus berupa angka yang valid.', $oldInput);
    }

    if (!is_numeric($stok) || (int) $stok < 0) {
        redirectWith($tujuanGagal, 'error', 'Jumlah stok harus berupa angka minimal 0.', $oldInput);
    }

    // Cek duplikasi kode buku
    foreach ($data['buku'] as $item) {
        if (strtoupper($item['kode']) === $kode && $item['id'] !== $idLama) {
            redirectWith($tujuanGagal, 'error', 'Kode buku "' . $kode . '" sudah digunakan.', $oldInput);
        }
    }

    if ($aksi === 'tambah_buku') {
        $bukuBaru = array(
            'id'       => $kode,
            'kode'     => $kode,
            'judul'    => $judul,
            'penulis'  => $penulis,
            'kategori' => $kategori,
            'tahun'    => (int) $tahun,
            'stok'     => (int) $stok,
            'rak'      => $rak
        );
        $data['buku'][] = $bukuBaru;
        simpanData($data);
        redirectWith('index.php?tab=buku', 'success', 'Buku "' . $judul . '" berhasil ditambahkan.');
    } else {
        $ditemukan = false;
        foreach ($data['buku'] as $index => $item) {
            if ($item['id'] === $idLama) {
                $data['buku'][$index]['kode']     = $kode;
                $data['buku'][$index]['judul']    = $judul;
                $data['buku'][$index]['penulis']  = $penulis;
                $data['buku'][$index]['kategori'] = $kategori;
                $data['buku'][$index]['tahun']    = (int) $tahun;
                $data['buku'][$index]['stok']     = (int) $stok;
                $data['buku'][$index]['rak']      = $rak;
                $ditemukan = true;
                break;
            }
        }

        if (!$ditemukan) {
            redirectWith('index.php?tab=buku', 'error', 'Data buku tidak ditemukan.');
        }

        simpanData($data);
        redirectWith('index.php?tab=buku', 'success', 'Data buku "' . $judul . '" berhasil diperbarui.');
    }
}

if ($aksi === 'hapus_buku') {
    $id = isset($_POST['id']) ? cleanInput($_POST['id']) : '';

    // Cegah hapus jika buku masih sedang dipinjam
    foreach ($data['peminjaman'] as $pjm) {
        if ($pjm['buku_id'] === $id && $pjm['status'] === 'Dipinjam') {
            redirectWith('index.php?tab=buku', 'error', 'Buku tidak dapat dihapus karena masih ada peminjaman aktif.');
        }
    }

    $bukuBaru = array();
    $terhapus = false;
    foreach ($data['buku'] as $item) {
        if ($item['id'] === $id) {
            $terhapus = true;
            continue;
        }
        $bukuBaru[] = $item;
    }

    if (!$terhapus) {
        redirectWith('index.php?tab=buku', 'error', 'Data buku yang akan dihapus tidak ditemukan.');
    }

    $data['buku'] = $bukuBaru;
    simpanData($data);
    redirectWith('index.php?tab=buku', 'success', 'Data buku berhasil dihapus.');
}

/* ==========================================================================
   MODUL 2: DATA ANGGOTA (tambah_anggota, edit_anggota, hapus_anggota)
   ========================================================================== */
if ($aksi === 'tambah_anggota' || $aksi === 'edit_anggota') {
    $idLama = isset($_POST['id']) ? cleanInput($_POST['id']) : '';
    $kode   = strtoupper(cleanInput(isset($_POST['kode']) ? $_POST['kode'] : ''));
    $nama   = cleanInput(isset($_POST['nama']) ? $_POST['nama'] : '');
    $nim    = cleanInput(isset($_POST['nim']) ? $_POST['nim'] : '');
    $kelas  = cleanInput(isset($_POST['kelas']) ? $_POST['kelas'] : '');
    $kontak = cleanInput(isset($_POST['kontak']) ? $_POST['kontak'] : '');
    $status = cleanInput(isset($_POST['status']) ? $_POST['status'] : 'Aktif');

    $oldInput = array(
        'kode'   => $kode,
        'nama'   => $nama,
        'nim'    => $nim,
        'kelas'  => $kelas,
        'kontak' => $kontak,
        'status' => $status
    );

    $tujuanGagal = ($aksi === 'edit_anggota')
        ? 'index.php?tab=anggota&edit=' . urlencode($idLama)
        : 'index.php?tab=anggota';

    if (empty($kode) || empty($nama) || empty($nim) || empty($kelas) || empty($kontak)) {
        redirectWith($tujuanGagal, 'error', 'Semua kolom data anggota wajib diisi.', $oldInput);
    }

    if ($status !== 'Aktif' && $status !== 'Nonaktif') {
        $status = 'Aktif';
    }

    foreach ($data['anggota'] as $item) {
        if (strtoupper($item['kode']) === $kode && $item['id'] !== $idLama) {
            redirectWith($tujuanGagal, 'error', 'ID Anggota "' . $kode . '" sudah terdaftar.', $oldInput);
        }
    }

    if ($aksi === 'tambah_anggota') {
        $anggotaBaru = array(
            'id'     => $kode,
            'kode'   => $kode,
            'nama'   => $nama,
            'nim'    => $nim,
            'kelas'  => $kelas,
            'kontak' => $kontak,
            'status' => $status
        );
        $data['anggota'][] = $anggotaBaru;
        simpanData($data);
        redirectWith('index.php?tab=anggota', 'success', 'Anggota "' . $nama . '" berhasil didaftarkan.');
    } else {
        $ditemukan = false;
        foreach ($data['anggota'] as $index => $item) {
            if ($item['id'] === $idLama) {
                $data['anggota'][$index]['kode']   = $kode;
                $data['anggota'][$index]['nama']   = $nama;
                $data['anggota'][$index]['nim']    = $nim;
                $data['anggota'][$index]['kelas']  = $kelas;
                $data['anggota'][$index]['kontak'] = $kontak;
                $data['anggota'][$index]['status'] = $status;
                $ditemukan = true;
                break;
            }
        }

        if (!$ditemukan) {
            redirectWith('index.php?tab=anggota', 'error', 'Data anggota tidak ditemukan.');
        }

        simpanData($data);
        redirectWith('index.php?tab=anggota', 'success', 'Data anggota "' . $nama . '" berhasil diperbarui.');
    }
}

if ($aksi === 'hapus_anggota') {
    $id = isset($_POST['id']) ? cleanInput($_POST['id']) : '';

    foreach ($data['peminjaman'] as $pjm) {
        if ($pjm['anggota_id'] === $id && $pjm['status'] === 'Dipinjam') {
            redirectWith('index.php?tab=anggota', 'error', 'Anggota tidak dapat dihapus karena masih memiliki pinjaman aktif.');
        }
    }

    $anggotaBaru = array();
    $terhapus = false;
    foreach ($data['anggota'] as $item) {
        if ($item['id'] === $id) {
            $terhapus = true;
            continue;
        }
        $anggotaBaru[] = $item;
    }

    if (!$terhapus) {
        redirectWith('index.php?tab=anggota', 'error', 'Data anggota tidak ditemukan.');
    }

    $data['anggota'] = $anggotaBaru;
    simpanData($data);
    redirectWith('index.php?tab=anggota', 'success', 'Data anggota berhasil dihapus.');
}

/* ==========================================================================
   MODUL 3: DATA PEMINJAMAN (tambah_peminjaman, kembalikan_buku, hapus_peminjaman)
   ========================================================================== */
if ($aksi === 'tambah_peminjaman') {
    $anggotaId  = cleanInput(isset($_POST['anggota_id']) ? $_POST['anggota_id'] : '');
    $bukuId     = cleanInput(isset($_POST['buku_id']) ? $_POST['buku_id'] : '');
    $jumlah     = cleanInput(isset($_POST['jumlah']) ? $_POST['jumlah'] : '1');
    $tglPinjam  = cleanInput(isset($_POST['tanggal_pinjam']) ? $_POST['tanggal_pinjam'] : '');
    $tglTempo   = cleanInput(isset($_POST['tanggal_jatuh_tempo']) ? $_POST['tanggal_jatuh_tempo'] : '');

    $oldInput = array(
        'anggota_id'          => $anggotaId,
        'buku_id'             => $bukuId,
        'jumlah'              => $jumlah,
        'tanggal_pinjam'      => $tglPinjam,
        'tanggal_jatuh_tempo' => $tglTempo
    );

    if (empty($anggotaId) || empty($bukuId) || empty($tglPinjam) || empty($tglTempo)) {
        redirectWith('index.php?tab=peminjaman', 'error', 'Semua kolom transaksi peminjaman wajib diisi.', $oldInput);
    }

    if (!is_numeric($jumlah) || (int) $jumlah < 1) {
        redirectWith('index.php?tab=peminjaman', 'error', 'Jumlah buku yang dipinjam minimal 1 eksemplar.', $oldInput);
    }

    if ($tglTempo < $tglPinjam) {
        redirectWith('index.php?tab=peminjaman', 'error', 'Tanggal jatuh tempo tidak boleh lebih awal dari tanggal pinjam.', $oldInput);
    }

    $anggota = cariAnggotaById($data['anggota'], $anggotaId);
    if ($anggota === null || $anggota['status'] !== 'Aktif') {
        redirectWith('index.php?tab=peminjaman', 'error', 'Anggota tidak ditemukan atau statusnya tidak aktif.', $oldInput);
    }

    $indexBuku = -1;
    foreach ($data['buku'] as $idx => $item) {
        if ($item['id'] === $bukuId) {
            $indexBuku = $idx;
            break;
        }
    }

    if ($indexBuku === -1) {
        redirectWith('index.php?tab=peminjaman', 'error', 'Buku yang dipilih tidak ditemukan.', $oldInput);
    }

    $qty = (int) $jumlah;
    if ($data['buku'][$indexBuku]['stok'] < $qty) {
        redirectWith('index.php?tab=peminjaman', 'error', 'Stok buku tidak mencukupi (tersedia ' . $data['buku'][$indexBuku]['stok'] . ' eksemplar).', $oldInput);
    }

    // Kurangi stok buku otomatis
    $data['buku'][$indexBuku]['stok'] = $data['buku'][$indexBuku]['stok'] - $qty;

    $kodeBaru = buatKodeOtomatis($data['peminjaman'], 'PJM');
    $transaksiBaru = array(
        'id'                  => $kodeBaru,
        'kode'                => $kodeBaru,
        'anggota_id'          => $anggotaId,
        'buku_id'             => $bukuId,
        'jumlah'              => $qty,
        'tanggal_pinjam'      => $tglPinjam,
        'tanggal_jatuh_tempo' => $tglTempo,
        'tanggal_kembali'     => '',
        'status'              => 'Dipinjam'
    );

    $data['peminjaman'][] = $transaksiBaru;
    simpanData($data);
    redirectWith('index.php?tab=peminjaman', 'success', 'Transaksi peminjaman ' . $kodeBaru . ' berhasil dicatat dan stok buku diperbarui.');
}

if ($aksi === 'kembalikan_buku') {
    $id = isset($_POST['id']) ? cleanInput($_POST['id']) : '';
    $ditemukan = false;

    foreach ($data['peminjaman'] as $idx => $pjm) {
        if ($pjm['id'] === $id) {
            if ($pjm['status'] === 'Dikembalikan') {
                redirectWith('index.php?tab=peminjaman', 'error', 'Buku pada transaksi ini sudah dikembalikan sebelumnya.');
            }

            $data['peminjaman'][$idx]['status'] = 'Dikembalikan';
            $data['peminjaman'][$idx]['tanggal_kembali'] = date('Y-m-d');

            // Kembalikan stok buku otomatis
            foreach ($data['buku'] as $bIdx => $buku) {
                if ($buku['id'] === $pjm['buku_id']) {
                    $data['buku'][$bIdx]['stok'] = $buku['stok'] + (int) $pjm['jumlah'];
                    break;
                }
            }

            $ditemukan = true;
            break;
        }
    }

    if (!$ditemukan) {
        redirectWith('index.php?tab=peminjaman', 'error', 'Transaksi peminjaman tidak ditemukan.');
    }

    simpanData($data);
    redirectWith('index.php?tab=peminjaman', 'success', 'Pengembalian buku berhasil dicatat dan stok buku telah ditambahkan kembali.');
}

if ($aksi === 'hapus_peminjaman') {
    $id = isset($_POST['id']) ? cleanInput($_POST['id']) : '';
    $daftarBaru = array();
    $terhapus = false;

    foreach ($data['peminjaman'] as $pjm) {
        if ($pjm['id'] === $id) {
            // Jika masih berstatus Dipinjam, kembalikan stok buku sebelum dihapus
            if ($pjm['status'] === 'Dipinjam') {
                foreach ($data['buku'] as $bIdx => $buku) {
                    if ($buku['id'] === $pjm['buku_id']) {
                        $data['buku'][$bIdx]['stok'] = $buku['stok'] + (int) $pjm['jumlah'];
                        break;
                    }
                }
            }
            $terhapus = true;
            continue;
        }
        $daftarBaru[] = $pjm;
    }

    if (!$terhapus) {
        redirectWith('index.php?tab=peminjaman', 'error', 'Data peminjaman tidak ditemukan.');
    }

    $data['peminjaman'] = $daftarBaru;
    simpanData($data);
    redirectWith('index.php?tab=peminjaman', 'success', 'Riwayat peminjaman berhasil dihapus.');
}

redirectWith('index.php', 'error', 'Aksi tidak dikenali.');

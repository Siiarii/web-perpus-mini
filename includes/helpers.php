<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DATA_FILE', __DIR__ . '/../data/perpustakaan.json');

/**
 * Escaping HTML untuk mencegah serangan XSS.
 */
function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

/**
 * Membersihkan spasi berlebih pada input form.
 */
function cleanInput($nilai)
{
    return trim((string) $nilai);
}

/**
 * Token CSRF sederhana berbasis session_id().
 */
function getCsrfToken()
{
    return session_id();
}

/**
 * Redirect menggunakan pola PRG (Post-Redirect-Get) dengan flash message dan sticky form.
 */
function redirectWith($url, $tipe, $pesan, $old = array())
{
    $_SESSION['flash'] = array(
        'type' => $tipe,
        'message' => $pesan
    );

    if (!empty($old)) {
        $_SESSION['old'] = $old;
    } else {
        unset($_SESSION['old']);
    }

    header('Location: ' . $url);
    exit;
}

/**
 * Mengambil dan menghapus flash message dari session.
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Mengambil nilai input lama untuk Sticky Form.
 */
function getOld($key, $default = '')
{
    if (isset($_SESSION['old'][$key])) {
        return $_SESSION['old'][$key];
    }
    return $default;
}

/**
 * Menghapus data input lama setelah form selesai ditampilkan.
 */
function clearOld()
{
    unset($_SESSION['old']);
}

/**
 * Data bawaan jika file JSON belum tersedia.
 */
function dataDefault()
{
    return array(
        'buku' => array(
            array(
                'id' => 'BK-001',
                'kode' => 'BK-001',
                'judul' => 'Pemrograman Web Modern dengan HTML5 & CSS3',
                'penulis' => 'Budi Raharjo',
                'kategori' => 'Teknologi',
                'tahun' => 2024,
                'stok' => 5,
                'rak' => 'A-01'
            )
        ),
        'anggota' => array(
            array(
                'id' => 'AGT-001',
                'kode' => 'AGT-001',
                'nama' => 'Faisal Listianto',
                'nim' => '17243002',
                'kelas' => 'TI 5.A',
                'kontak' => '0812-3456-7890',
                'status' => 'Aktif'
            )
        ),
        'peminjaman' => array()
    );
}

/**
 * Membaca data perpustakaan dari file JSON (dengan fallback $_SESSION).
 */
function bacaData()
{
    if (file_exists(DATA_FILE)) {
        $isi = @file_get_contents(DATA_FILE);
        if ($isi !== false) {
            $hasil = json_decode($isi, true);
            if (is_array($hasil) && isset($hasil['buku'], $hasil['anggota'], $hasil['peminjaman'])) {
                $_SESSION['perpustakaan_data'] = $hasil;
                return $hasil;
            }
        }
    }

    if (isset($_SESSION['perpustakaan_data']) && is_array($_SESSION['perpustakaan_data'])) {
        return $_SESSION['perpustakaan_data'];
    }

    $default = dataDefault();
    simpanData($default);
    return $default;
}

/**
 * Menyimpan data perpustakaan ke file JSON dan sinkronisasi ke $_SESSION.
 */
function simpanData($data)
{
    $_SESSION['perpustakaan_data'] = $data;

    $folder = dirname(DATA_FILE);
    if (!is_dir($folder)) {
        @mkdir($folder, 0775, true);
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json !== false) {
        @file_put_contents(DATA_FILE, $json, LOCK_EX);
    }
}

/**
 * Daftar kategori buku yang tersedia.
 */
function daftarKategoriBuku()
{
    return array('Teknologi', 'Fiksi', 'Non-Fiksi', 'Sains', 'Sejarah', 'Referensi');
}

/**
 * Mencari data buku berdasarkan ID atau kode.
 */
function cariBukuById($daftarBuku, $id)
{
    foreach ($daftarBuku as $buku) {
        if ($buku['id'] === $id) {
            return $buku;
        }
    }
    return null;
}

/**
 * Mencari data anggota berdasarkan ID atau kode.
 */
function cariAnggotaById($daftarAnggota, $id)
{
    foreach ($daftarAnggota as $anggota) {
        if ($anggota['id'] === $id) {
            return $anggota;
        }
    }
    return null;
}

/**
 * Membuat kode otomatis berikutnya (contoh: BK-007, AGT-006, PJM-005).
 */
function buatKodeOtomatis($daftar, $prefix)
{
    $maks = 0;
    foreach ($daftar as $item) {
        $bagian = explode('-', $item['kode']);
        if (isset($bagian[1]) && is_numeric($bagian[1])) {
            $angka = (int) $bagian[1];
            if ($angka > $maks) {
                $maks = $angka;
            }
        }
    }
    $berikutnya = $maks + 1;
    return $prefix . '-' . str_pad((string) $berikutnya, 3, '0', STR_PAD_LEFT);
}

/**
 * Memformat tanggal YYYY-MM-DD menjadi format Indonesia singkat.
 */
function formatTanggal($tanggal)
{
    if (empty($tanggal)) {
        return '-';
    }

    $bulan = array(
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    );

    $pecah = explode('-', $tanggal);
    if (count($pecah) === 3 && isset($bulan[$pecah[1]])) {
        return (int) $pecah[2] . ' ' . $bulan[$pecah[1]] . ' ' . $pecah[0];
    }

    return $tanggal;
}

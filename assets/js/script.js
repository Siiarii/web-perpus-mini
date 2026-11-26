// ============================================================================
// Sistem Manajemen Data Interaktif — Web Perpustakaan Mini
// Vanilla JavaScript: Toggle Tema, Menu Mobile, Validasi Form Real-Time & DOM
// ============================================================================

document.addEventListener("DOMContentLoaded", function () {
    // 1. Toggle Tema (Dark / Light Mode) dengan localStorage
    const themeToggle = document.querySelector("#themeToggle");
    const themeLabel = document.querySelector("#themeLabel");

    function terapkanTema(tema) {
        if (tema === "dark") {
            document.body.classList.add("dark-mode");
            if (themeToggle) {
                themeToggle.setAttribute("aria-pressed", "true");
            }
            if (themeLabel) {
                themeLabel.textContent = "Mode Terang";
            }
        } else {
            document.body.classList.remove("dark-mode");
            if (themeToggle) {
                themeToggle.setAttribute("aria-pressed", "false");
            }
            if (themeLabel) {
                themeLabel.textContent = "Mode Gelap";
            }
        }
    }

    const savedTheme = localStorage.getItem("saved-theme");
    terapkanTema(savedTheme === "dark" ? "dark" : "light");

    if (themeToggle) {
        themeToggle.addEventListener("click", function () {
            document.body.classList.toggle("dark-mode");
            const isDark = document.body.classList.contains("dark-mode");
            localStorage.setItem("saved-theme", isDark ? "dark" : "light");
            terapkanTema(isDark ? "dark" : "light");
        });
    }

    // 2. Toggle Menu Navigasi Mobile
    const menuToggle = document.querySelector("#menuToggle");
    const mainNav = document.querySelector("#mainNav");

    if (menuToggle && mainNav) {
        menuToggle.addEventListener("click", function () {
            const terbuka = mainNav.classList.toggle("open");
            menuToggle.setAttribute("aria-expanded", terbuka ? "true" : "false");
        });
    }

    // 3. Tombol Tutup Notifikasi Flash Message
    const alertClose = document.querySelector(".alert-close");
    if (alertClose) {
        alertClose.addEventListener("click", function () {
            const alertBox = alertClose.closest(".alert");
            if (alertBox) {
                alertBox.remove();
            }
        });
    }

    // 4. Konfirmasi Aksi Hapus / Pengembalian Buku
    const confirmForms = document.querySelectorAll(".js-confirm");
    confirmForms.forEach(function (form) {
        form.addEventListener("submit", function (e) {
            const pesan = form.getAttribute("data-confirm") || "Lanjutkan aksi ini?";
            if (!window.confirm(pesan)) {
                e.preventDefault();
            }
        });
    });

    // Helper menampilkan & menghapus pesan error validasi pada input
    function setError(input, pesan) {
        const grup = input.closest(".form-group");
        if (!grup) {
            return;
        }
        const errorEl = grup.querySelector(".error-msg");
        if (pesan) {
            input.classList.add("input-error");
            if (errorEl) {
                errorEl.textContent = pesan;
            }
        } else {
            input.classList.remove("input-error");
            if (errorEl) {
                errorEl.textContent = "";
            }
        }
    }

    // 5. Validasi Form Real-Time: Modul Data Buku
    const formBuku = document.querySelector("#formBuku");
    if (formBuku) {
        const bukuKode = document.querySelector("#bukuKode");
        const bukuRak = document.querySelector("#bukuRak");
        const bukuJudul = document.querySelector("#bukuJudul");
        const bukuPenulis = document.querySelector("#bukuPenulis");
        const bukuKategori = document.querySelector("#bukuKategori");
        const bukuTahun = document.querySelector("#bukuTahun");
        const bukuStok = document.querySelector("#bukuStok");

        function validasiBukuField(field) {
            const val = field.value.trim();

            if (field === bukuKode && val === "") {
                setError(field, "Kode buku wajib diisi.");
                return false;
            }
            if (field === bukuRak && val === "") {
                setError(field, "Lokasi rak wajib diisi.");
                return false;
            }
            if (field === bukuJudul && val.length < 2) {
                setError(field, "Judul buku minimal 2 karakter.");
                return false;
            }
            if (field === bukuPenulis && val.length < 2) {
                setError(field, "Nama penulis wajib diisi.");
                return false;
            }
            if (field === bukuKategori && val === "") {
                setError(field, "Silakan pilih kategori buku.");
                return false;
            }
            if (field === bukuTahun) {
                const tahun = Number(val);
                const tahunMaks = new Date().getFullYear() + 1;
                if (val === "" || isNaN(tahun) || tahun < 1900 || tahun > tahunMaks) {
                    setError(field, "Masukkan tahun terbit antara 1900 hingga " + tahunMaks + ".");
                    return false;
                }
            }
            if (field === bukuStok) {
                const stok = Number(val);
                if (val === "" || isNaN(stok) || stok < 0) {
                    setError(field, "Jumlah stok tidak boleh kurang dari 0.");
                    return false;
                }
            }

            setError(field, "");
            return true;
        }

        const fieldsBuku = [bukuKode, bukuRak, bukuJudul, bukuPenulis, bukuKategori, bukuTahun, bukuStok];
        fieldsBuku.forEach(function (field) {
            if (!field) {
                return;
            }
            const eventName = field.tagName === "SELECT" ? "change" : "input";
            field.addEventListener(eventName, function () {
                validasiBukuField(field);
            });
        });

        formBuku.addEventListener("submit", function (e) {
            let valid = true;
            fieldsBuku.forEach(function (field) {
                if (field && !validasiBukuField(field)) {
                    valid = false;
                }
            });
            if (!valid) {
                e.preventDefault();
            }
        });
    }

    // 6. Validasi Form Real-Time: Modul Data Anggota
    const formAnggota = document.querySelector("#formAnggota");
    if (formAnggota) {
        const anggotaKode = document.querySelector("#anggotaKode");
        const anggotaNim = document.querySelector("#anggotaNim");
        const anggotaNama = document.querySelector("#anggotaNama");
        const anggotaKelas = document.querySelector("#anggotaKelas");
        const anggotaKontak = document.querySelector("#anggotaKontak");

        function validasiAnggotaField(field) {
            const val = field.value.trim();

            if (field === anggotaKode && val === "") {
                setError(field, "ID anggota wajib diisi.");
                return false;
            }
            if (field === anggotaNim && val.length < 4) {
                setError(field, "NIM / No. Identitas minimal 4 karakter.");
                return false;
            }
            if (field === anggotaNama && val.length < 3) {
                setError(field, "Nama lengkap minimal 3 karakter.");
                return false;
            }
            if (field === anggotaKelas && val === "") {
                setError(field, "Kelas atau program studi wajib diisi.");
                return false;
            }
            if (field === anggotaKontak && val.length < 6) {
                setError(field, "Masukkan nomor HP atau email yang valid.");
                return false;
            }

            setError(field, "");
            return true;
        }

        const fieldsAnggota = [anggotaKode, anggotaNim, anggotaNama, anggotaKelas, anggotaKontak];
        fieldsAnggota.forEach(function (field) {
            if (!field) {
                return;
            }
            field.addEventListener("input", function () {
                validasiAnggotaField(field);
            });
        });

        formAnggota.addEventListener("submit", function (e) {
            let valid = true;
            fieldsAnggota.forEach(function (field) {
                if (field && !validasiAnggotaField(field)) {
                    valid = false;
                }
            });
            if (!valid) {
                e.preventDefault();
            }
        });
    }

    // 7. Validasi Form Real-Time & Manipulasi DOM: Modul Data Peminjaman
    const formPeminjaman = document.querySelector("#formPeminjaman");
    if (formPeminjaman) {
        const pinjamAnggota = document.querySelector("#pinjamAnggota");
        const pinjamBuku = document.querySelector("#pinjamBuku");
        const pinjamJumlah = document.querySelector("#pinjamJumlah");
        const pinjamTanggal = document.querySelector("#pinjamTanggal");
        const pinjamTempo = document.querySelector("#pinjamTempo");
        const previewText = document.querySelector("#previewText");

        // Hitung otomatis tanggal jatuh tempo (+7 hari) saat tanggal pinjam berubah
        function perbaruiJatuhTempoOtomatis() {
            if (!pinjamTanggal || !pinjamTempo || !pinjamTanggal.value) {
                return;
            }
            const tgl = new Date(pinjamTanggal.value + "T00:00:00");
            if (!isNaN(tgl.getTime())) {
                tgl.setDate(tgl.getDate() + 7);
                const tahun = tgl.getFullYear();
                const bulan = String(tgl.getMonth() + 1).padStart(2, "0");
                const hari = String(tgl.getDate()).padStart(2, "0");
                pinjamTempo.value = tahun + "-" + bulan + "-" + hari;
            }
        }

        // Perbarui kotak pratinjau ringkasan peminjaman di DOM
        function perbaruiPratinjau() {
            if (!previewText) {
                return;
            }
            const anggotaOpt = pinjamAnggota.options[pinjamAnggota.selectedIndex];
            const bukuOpt = pinjamBuku.options[pinjamBuku.selectedIndex];
            const jumlah = Number(pinjamJumlah.value) || 0;
            const tglPinjam = pinjamTanggal.value;
            const tglTempo = pinjamTempo.value;

            if (!pinjamAnggota.value || !pinjamBuku.value) {
                previewText.textContent = "Pilih anggota dan buku untuk melihat ringkasan peminjaman serta durasi pinjam.";
                return;
            }

            let durasiTeks = "";
            if (tglPinjam && tglTempo) {
                const mulai = new Date(tglPinjam + "T00:00:00");
                const selesai = new Date(tglTempo + "T00:00:00");
                const selisihHari = Math.round((selesai - mulai) / (1000 * 60 * 60 * 24));
                if (selisihHari >= 0) {
                    durasiTeks = " selama " + selisihHari + " hari (jatuh tempo " + tglTempo + ")";
                }
            }

            previewText.textContent =
                anggotaOpt.text + " meminjam " + jumlah + " eksemplar \"" + bukuOpt.text + "\"" + durasiTeks + ".";
        }

        function validasiPinjamField(field) {
            const val = field.value.trim();

            if (field === pinjamAnggota && val === "") {
                setError(field, "Silakan pilih anggota peminjam.");
                return false;
            }
            if (field === pinjamBuku && val === "") {
                setError(field, "Silakan pilih buku yang akan dipinjam.");
                return false;
            }
            if (field === pinjamJumlah) {
                const qty = Number(val);
                const bukuOpt = pinjamBuku.options[pinjamBuku.selectedIndex];
                const stokTersedia = bukuOpt ? Number(bukuOpt.getAttribute("data-stok")) : 0;

                if (val === "" || isNaN(qty) || qty < 1) {
                    setError(field, "Jumlah pinjam minimal 1 eksemplar.");
                    return false;
                }
                if (pinjamBuku.value !== "" && qty > stokTersedia) {
                    setError(field, "Jumlah melebihi stok tersedia (" + stokTersedia + " eks.).");
                    return false;
                }
            }
            if (field === pinjamTanggal && val === "") {
                setError(field, "Tanggal pinjam wajib diisi.");
                return false;
            }
            if (field === pinjamTempo) {
                if (val === "") {
                    setError(field, "Tanggal jatuh tempo wajib diisi.");
                    return false;
                }
                if (pinjamTanggal.value && val < pinjamTanggal.value) {
                    setError(field, "Tanggal jatuh tempo tidak boleh sebelum tanggal pinjam.");
                    return false;
                }
            }

            setError(field, "");
            return true;
        }

        if (pinjamTanggal) {
            pinjamTanggal.addEventListener("change", function () {
                perbaruiJatuhTempoOtomatis();
                validasiPinjamField(pinjamTanggal);
                validasiPinjamField(pinjamTempo);
                perbaruiPratinjau();
            });
        }

        const fieldsPinjam = [pinjamAnggota, pinjamBuku, pinjamJumlah, pinjamTempo];
        fieldsPinjam.forEach(function (field) {
            if (!field) {
                return;
            }
            const eventName = field.tagName === "SELECT" || field.type === "date" ? "change" : "input";
            field.addEventListener(eventName, function () {
                validasiPinjamField(field);
                if (field === pinjamBuku) {
                    validasiPinjamField(pinjamJumlah);
                }
                perbaruiPratinjau();
            });
        });

        perbaruiPratinjau();

        formPeminjaman.addEventListener("submit", function (e) {
            let valid = true;
            const semua = [pinjamAnggota, pinjamBuku, pinjamJumlah, pinjamTanggal, pinjamTempo];
            semua.forEach(function (field) {
                if (field && !validasiPinjamField(field)) {
                    valid = false;
                }
            });
            if (!valid) {
                e.preventDefault();
            }
        });
    }
});

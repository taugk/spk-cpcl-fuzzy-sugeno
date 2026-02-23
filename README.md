# SPK Pemilihan CPCL - Metode Fuzzy Sugeno Orde Nol

Sistem Pendukung Keputusan (SPK) untuk menentukan kelayakan **Calon Petani Calon Lokasi (CPCL)** menggunakan metode **Fuzzy Sugeno Orde Nol**. Aplikasi ini dirancang untuk mendigitalisasi proses verifikasi dan penilaian kelompok tani secara transparan dan akurat.

## 🚀 Fitur Utama

- **Dashboard Statistik**: Monitoring jumlah CPCL masuk dan status verifikasi.
- **Manajemen Kriteria**: Pengaturan dinamis kriteria (C1-C5) dan sub-kriteria (Himpunan Fuzzy).
- **Verifikasi CPCL**: Alur kerja verifikasi data lapangan oleh Admin/UPTD.
- **Engine Fuzzy Sugeno**: Perhitungan otomatis menggunakan fungsi keanggotaan (Bahu & Trapesium).
- **Audit Transparansi**: Visualisasi langkah-demi-langkah proses perhitungan matematis (Fuzzifikasi, Inferensi, Defuzzifikasi).

## 🛠️ Teknologi yang Digunakan

- **Framework**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL / MariaDB
- **Frontend**: Bootstrap / Sneat Admin Template
- **Library**: Chart.js (Visualisasi Kurva Fuzzy)

## 📋 Persyaratan Sistem

- PHP >= 8.2
- Composer
- MySQL/MariaDB
- Web Server (Apache/Nginx)

## 🔧 Instalasi Proyek

1. **Clone Repositori**

    ```bash
    git clone [https://github.com/username/spk-cpcl-fuzzy-sugeno.git](https://github.com/username/spk-cpcl-fuzzy-sugeno.git)
    cd spk-cpcl-fuzzy-sugeno
    ```

2. **Instalasi Dependency**

    ```bash
    composer install
    npm install && npm run build
    ```

3. **Konfigurasi Environment**
   Salin file `.env.example` menjadi `.env` dan sesuaikan pengaturan database Anda.

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Migrasi & Seeding**
   Laravel 12 akan otomatis menanyakan pembuatan database jika belum ada.

    ```bash
    php artisan migrate --seed
    ```

5. **Jalankan Aplikasi**

    ```bash
    php artisan serve
    ```

## 📐 Metodologi SPK

Proyek ini menerapkan tahapan Fuzzy Sugeno sebagai berikut:

1. **Fuzzifikasi**: Transformasi nilai real (Ha, Tahun, Ton) ke variabel linguistik.
2. **Inferensi**: Menggunakan operator **MIN** (Firing Strength) untuk mendapatkan nilai α-predikat.
3. **Defuzzifikasi**: Menggunakan metode **Weighted Average** untuk menghasilkan skor tegas (Z) sebagai penentu kelayakan.

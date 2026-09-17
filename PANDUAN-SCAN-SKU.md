# Fitur Scan SKU dan Barcode

Fitur scan aktif tersedia pada:

- Master Data Produk: menambahkan dan mengganti barcode produk.
- Master Data Produk: mencari produk menggunakan SKU atau barcode.
- Purchase Request: memilih produk dari hasil scan.
- Purchase Order: memilih PR disetujui yang memuat produk hasil scan.
- Penerimaan Barang: memilih PO aktif yang memuat produk hasil scan.
- Penyesuaian Stok: memilih produk dari hasil scan.

## Perbedaan SKU dan barcode

- SKU internal, misalnya `SP-000001`, dibuat otomatis oleh sistem dan tidak perlu diketik.
- Barcode atau kode scan berasal dari label fisik produk dan dapat dipindai saat produk ditambahkan.
- SKU dan barcode sama-sama dapat digunakan untuk menemukan produk pada transaksi.
- Barcode tidak boleh sama pada dua produk.

## Cara menggunakan scanner

1. Tekan tombol **Scan SKU** atau **Scan** di samping kolom.
2. Untuk scanner USB/Bluetooth, scan kode ketika kolom pada jendela scanner aktif. Kode biasanya diproses otomatis setelah scanner mengirim Enter.
3. Untuk kamera, tekan **Aktifkan Kamera**, izinkan akses kamera, lalu arahkan kode ke bingkai.
4. Jika kamera tidak didukung, ketik kode dan tekan Enter.

Kamera dapat digunakan pada `localhost`. Setelah deployment, situs harus memakai HTTPS agar browser mengizinkan akses kamera.

## Pemasangan aman

Gabungkan paket pembaruan ke project utama menggunakan `ditto`, bukan mengganti seluruh folder:

```bash
ditto "/lokasi/safar-point-scan-sku-update" "/Users/safar/Documents/safar-point-management"
cd "/Users/safar/Documents/safar-point-management"
php artisan optimize:clear
php artisan test
php artisan serve
```

Tidak ada migration baru dan tidak ada perubahan pada `.env` atau database. Data lama tetap digunakan.

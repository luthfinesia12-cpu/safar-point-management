# Frontend Baru Safar Point Management

Pembaruan ini mengubah tampilan tanpa menghapus database atau transaksi.

## Yang sudah aktif

- Sidebar responsif dengan menu berdasarkan permission pengguna.
- Dashboard, Master Data, PR, PO, Penerimaan, ADJ, Pembayaran, Pengguna, Role, Audit, Pengaturan, login, dan ganti password.
- Form tambah/edit berbentuk panel yang tetap mengirim ke controller Laravel asli.
- Pencarian master data dari server dan pencarian cepat pada tabel.
- Filter, pagination, ekspor, status badge, pesan sukses/error, serta tampilan HP.
- Pemilihan produk otomatis dari PR ke PO dan dari PO ke penerimaan.

## Setelah menyalin pembaruan

Jalankan dari terminal folder proyek:

```bash
npm install
npm run build
php artisan optimize:clear
php artisan test
php artisan serve
```

Buka `http://127.0.0.1:8000` dan lakukan hard refresh dengan `Command + Shift + R`.

## Keamanan data

Paket pembaruan tidak berisi `.env`, database, backup, `vendor`, `node_modules`, atau metadata Git. Jangan menjalankan `migrate:fresh`, `db:wipe`, `migrate:reset`, atau `migrate:rollback`.

# Fase 0: Blueprint Safar Point Management System

**Status:** Dokumen acuan Rilis 1  
**Tanggal:** 15 September 2026  
**Ruang lingkup:** Fondasi operasional pembelian, penerimaan, penyesuaian stok, dan pembayaran.  
**Di luar cakupan:** Modul fase berikutnya, migrasi data lama, dan integrasi eksternal.

## 1. Keputusan Resmi

Keputusan berikut menjadi batasan dan acuan desain Rilis 1:

1. Semua pengajuan pembelian wajib disetujui Owner/Direksi.
2. Format nomor dokumen:
   - PR: `PR/SP/YYYY/MM/00001`
   - PO: `PO/SP/YYYY/MM/00001`
   - GR: `GR/SP/YYYY/MM/00001`
   - ADJ: `ADJ/SP/YYYY/MM/00001`
3. Nomor dokumen otomatis reset setiap bulan, immutable setelah diterbitkan, dan tidak boleh digunakan ulang.
4. Finance Rilis 1 mencakup pembayaran PO dengan status:
   - `BELUM DIBAYAR`
   - `DP`
   - `LUNAS`
5. Pembayaran mendukung beberapa kali DP, rekening sumber, bukti transfer, nota/kwitansi, PIC, verifikasi, dan audit.
6. Role resmi:
   - Super Admin
   - Owner/Direksi
   - Admin Master Data
   - Purchasing
   - Gudang
   - Finance
   - Viewer/Auditor
7. Fase 6 migrasi data lama tidak digunakan. Sistem dimulai dengan data baru.
8. Integrasi Olsera, SISO, dan marketplace tidak termasuk Rilis 1.
9. Setiap PR wajib memperoleh satu persetujuan dari salah satu Owner/Direksi aktif. Pembuat tidak boleh menyetujui pengajuannya sendiri. Super Admin tidak menjadi approver bisnis kecuali juga memiliki role Owner/Direksi.
10. PO tidak memerlukan approval ulang jika seluruh isinya sama dengan PR yang disetujui. Approval ulang wajib jika produk, varian, jumlah, supplier, harga, diskon, pajak, ongkir, atau total berbeda dari PR yang disetujui. Perubahan catatan internal dan estimasi kedatangan adalah perubahan non-material yang dapat dilakukan Purchasing dengan alasan dan dicatat di Audit Log tanpa membuat versi baru.
11. Rilis 1 tidak menggunakan tier nominal. Semua nominal mengikuti satu persetujuan Owner/Direksi.
12. Perubahan material pada PR/PO yang sudah disetujui harus membuat versi revisi, menyimpan histori, dan melalui approval ulang.
13. Pembatalan mengikuti status dan role:
   - `DRAFT` dapat dibatalkan pembuat.
   - `DIAJUKAN` memerlukan alasan dan persetujuan Owner/Direksi.
   - `DISETUJUI` atau dokumen yang sudah menjadi PO hanya dapat dibatalkan Owner/Direksi.
   - GR atau ADJ yang sudah memengaruhi stok tidak boleh dihapus/dibatalkan langsung; wajib transaksi pembalik.
   - Dokumen yang memiliki pembayaran wajib melibatkan Finance dan Owner/Direksi.
14. PO memakai status `DIBATALKAN`. Status pembayaran keseluruhan PO hanya `BELUM DIBAYAR`, `DP`, atau `LUNAS`. Transaksi pembayaran individual memakai `MENUNGGU VERIFIKASI`, `TERVERIFIKASI`, `DITOLAK`, atau `DIBATALKAN`. PO yang dibatalkan tidak dapat menerima pembayaran baru.
15. `LUNAS` dihitung otomatis jika total pembayaran `TERVERIFIKASI` sama persis dengan total final PO setelah diskon, pajak, ongkir, dan pembulatan. Status ini tidak dapat dipilih manual dan tidak menggunakan toleransi selisih.
16. Pembayaran berlebih, refund, dan pemotongan pajak khusus tidak termasuk Rilis 1. Sistem menolak pembayaran melebihi tagihan. Kesalahan dikoreksi dengan membatalkan transaksi dan membuat transaksi pengganti tanpa menghapus riwayat.
17. Metode pembayaran Rilis 1 adalah `TRANSFER BANK` dan `TUNAI`. Rekening menjadi Master Data Finance berisi bank, nomor rekening, nama pemilik, dan status. Transfer wajib memilih rekening sumber serta bukti transfer. Tunai wajib mencatat penerima dan PIC Finance.
18. Lampiran mendukung PDF, JPG, JPEG, PNG, dan WEBP; maksimal 5 MB per file dan 10 file per dokumen. Nama asli disimpan sebagai metadata, nama penyimpanan dibuat acak, dan lampiran transaksi tidak dihapus permanen. Penggantian membuat versi/riwayat baru.
19. Pembayaran dicatat oleh PIC Finance, dapat diperbaiki oleh Finance sebelum verifikasi, dan diajukan setelah data serta bukti lengkap. Pembayaran wajib diverifikasi oleh Owner/Direksi yang berbeda. Pembuat tidak boleh memverifikasi sendiri. Pembayaran hanya dihitung setelah berstatus `TERVERIFIKASI`.
20. GR parsial diperbolehkan berkali-kali dan jumlah kumulatif tidak boleh melebihi PO. PO tetap terbuka sampai lengkap atau ditutup dengan status `TUTUP KURANG`. Purchasing mengajukan Tutup Kurang dengan alasan dan Owner/Direksi menyetujuinya.
21. Stok negatif dilarang tanpa pengecualian. Semua transaksi yang membuat saldo negatif ditolak. ADJ dibuat Gudang dengan alasan dan bukti, serta wajib disetujui Owner/Direksi sebelum masuk stock ledger. Koreksi ADJ menggunakan transaksi pembalik.
22. Data dimulai baru tanpa migrasi. Kode otomatis dan immutable: produk `PRD-000001`, SKU `SP-000001`, supplier `SUP-00001`, kategori `CAT-001`, brand `BRD-001`, lokasi `WH-001`, dan satuan `UNT-001`. Barcode wajib unik, dapat dimasukkan manual atau dibuat dari SKU. Import menggunakan template tervalidasi dan preview error.
23. Export Excel dan PDF wajib. Export mengikuti filter dan permission. PDF memuat identitas perusahaan, judul, periode, waktu cetak, filter, data, total, dan pembuat. Excel berisi data detail siap analisis. Angka export wajib sama dengan dashboard dan database.
24. Audit Log disimpan minimal 10 tahun dan tidak dapat diedit atau dihapus melalui aplikasi. Backup database otomatis dilakukan harian dengan retensi harian 30 hari, mingguan 12 minggu, dan bulanan 12 bulan. Database serta lampiran dibackup terenkripsi ke lokasi terpisah. RPO maksimal 24 jam dan RTO maksimal 4 jam. Rollback mencakup kode, database, konfigurasi, dan lampiran. Uji restore dilakukan setiap 3 bulan dan mencatat tanggal, pelaksana, hasil, durasi, dan masalah.
25. Password minimal 12 karakter. Tidak ada kedaluwarsa password berkala secara paksa. Password wajib diganti pada login pertama, setelah reset, atau jika terindikasi bocor. Idle timeout 30 menit dan batas session 8 jam. MFA wajib untuk Super Admin, Owner/Direksi, dan Finance; opsional untuk role lain. Recovery code disediakan.
26. Zona waktu resmi adalah `Asia/Jakarta`. Tampilan menggunakan WIB dan waktu transaksi yang sudah diposting tidak dapat diedit manual.
27. Rilis 1 memakai notifikasi dalam aplikasi dan email untuk approval, penolakan, revisi, pembatalan, stok minimum, outstanding, dan pembayaran yang perlu diverifikasi. Minimum stok ditentukan per SKU pada field `minimum_stock`; notifikasi dikirim kepada Gudang, Purchasing, dan Owner/Direksi. PO outstanding aktif ketika estimasi kedatangan terlewati; notifikasi dikirim kepada Purchasing dan Owner/Direksi. Pembayaran yang menunggu verifikasi diberitahukan kepada Owner/Direksi. WhatsApp/SMS ditunda. Kegagalan email dicatat dan dapat dikirim ulang.
28. Production menggunakan Hostinger melalui domain/subdomain resmi dan HTTPS. Domain final ditentukan pada Fase 8. VPN tidak wajib. Akses dilindungi autentikasi, role/permission, MFA, rate limit, dan audit. Staging terpisah disediakan, debug production nonaktif, serta backup dan rollback tersedia sesuai RPO maksimal 24 jam dan RTO maksimal 4 jam.
29. PR yang sudah dikirim berstatus `DIAJUKAN`, yang berarti sedang menunggu persetujuan Owner/Direksi. Perubahan material PR/PO adalah perubahan produk, varian, jumlah, supplier, harga, diskon, pajak, ongkir, atau total; perubahan tersebut wajib membuat versi baru dan approval ulang. Perubahan non-material hanya catatan internal dan estimasi kedatangan, dapat dilakukan Purchasing dengan alasan dan Audit Log tanpa versi baru.
30. `DRAFT` tidak digunakan untuk transaksi pembayaran Rilis 1. Setelah data dan bukti lengkap dikirim, pembayaran langsung berstatus `MENUNGGU VERIFIKASI`.
31. Finance memiliki izin membuat, memperbaiki sebelum verifikasi, dan mengajukan pembayaran. Permission verifikasi pembayaran hanya untuk Owner/Direksi. Pembuat tidak boleh memverifikasi sendiri.
32. Super Admin memiliki akses teknis penuh, tetapi bypass tidak berlaku untuk approval bisnis dan verifikasi pembayaran. Approval PR, PO, dan ADJ serta verifikasi pembayaran tetap memerlukan role Owner/Direksi.
33. Gudang dapat membuat ADJ dan melihat statusnya, tetapi tidak dapat menyetujui atau memverifikasi ADJ. Owner/Direksi menyetujui ADJ dan sistem mempostingnya otomatis ke stock ledger.
34. Pembayaran tunai memiliki field `penerima_pembayaran`, wajib diisi untuk metode `TUNAI`, dan tidak digunakan untuk `TRANSFER BANK`.
35. GR dan ADJ memiliki `posted_at` serta `posted_by`. GR diposting oleh Gudang setelah data penerimaan lengkap. ADJ diposting otomatis setelah approval Owner/Direksi. Pembayaran memiliki `verified_at` dan `verified_by`. Waktu tersebut dibuat sistem, tidak dapat diedit, dan transaksi yang sudah diposting tidak dapat diubah langsung.
36. Owner/Direksi aktif adalah pengguna dengan `is_active=true` dan role Owner/Direksi. Role diberikan atau dicabut oleh Super Admin. Sistem wajib menjaga minimal satu Owner/Direksi aktif dan melarang penonaktifan atau pencabutan role pada Owner/Direksi aktif terakhir.

## 2. Ruang Lingkup Rilis 1

### Termasuk

- Autentikasi, akun aktif, penggantian password wajib, role, permission, dan audit aktivitas.
- Master data dasar yang dibutuhkan transaksi pembelian.
- Pembuatan dan persetujuan Purchase Request (PR).
- Pembuatan Purchase Order (PO) berdasarkan PR yang disetujui.
- Penerimaan barang melalui Goods Receipt (GR).
- Penyesuaian stok melalui Adjustment (ADJ) dengan alasan dan audit.
- Pencatatan pembayaran PO, termasuk DP bertahap dan verifikasi bukti.
- Dashboard dan laporan operasional Rilis 1.
- Penomoran dokumen terpusat dan immutable.

### Tidak termasuk

- Migrasi data dari sistem lama.
- Sinkronisasi Olsera, SISO, marketplace, atau integrasi pihak ketiga lainnya.
- Modul penjualan, akuntansi penuh, payroll, produksi, atau pengiriman.
- Otomatisasi pembayaran ke bank.
- Perubahan atau penghapusan data transaksi yang menghilangkan histori audit.

## 3. Matriks Role dan Permission

Keterangan: `Lihat` membaca data, `Buat` membuat draft, `Ubah` mengubah data yang masih boleh diubah, `Ajukan` mengirim untuk proses berikutnya, `Setujui` memberi persetujuan resmi, `Proses` menjalankan aktivitas operasional, `Verifikasi` memvalidasi bukti atau hasil, `Kelola` mencakup konfigurasi dan administrasi.

| Modul / aksi | Super Admin | Owner/Direksi | Admin Master Data | Purchasing | Gudang | Finance | Viewer/Auditor |
|---|---|---|---|---|---|---|---|
| Dashboard | Kelola | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |
| Pengguna, role, permission | Kelola | Lihat | - | - | - | - | Lihat |
| Audit log | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |
| Master barang/satuan/kategori | Kelola | Lihat | Kelola | Lihat | Lihat | Lihat | Lihat |
| Master supplier | Kelola | Lihat | Kelola | Lihat | Lihat | Lihat | Lihat |
| Master gudang/lokasi | Kelola | Lihat | Kelola | Lihat | Kelola terbatas | Lihat | Lihat |
| Rekening sumber pembayaran | Kelola | Lihat | - | - | - | Kelola |
| Buat PR | Kelola | Kelola | Lihat | Buat/Ubah/Ajukan | - | Lihat | - |
| Lihat PR | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |
| Persetujuan PR | Kelola teknis, bukan approval bisnis | Setujui/Tolak | - | - | - | - | - |
| Buat PO | Kelola | Lihat | - | Buat/Ubah/Ajukan | - | Lihat | - |
| Persetujuan ulang PO karena perubahan material | Kelola teknis, bukan approval bisnis | Setujui/Tolak | - | - | - | - | - |
| Lihat PO | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |
| Buat GR | Kelola | Lihat | - | Lihat | Proses/Buat | - | - |
| Posting GR | Kelola teknis, bukan posting bisnis | Lihat | - | - | Proses/Posting | - | Lihat |
| Buat ADJ | Kelola | Lihat | - | - | Buat/Ajukan | - | - |
| Persetujuan ADJ | Kelola teknis, bukan approval bisnis | Setujui/Tolak | - | - | Lihat status | - | - |
| Catat, perbaiki sebelum verifikasi, dan ajukan pembayaran PO | Kelola teknis, bukan approval bisnis | Lihat | - | Lihat | - | Buat/Ubah/Ajukan | - |
| Upload bukti pembayaran | Kelola teknis | Lihat | - | Lihat | - | Proses | - |
| Verifikasi pembayaran | Kelola teknis, bukan verifikasi bisnis | Verifikasi | - | - | - | - | - |
| Laporan operasional | Kelola | Lihat | Lihat | Lihat | Lihat | Lihat | Lihat |

**Catatan otorisasi:** Super Admin memiliki akses teknis penuh, tetapi bypass authorization tidak berlaku untuk approval bisnis atau verifikasi pembayaran. Approval PR, approval ulang PO, approval ADJ, dan verifikasi pembayaran tetap memerlukan role Owner/Direksi. Role Super Admin terkunci dan tidak dapat dihapus. Role Owner/Direksi diberikan atau dicabut oleh Super Admin. Sistem menjaga minimal satu Owner/Direksi aktif dan melarang penonaktifan atau pencabutan role pada Owner/Direksi aktif terakhir. Tidak ada aksi penghapusan permanen user atau transaksi pada Rilis 1.

## 4. Workflow Utama

### 4.1 Purchase Request (PR)

1. Purchasing membuat PR sebagai `DRAFT`.
2. PR dilengkapi barang, kuantitas, kebutuhan, dan catatan.
3. Purchasing mengajukan PR menjadi `DIAJUKAN`, yang berarti sedang menunggu persetujuan Owner/Direksi.
4. Owner/Direksi meninjau dan memilih `DISETUJUI` atau `DITOLAK`.
5. PR yang ditolak menyimpan alasan dan dapat diperbaiki sebagai pengajuan baru atau revisi material dengan versi baru dan approval ulang.
6. PR yang disetujui dapat menjadi dasar PO.

**Aturan wajib:** Tidak ada PR yang dapat diproses menjadi PO sebelum persetujuan Owner/Direksi tercatat.

### 4.2 Purchase Order (PO)

1. Purchasing membuat PO dari PR yang sudah disetujui.
2. Purchasing melengkapi supplier, alamat kirim, item, harga, pajak/diskon bila berlaku, dan jadwal kirim.
3. Sistem menerbitkan nomor PO saat dokumen masuk status final/terbit.
4. PO menjadi acuan penerimaan barang dan pembayaran.
5. Perubahan material setelah terbit, yaitu produk, varian, jumlah, supplier, harga, diskon, pajak, ongkir, atau total, hanya melalui versi baru yang menyimpan histori dan memerlukan approval ulang. Perubahan non-material hanya catatan internal dan estimasi kedatangan, dapat dilakukan Purchasing dengan alasan dan Audit Log tanpa versi baru.

### 4.3 Goods Receipt (GR)

1. Gudang memilih PO yang akan diterima.
2. Gudang mencatat item diterima, kuantitas, kondisi, tanggal, dan bukti pendukung.
3. Sistem menerbitkan nomor GR.
4. Gudang memeriksa kelengkapan dan memposting GR setelah data penerimaan lengkap; sistem mengisi `posted_at` dan `posted_by` tanpa opsi edit manual.
5. Stok bertambah hanya berdasarkan GR yang sudah diposting.
6. GR parsial dapat dibuat berkali-kali, dengan jumlah kumulatif tidak melebihi PO.
7. PO tetap terbuka sampai lengkap atau ditutup dengan status `TUTUP KURANG` melalui pengajuan Purchasing dan persetujuan Owner/Direksi.
8. Selisih, kerusakan, atau kekurangan dicatat sebagai catatan penerimaan dan dapat ditindaklanjuti.

### 4.4 Adjustment (ADJ)

1. Gudang membuat ADJ dengan alasan wajib.
2. ADJ mencatat item, kuantitas sebelum, perubahan, kuantitas sesudah, dan bukti.
3. Sistem menerbitkan nomor ADJ.
4. ADJ diajukan oleh Gudang dan disetujui Owner/Direksi. Gudang hanya dapat melihat status dan tidak dapat menyetujui atau memverifikasi ADJ.
5. Transaksi yang membuat saldo stok negatif ditolak.
6. Sistem memposting ADJ otomatis setelah disetujui, mengisi `posted_at` dan `posted_by`, lalu memasukkannya ke stock ledger.
7. Koreksi ADJ dilakukan melalui transaksi pembalik.
8. Semua perubahan tercatat di audit log.

### 4.5 Pembayaran PO

1. Finance memilih PO yang akan dibayar.
2. Pembayaran dapat dicatat beberapa kali, termasuk beberapa DP.
3. Setiap pembayaran mencatat rekening sumber, nominal, tanggal, PIC, metode, bukti transfer, dan nota/kwitansi.
4. Finance melengkapi dan mengajukan pembayaran. Setelah data dan bukti lengkap, sistem memberi status `MENUNGGU VERIFIKASI`.
5. Owner/Direksi yang berbeda dari PIC Finance melakukan verifikasi. Sistem mengisi `verified_at` dan `verified_by` secara otomatis.
6. Status PO dihitung/ditetapkan:
   - `BELUM DIBAYAR`: belum ada pembayaran terverifikasi.
   - `DP`: ada satu atau lebih pembayaran terverifikasi, tetapi belum lunas.
   - `LUNAS`: total pembayaran terverifikasi memenuhi nilai PO.
   - PO berstatus `DIBATALKAN` tidak dapat menerima pembayaran baru.
7. Pembatalan atau koreksi tidak menghapus histori pembayaran.

## 5. Status Dokumen

### Status umum

- `DRAFT`
- `DIAJUKAN`
- `DISETUJUI`
- `DITOLAK`
- `DIBATALKAN`
- `TUTUP KURANG` (khusus PO)
- `SELESAI`

`SELESAI` digunakan untuk PO yang penerimaannya lengkap dan proses purchasing selesai. Status pembayaran PO tetap dihitung terpisah. `TUTUP KURANG` adalah status terminal alternatif untuk PO yang sisa barangnya tidak diterima setelah pengajuan beralasan oleh Purchasing dan persetujuan Owner/Direksi.

### Status pembayaran PO secara keseluruhan

- `BELUM DIBAYAR`
- `DP`
- `LUNAS`

### Status transaksi pembayaran individual

- `MENUNGGU VERIFIKASI`
- `TERVERIFIKASI`
- `DITOLAK`
- `DIBATALKAN`

Status harus memiliki histori perubahan, pengguna pengubah, waktu, dan alasan bila statusnya ditolak atau dibatalkan.

## 6. Penomoran Dokumen

| Dokumen | Format | Contoh |
|---|---|---|
| Purchase Request | `PR/SP/YYYY/MM/00001` | `PR/SP/2026/09/00001` |
| Purchase Order | `PO/SP/YYYY/MM/00001` | `PO/SP/2026/09/00001` |
| Goods Receipt | `GR/SP/YYYY/MM/00001` | `GR/SP/2026/09/00001` |
| Adjustment | `ADJ/SP/YYYY/MM/00001` | `ADJ/SP/2026/09/00001` |

Aturan:

- Counter terpisah untuk setiap jenis dokumen dan periode `YYYY/MM`.
- Counter reset ke `00001` pada bulan baru.
- Nomor ditetapkan atomik untuk mencegah duplikasi saat ada akses bersamaan.
- Nomor yang sudah diterbitkan tidak boleh diedit.
- Nomor yang pernah diterbitkan tidak boleh digunakan ulang, termasuk setelah dokumen dibatalkan.
- Nomor tidak boleh dialokasikan pada tahap sekadar membuka form; penerbitan terjadi pada transisi final yang ditentukan.
- Kegagalan penyimpanan setelah nomor dialokasikan harus ditangani dengan mekanisme reservasi/void yang tetap menjaga nomor tidak digunakan ulang.

## 7. Approval dan Audit

Semua pengajuan pembelian wajib memiliki persetujuan Owner/Direksi. Data approval minimal:

- Dokumen dan nomor dokumen.
- Keputusan: setujui atau tolak.
- Pengguna pemberi keputusan.
- Waktu keputusan.
- Catatan atau alasan.
- Data sebelum dan sesudah yang aman.

Audit log minimal menyimpan:

- Pengguna.
- Aksi teknis asli dan label Indonesia di antarmuka.
- Modul.
- Deskripsi.
- Data sebelum dan sesudah yang sudah disanitasi.
- IP address.
- User agent.
- Waktu.

Password, token, credential, dan data rahasia tidak boleh masuk audit log. Audit log bersifat read-only pada antarmuka.

## 8. Daftar Form dan Field

### 8.1 PR

- Nomor PR: otomatis, immutable.
- Tanggal pengajuan.
- Pemohon/PIC.
- Departemen atau kebutuhan.
- Prioritas.
- Supplier rekomendasi, bila ada.
- Item: barang, spesifikasi, satuan, kuantitas, estimasi harga.
- Alasan pembelian.
- Lampiran.
- Catatan.
- Status dan histori approval.

### 8.2 PO

- Nomor PO: otomatis, immutable.
- Referensi PR.
- Supplier.
- Alamat penagihan dan pengiriman.
- Tanggal PO dan estimasi tiba.
- Item, kuantitas, harga satuan, diskon, pajak, subtotal, total.
- Syarat pembayaran.
- PIC Purchasing.
- Catatan dan lampiran.
- Status pembayaran.

### 8.3 GR

- Nomor GR: otomatis, immutable.
- Referensi PO.
- Gudang/lokasi.
- Tanggal penerimaan.
- Penerima/PIC Gudang.
- Item PO, kuantitas dipesan, diterima, kurang/rusak.
- Kondisi barang.
- Bukti penerimaan.
- Catatan selisih.
- Status posting.
- `posted_at` dan `posted_by`, dibuat sistem saat GR diposting dan tidak dapat diedit.

### 8.4 ADJ

- Nomor ADJ: otomatis, immutable.
- Gudang/lokasi.
- Tanggal.
- Pemohon/PIC.
- Item.
- Kuantitas sebelum, perubahan, dan sesudah.
- Jenis adjustment.
- Alasan wajib.
- Bukti pendukung.
- Approval dan status.
- `posted_at` dan `posted_by`, dibuat sistem saat ADJ diposting otomatis setelah approval dan tidak dapat diedit.

### 8.5 Pembayaran PO

- PO terkait.
- Status PO.
- Status pembayaran PO secara keseluruhan.
- Status transaksi pembayaran individual: `MENUNGGU VERIFIKASI`, `TERVERIFIKASI`, `DITOLAK`, atau `DIBATALKAN`.
- Nominal pembayaran.
- Tanggal pembayaran.
- Jenis pembayaran: DP atau pelunasan.
- Rekening sumber.
- Metode pembayaran.
- Penerima pembayaran tunai, wajib untuk metode `TUNAI` dan tidak digunakan untuk `TRANSFER BANK`.
- Nomor referensi transaksi.
- Bukti transfer.
- Nota/kwitansi.
- PIC Finance.
- `verified_at` dan `verified_by`, dibuat sistem dan tidak dapat diedit.
- Catatan verifikasi.
- Riwayat pembayaran sebelumnya.

### 8.6 Master data

- Barang: kode otomatis `PRD-000001`, SKU `SP-000001`, nama, kategori, brand, satuan, spesifikasi, barcode unik, status aktif.
- Produk: `minimum_stock` per SKU untuk menentukan kondisi stok minimum.
- Supplier: kode otomatis `SUP-00001`, nama, kontak, alamat, rekening, status aktif.
- Gudang: kode otomatis `WH-001`, nama, alamat, PIC, status aktif.
- Kategori: kode otomatis `CAT-001`, nama, status aktif.
- Brand: kode otomatis `BRD-001`, nama, status aktif.
- Satuan: kode otomatis `UNT-001`, nama, status aktif.
- Rekening sumber: nama bank, nomor rekening, nama pemilik, status aktif.

## 9. Laporan Rilis 1

- Daftar PR berdasarkan status, periode, pemohon, dan approver.
- Daftar PO berdasarkan supplier, status, periode, dan status pembayaran.
- Rekap pembayaran PO: BELUM DIBAYAR, DP, dan LUNAS.
- Detail histori pembayaran dan verifikasi bukti.
- Laporan penerimaan barang berdasarkan PO, supplier, gudang, dan periode.
- Laporan selisih/kekurangan/kerusakan penerimaan.
- Laporan adjustment berdasarkan gudang, jenis, periode, dan approver.
- Laporan aktivitas/audit berdasarkan pengguna, modul, aksi, dan periode.
- Export Excel dan PDF wajib, mengikuti filter dan hak akses, serta tidak menghilangkan audit trail. PDF memuat identitas perusahaan, judul, periode, waktu cetak, filter, data, total, dan pembuat. Excel berisi data detail siap analisis.

## 10. Acceptance Criteria

### Otorisasi dan keamanan

- Setiap PR pengadaan wajib memiliki persetujuan Owner/Direksi sebelum dapat diproses.
- Role hanya dapat menjalankan aksi yang diizinkan matriks.
- Super Admin memiliki akses teknis penuh, tetapi tidak dapat melakukan approval bisnis atau verifikasi pembayaran tanpa role Owner/Direksi.
- Password minimal 12 karakter, wajib diganti saat login pertama, setelah reset, atau jika terindikasi bocor; tidak kedaluwarsa berkala secara paksa.
- Idle timeout 30 menit dan batas session 8 jam diterapkan. MFA wajib untuk Super Admin, Owner/Direksi, dan Finance, dengan recovery code.
- Password, token, dan credential tidak pernah muncul di audit log.
- Audit log dapat dibaca sesuai permission, disimpan minimal 10 tahun, dan tidak dapat diedit atau dihapus melalui aplikasi.
- Backup database dan lampiran terenkripsi ke lokasi terpisah dengan retensi sesuai keputusan resmi. RPO maksimal 24 jam dan RTO maksimal 4 jam. Rollback mencakup kode, database, konfigurasi, dan lampiran. Uji restore setiap 3 bulan mencatat tanggal, pelaksana, hasil, durasi, dan masalah.

### Dokumen dan penomoran

- PR, PO, GR, dan ADJ mengikuti format resmi.
- Counter reset setiap bulan per jenis dokumen.
- Nomor immutable dan tidak digunakan ulang.
- Dua request bersamaan tidak menghasilkan nomor yang sama.
- Pembatalan dokumen tidak mengembalikan nomor ke counter.

### Pembayaran

- Satu PO dapat memiliki beberapa pembayaran DP.
- Sistem menghitung status BELUM DIBAYAR, DP, atau LUNAS sesuai pembayaran terverifikasi.
- Setiap pembayaran memiliki rekening sumber, bukti transfer, nota/kwitansi, PIC, dan verifikasi.
- Finance dapat membuat, memperbaiki sebelum verifikasi, dan mengajukan pembayaran. Setelah data dan bukti lengkap, status langsung `MENUNGGU VERIFIKASI`.
- Pembayaran diverifikasi Owner/Direksi yang berbeda dari PIC Finance dan baru dihitung setelah berstatus TERVERIFIKASI.
- Sistem menolak pembayaran yang melebihi tagihan.
- Pembayaran yang sudah diaudit tidak hilang ketika status PO berubah.

### Operasional dan pelaporan

- GR hanya dapat dibuat dari PO yang valid.
- GR parsial dapat dibuat berkali-kali tanpa jumlah kumulatif melebihi PO. GR diposting Gudang setelah data lengkap dengan `posted_at` dan `posted_by`. PO dapat ditutup dengan status TUTUP KURANG melalui pengajuan Purchasing dan persetujuan Owner/Direksi.
- Stok hanya berubah dari GR yang sudah diposting atau ADJ yang sudah disetujui dan diposting otomatis.
- Transaksi yang membuat saldo negatif ditolak. Gudang dapat membuat ADJ dan melihat statusnya, tetapi tidak dapat menyetujui atau memverifikasi. ADJ wajib memiliki alasan dan bukti, disetujui Owner/Direksi, diposting otomatis dengan `posted_at` dan `posted_by`, dan koreksinya memakai transaksi pembalik.
- Filter dan laporan mengikuti role pengguna.
- Sistem dapat dimulai dengan data baru tanpa proses migrasi data lama.
- Kode master data otomatis dan immutable sesuai format resmi, barcode unik, serta import memakai template tervalidasi dengan preview error.
- Angka pada export Excel/PDF sama dengan dashboard dan database.
- Notifikasi dalam aplikasi dan email tersedia untuk approval, penolakan, revisi, pembatalan, stok minimum, outstanding, dan pembayaran yang perlu diverifikasi. Minimum stok memakai `minimum_stock` per SKU dan memberi notifikasi kepada Gudang, Purchasing, dan Owner/Direksi. Outstanding aktif setelah estimasi kedatangan terlewati dan memberi notifikasi kepada Purchasing dan Owner/Direksi. Pembayaran menunggu verifikasi memberi notifikasi kepada Owner/Direksi. Kegagalan email dicatat dan dapat dikirim ulang.
- Waktu posting GR/ADJ dan waktu verifikasi pembayaran dibuat sistem, tidak dapat diedit, dan transaksi yang sudah diposting tidak dapat diubah langsung.
- Tidak ada ketergantungan terhadap Olsera, SISO, atau marketplace pada Rilis 1.

## 11. Keputusan yang Masih Membutuhkan Persetujuan

Seluruh keputusan Fase 0 nomor 1–20 telah dikunci. Tidak ada keputusan terbuka pada bagian ini.

## 12. Batas Perubahan Dokumen

Perubahan keputusan resmi, workflow, status, format nomor, atau role wajib dicatat sebagai revisi blueprint dan disetujui pemilik proses. Implementasi tidak boleh memperluas modul di luar ruang lingkup Rilis 1 sebelum keputusan Fase berikutnya disahkan.

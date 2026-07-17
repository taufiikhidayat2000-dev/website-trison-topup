# PRD — Fitur Reseller

**Status:** Draft untuk review
**Dibuat:** 2026-07-17
**Produk:** Trison Topup (webtopup)

---

## 1. Latar Belakang & Tujuan

Saat ini semua user membeli produk PPOB dengan satu harga yang sama (`PPOBProduct.sell_price`). Kita ingin membuka jalur "reseller": user dengan role khusus mendapat harga **2% lebih murah** dari harga dasar, dibayar hanya dari saldo (wallet) yang sudah ada di platform.

Tujuan:
- Menambah volume transaksi lewat user yang jual-ulang produk topup.
- Harga reseller aman dari manipulasi (tidak bisa dipalsukan dari sisi client).
- Data transaksi reseller mudah direkap terpisah dari transaksi user biasa (omzet, jumlah hemat, riwayat).

## 2. Ruang Lingkup

**In scope (MVP):**
- Satu tier reseller flat (diskon 2%, bukan berjenjang).
- Pengajuan jadi reseller oleh user, disetujui manual oleh admin.
- Checkout dengan harga reseller, wajib bayar pakai saldo (balance), tidak lewat gateway otomatis.
- Snapshot harga reseller per transaksi untuk audit trail & laporan.
- CMS: kelola pengajuan reseller, lihat daftar reseller & rekap transaksinya.

**Out of scope (MVP, didokumentasikan sebagai keputusan sadar, bukan lupa):**
- Reseller berjenjang / sub-reseller / skema komisi MLM.
- Tier reseller ganda (silver/gold/platinum) dengan diskon berbeda-beda.
- Akses API/H2H khusus reseller.
- Deposit saldo dengan bukti transfer manual (fitur baru) — top-up saldo tetap lewat gateway (LinkQu/Midtrans) yang sudah ada, atau adjust manual oleh admin dari CMS Member (sudah ada, dipakai ulang untuk reseller).

## 3. Kondisi Existing yang Relevan (baseline)

Diverifikasi langsung dari kode, supaya PRD ini nyambung ke implementasi nyata:

| Area | Kondisi sekarang | File |
|---|---|---|
| Harga order | `amount = flashSale.flash_price ?? product.sell_price`, satu-satunya override harga yang ada saat ini adalah Flash Sale | `app/Actions/Main/StoreTransactionAction.php` |
| Role | Spatie roles: `superadmin`, `admin`, `user` (guard `api`), belum ada `reseller` | `database/seeders/PermissionSeeder.php` |
| Bayar pakai saldo | `payment_type = balance` → fee 0, debit lewat `BalanceService::debit()` (pakai `lockForUpdate`, aman dari race condition), auto-refund kalau fulfillment gagal | `app/Actions/Main/ProcessBalancePaymentAction.php` |
| Adjust saldo manual | Sudah ada fitur admin adjust saldo member dari CMS | `app/Http/Controllers/Cms/Member/AdjustMemberBalanceController.php` |
| Toggle status member | Sudah ada pola aktif/nonaktifkan member dari CMS | `app/Http/Controllers/Cms/Member/UpdateMemberStatusController.php` |
| Audit trail harga | Pola snapshot harga per-order sudah ada untuk Voucher & Flash Sale (`before_amount`/`after_amount`, `original_price`/`flash_price`) | `app/Models/Voucher/VoucherUse.php`, `app/Models/FlashSale/FlashSaleUse.php` |
| Pengaturan global | Tabel `settings` (singleton, kolom `value` berupa JSON) | `app/Models/Setting/Setting.php` |

**Kesimpulan:** ini benar-benar blank slate — tidak ada sisa kode reseller/tier/member-price sebelumnya (sudah di-grep). Kita bisa mengikuti pola Voucher/FlashSale yang sudah terbukti dipakai, bukan bikin pola baru.

## 4. Business Rules — Pricing

- Diskon reseller **dihitung dinamis, bukan disimpan sebagai kolom di `PPOBProduct`**. Alasan: nilainya flat 2% dari harga dasar, kalau disimpan sebagai kolom terpisah, rawan basi begitu admin ubah `sell_price` dan lupa update harga reseller-nya. Rumus dihitung saat checkout:
  ```
  reseller_price = round(sell_price * (1 - reseller_discount_percent / 100))
  ```
- `reseller_discount_percent` disimpan di tabel `settings` (default `2`), **bukan di-hardcode** — supaya admin bisa ubah persentase tanpa deploy kalau bisnis butuh, tapi tetap satu angka global (bukan per-produk).
- **Urutan prioritas kalau ada promo lain aktif bersamaan** (perlu dikonfirmasi ke stakeholder, lihat §9):
  1. Kalau produk sedang **Flash Sale**, harga Flash Sale yang dipakai (tidak stack dengan diskon reseller) — Flash Sale sifatnya promosi terbatas waktu dan sudah dikurasi harganya oleh admin per produk.
  2. Kalau tidak ada Flash Sale dan user berstatus reseller aktif, pakai `reseller_price`.
  3. Kalau tidak keduanya, pakai `sell_price` normal.
  4. Voucher (kalau ada) tetap dihitung di atas harga hasil langkah 1-3, seperti alur sekarang.
- Reseller boleh checkout dengan **metode pembayaran apa saja** (saldo, transfer manual, maupun otomatis/QRIS/VA/e-wallet) — keputusan revisi (2026-07-17) untuk mengurangi friksi buat reseller baru yang belum mau top-up saldo di awal. Trade-off yang disadari: metode otomatis auto-fulfillment lewat webhook gateway tanpa review admin, jadi ini jalur yang paling minim penjagaan untuk transaksi berharga diskon; keputusan diambil sadar oleh pemilik produk setelah trade-off ini dijelaskan.

## 5. User Flow

### 5.1 Pengajuan jadi Reseller
1. User login mengisi form "Ajukan jadi Reseller" (nama usaha/alasan, opsional nomor WA bisnis).
2. Sistem membuat record `ResellerApplication` berstatus `pending`. Satu user hanya boleh punya satu pengajuan `pending` aktif (cegah spam pengajuan).
3. Admin melihat daftar pengajuan di CMS (`/cms/reseller/applications`), approve atau reject (dengan catatan alasan kalau reject).
4. Saat **approve**: sistem assign role `reseller` ke user (Spatie), set `ResellerApplication.status = approved`, `approved_by`, `approved_at`. Dicatat ke activity log (infra sudah ada, tabel `activity_log`).
5. Saat **reject**: status `rejected`, user bisa mengajukan ulang.
6. User yang sudah jadi reseller otomatis melihat harga reseller di seluruh halaman produk & saat checkout.

### 5.2 Top-up Saldo
Tidak ada fitur baru — reseller top-up saldo lewat jalur yang sudah ada:
- Deposit via gateway (LinkQu/Midtrans), **harga topup normal, tidak ada diskon untuk top-up**, diskon hanya berlaku saat belanja produk.
- Atau admin adjust saldo manual dari CMS Member (dipakai kalau reseller transfer manual ke rekening perusahaan), memakai kontrol yang sudah ada (`AdjustMemberBalanceController`).

### 5.3 Checkout dengan Harga Reseller
1. User dengan role `reseller` membuka halaman produk → harga yang tampil sudah harga reseller (kecuali sedang flash sale, lihat §4).
2. Submit checkout → `StoreTransactionAction::handle()` resolve harga lewat urutan prioritas §4, **selalu dihitung ulang di server**, tidak menerima harga dari payload request.
3. `payment_type` bebas dipilih (saldo/manual/otomatis) untuk transaksi berharga reseller.
4. Order tersimpan seperti biasa + record baru `ResellerPriceUse` (snapshot `original_price`, `reseller_price`, `discount_amount`) — pola sama seperti `VoucherUse`/`FlashSaleUse`.
5. Debit saldo & fulfillment jalan seperti alur balance payment yang sudah ada (tidak berubah).

## 6. Keamanan

Poin ini menjawab langsung requirement "aman":

- **Harga tidak pernah dipercaya dari client.** Field harga di request checkout (kalau ada) diabaikan; server selalu resolve ulang dari `sell_price` + role user + status flash sale saat itu juga.
- **Role reseller tidak self-service aktif otomatis** — wajib approval admin, mencegah user mendaftar sendiri lalu langsung dapat harga murah.
- **Semua metode pembayaran diperbolehkan** untuk harga reseller (lihat §4). Untuk transaksi lewat saldo, tetap tercatat penuh di ledger `BalanceMutation` (`balance_before`/`balance_after`/`performed_by`). Untuk transfer manual, order baru diproses setelah admin memverifikasi bukti transfer (`ValidatePaymentAction`, tidak berubah dari alur non-reseller). Untuk metode otomatis, fulfillment langsung jalan begitu gateway konfirmasi bayar tanpa review manusia — ini secara sadar diterima sebagai risiko yang lebih tinggi demi mengurangi friksi bagi reseller baru.
- **Race condition saldo sudah ditangani** oleh `BalanceService::debit()` existing (`lockForUpdate`) — tidak perlu logic baru, reseller checkout memakai fungsi yang sama dengan user biasa.
- **Approval & reject reseller dicatat ke activity log** (`spatie/laravel-activitylog`, tabel sudah ada), termasuk siapa admin yang approve dan kapan — penting kalau ada dispute soal siapa yang authorize suatu reseller.
- **Rate-limit pengajuan**: maksimal 1 pengajuan `pending` per user; tambahkan throttle submit form (mis. 3x/hari) untuk cegah spam pengajuan otomatis/bot.
- **Snapshot harga per transaksi** (`ResellerPriceUse`) memastikan kalau `sell_price` produk atau `reseller_discount_percent` berubah di kemudian hari, riwayat order lama tidak ikut berubah nilainya — penting untuk rekonsiliasi keuangan.

## 7. Pendataan / Reporting

Poin ini menjawab langsung requirement "memudahkan pendataan":

- Karena reseller tetap row `User` biasa + role Spatie, **semua data transaksi existing (`Order`, `BalanceMutation`) otomatis bisa difilter by role** tanpa duplikasi tabel user.
- CMS baru: **Daftar Reseller** (`/cms/reseller`) menampilkan per reseller: status, tanggal approve, saldo saat ini, total transaksi, total omzet, total nominal yang "dihemat" (sum `ResellaerPriceUse.discount_amount`).
- CMS baru: **Riwayat Pengajuan** (`/cms/reseller/applications`) — approved/rejected/pending, siapa yang approve.
- Halaman detail reseller bisa reuse pola yang sudah ada di Member detail (riwayat order + riwayat mutasi saldo per user, kemungkinan besar sudah ada komponennya di `resources/js/pages/cms/member/`).
- Export CSV/Excel untuk laporan reseller **tidak termasuk MVP**, diusulkan sebagai fast-follow kalau dibutuhkan finance/accounting.

## 8. Data Model — Perubahan Database

| Tabel | Jenis | Kolom utama |
|---|---|---|
| `reseller_applications` | **Baru** | `user_id`, `business_name`, `note`, `status` (pending/approved/rejected), `reviewed_by`, `reviewed_at`, `rejection_reason`, timestamps |
| `reseller_price_uses` | **Baru** | `usable_type`, `usable_id` (morphTo Order, ikut pola `VoucherUse`), `original_price`, `reseller_price`, `discount_amount`, `discount_percent_snapshot` |
| `settings` | **Ubah data, bukan skema** | tambah key `reseller_discount_percent` (default `2`) di JSON `value` |
| `roles` (Spatie) | **Data seed** | tambah role `reseller` di `PermissionSeeder` |
| `p_p_o_b_products` | **Tidak berubah** | harga reseller dihitung on-the-fly, tidak ada kolom baru |
| `users` | **Tidak berubah** | status reseller sepenuhnya lewat role Spatie, tidak butuh kolom `is_reseller` terpisah (hindari dua sumber kebenaran) |

## 9. Keputusan yang Sudah Diambil (sudah diimplementasikan)

1. **Flash Sale vs harga reseller** — Flash Sale menang, tidak stack dengan diskon reseller (default, belum ada keberatan).
2. **Syarat pengajuan** — tidak ada syarat tambahan selain mengisi form pengajuan.
3. **Pencabutan status reseller** — masuk MVP. Admin bisa cabut role reseller kapan saja dari CMS (`/cms/reseller`, tombol revoke).
4. **Cakupan produk** — berlaku untuk semua produk PPOB, tanpa pengecualian kategori.
5. **Metode pembayaran** — *(revisi 2026-07-17)* awalnya dibatasi saldo-saja demi keamanan, lalu direvisi ke "semua metode diperbolehkan" (termasuk otomatis) atas permintaan eksplisit untuk mengurangi friksi pendaftaran reseller baru. Lihat trade-off di §4/§6 — keputusan ini menerima risiko fraud lebih tinggi di jalur otomatis demi kemudahan reseller baru yang belum mau top-up saldo di awal.

## 10. Acceptance Criteria (ringkas)

- [ ] User bisa submit pengajuan reseller, tidak bisa submit dobel selagi masih `pending`.
- [ ] Admin bisa approve/reject pengajuan dari CMS; approve otomatis assign role `reseller`.
- [ ] User dengan role `reseller` melihat harga 2% lebih murah dari `sell_price` di halaman produk & checkout, **kecuali** produk sedang flash sale (pakai harga flash sale).
- [ ] Checkout harga reseller bisa dengan metode pembayaran apa saja (saldo/manual/otomatis).
- [ ] Harga final selalu dihitung server-side; payload harga dari client (kalau ada) diabaikan sepenuhnya.
- [ ] Setiap order dengan harga reseller punya record `ResellerPriceUse` yang menyimpan snapshot harga.
- [ ] CMS menampilkan daftar reseller beserta rekap omzet & total hemat per reseller.
- [ ] Semua approval/reject/assign-role reseller tercatat di activity log.
- [ ] Perubahan `reseller_discount_percent` di settings tidak mengubah nilai historis order yang sudah terjadi.

---

*Dokumen ini adalah draft untuk direview. Setelah pertanyaan di §9 dijawab, lanjut ke rencana implementasi teknis (migration, action classes, CMS pages) berdasarkan file-file yang sudah dipetakan di §3 dan §8.*

# Studi Kasus: Aplikasi Stock Barang (Laravel + Livewire)

Paket ini berisi **seluruh kode khas proyek** (app/, resources/views, database, routes, config,
bootstrap/app.php, tests/, Jenkinsfile, .gitignore). Skeleton Laravel + vendor harus dibuat di
mesin Anda (Composer tidak bisa dijalankan di lingkungan penyusun paket ini).

## 1. Buat proyek (bagian 2.2 / 3.1)
```bash
composer create-project laravel/laravel {username}-pabwe2026-sk-p7-laravel-livewire
cd {username}-pabwe2026-sk-p7-laravel-livewire
composer require livewire/livewire phpoffice/phpspreadsheet
```
Buat database MySQL `pabwe_p7_stock`, lalu isi `.env` dengan nilai pada `env.example.additions`
(salin juga ke `.env.example`).

## 2. Salin paket ini (timpa file yang sudah ada)
```bash
# hapus file bawaan yang tidak dipakai
rm -f app/Http/Controllers/Controller.php tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
# salin isi paket ke root proyek (timpa)
cp -R /path/paket/app /path/paket/bootstrap /path/paket/config /path/paket/database \
      /path/paket/resources /path/paket/routes /path/paket/tests \
      /path/paket/Jenkinsfile /path/paket/.gitignore .
```
File yang menimpa bawaan: `app/Models/User.php`, `app/Providers/AppServiceProvider.php`,
`bootstrap/app.php`, `routes/web.php`, `database/factories/UserFactory.php`,
`database/migrations/0001_01_01_000000_create_users_table.php`, `database/seeders/DatabaseSeeder.php`.
Kalau skeleton Anda punya `routes/console.php` itu dibiarkan.

## 3. Jalankan
```bash
php artisan storage:link
php artisan migrate:fresh --seed     # admin/admin, member/member + 4 contoh stok
php artisan serve
```

## 4. Uji (harus hijau & coverage 100%)
Pasang ekstensi **pcov** (atau Xdebug mode coverage), lalu:
```bash
composer test
php -d pcov.enabled=1 artisan test --coverage --min=100
composer audit
```
Test memakai SQLite in-memory (default `phpunit.xml` Laravel). Pastikan `pdo_sqlite` aktif.

## Catatan desain
- Status stok **dihitung otomatis** dari quantity vs min_stock (<=0 habis, <=min menipis, selain itu tersedia);
  form hanya menampilkan info status, tidak menerimanya sebagai input.
- `OpenAiStockGeneratorService` mengimplementasikan `AiClientInterface`; test memakai `createStub`.
- Middleware `role` & `owns.stock` di `bootstrap/app.php`; rate limit `global` = 1000/detik; `FORCE_HTTPS` via `config/security.php`.
- Admin hanya melihat (read-only): policy `update/delete/create` menolak admin.
- Jangan commit `.env`; `.gitignore` sudah mengabaikan artefak uji.

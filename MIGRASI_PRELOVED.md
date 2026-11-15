# Migrasi Database Preloved Marketplace Karawang (Laravel)

Panduan ini dibuat agar bisa dibaca manusia dan dijalankan berurutan oleh agent (Antigravity).

## Aturan untuk agent (wajib dipatuhi)

1. Jalankan step **berurutan**, jangan lompat.
2. Kalau ada perintah yang error, **berhenti**, tampilkan error, jangan lanjut.
3. **Jangan membuat database.** Database `preloved_karawang` dianggap sudah ada dan MySQL sudah menyala.
4. **Jangan menambah tabel** selain 18 tabel di bawah ini.
5. Jalankan `php artisan make:migration` **satu per satu** (jangan digabung dalam satu baris), supaya urutan nama file benar.
6. Setiap file migrasi: **ganti seluruh isi file** dengan kode yang diberikan.

Daftar 18 tabel (sesuai urutan pembuatan, karena foreign key; tabel `auto_replies` sengaja tidak dibuat):
`users`, `email_verifications`, `password_resets`, `categories`, `products`, `product_media`, `campus_meeting_points`, `transactions`, `transaction_items`, `ratings`, `wishlist_requests`, `request_responses`, `conversations`, `messages`, `message_attachments`, `negotiations`, `notifications`, `reward_records`.

---

## Step 1: Edit file `.env`

Buka file `.env` di root project, cari dan ubah baris-baris berikut (jika baris tidak ada, tambahkan):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=preloved_karawang
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Catatan: tiga baris terakhir wajib diubah. Bawaan Laravel memakai driver `database`, yang butuh tabel `sessions`, `cache`, `jobs`. Tabel itu tidak ada di ERD, jadi diganti ke `file`/`sync`.

Isi `DB_PASSWORD` kalau MySQL kamu memakai password.

Bersihkan cache config:

```bash
php artisan config:clear
```

## Step 2: Cek koneksi ke database

```bash
php artisan db:show
```

Harus tampil nama database `preloved_karawang`. Kalau gagal, periksa `.env` dan pastikan MySQL menyala.

## Step 3: Hapus migrasi bawaan Laravel

Migrasi bawaan membuat tabel yang tidak ada di ERD (`password_reset_tokens`, `sessions`, `cache`, `jobs`, dan `users` versi lain).

Linux / Mac / Git Bash:
```bash
rm database/migrations/*.php
```

Windows CMD:
```bash
del database\migrations\*.php
```

> Tabel `migrations` tetap akan muncul. Itu dibuat Laravel sendiri untuk mencatat riwayat migrasi.

## Step 4: Buat 18 file migrasi

Jalankan satu per satu, sesuai urutan:

```bash
php artisan make:migration create_users_table
php artisan make:migration create_email_verifications_table
php artisan make:migration create_password_resets_table
php artisan make:migration create_categories_table
php artisan make:migration create_products_table
php artisan make:migration create_product_media_table
php artisan make:migration create_campus_meeting_points_table
php artisan make:migration create_transactions_table
php artisan make:migration create_transaction_items_table
php artisan make:migration create_ratings_table
php artisan make:migration create_wishlist_requests_table
php artisan make:migration create_request_responses_table
php artisan make:migration create_conversations_table
php artisan make:migration create_messages_table
php artisan make:migration create_message_attachments_table
php artisan make:migration create_negotiations_table
php artisan make:migration create_notifications_table
php artisan make:migration create_reward_records_table
```

Cek hasilnya, harus ada 18 file:

```bash
ls database/migrations
```

## Step 5: Isi tiap file migrasi

Semua file ada di `database/migrations/` dengan nama `<timestamp>_create_<nama>_table.php`. Ganti **seluruh isi** tiap file dengan kode di bawah.

Aturan yang dipakai:
- Induk dihapus, data anak ikut terhapus (`cascadeOnDelete`).
- Kolom yang boleh kosong diberi `nullable`.

### 5.1 `create_users_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('campus', ['UNSIKA', 'UBP']);
            $table->string('faculty');
            $table->string('cohort');
            $table->string('profile_photo')->nullable();
            $table->string('social_media_url')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->dateTime('email_verified_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### 5.2 `create_email_verifications_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id('verification_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('token');
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
    }
};
```

### 5.3 `create_password_resets_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id('reset_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('token');
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
```

### 5.4 `create_categories_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### 5.5 `create_products_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories', 'category_id')->cascadeOnDelete();
            $table->string('product_name');
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->string('condition');
            $table->string('size')->nullable();
            $table->string('usage_period')->nullable();
            $table->text('defects')->nullable();
            $table->enum('status', ['tersedia', 'terjual'])->default('tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### 5.6 `create_product_media_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id('media_id');
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->enum('media_type', ['foto', 'video']);
            $table->string('media_url');
            $table->integer('file_size');
            $table->integer('display_order')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
```

### 5.7 `create_campus_meeting_points_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_meeting_points', function (Blueprint $table) {
            $table->id('meeting_point_id');
            $table->enum('campus', ['UNSIKA', 'UBP']);
            $table->string('point_name');
            $table->text('address_description');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_meeting_points');
    }
};
```

### 5.8 `create_transactions_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('buyer_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('meeting_point_id')->nullable()
                  ->constrained('campus_meeting_points', 'meeting_point_id')->nullOnDelete();
            $table->decimal('total_price', 12, 2);
            $table->decimal('agreed_discount', 12, 2)->default(0);
            $table->enum('payment_method', ['cod'])->default('cod');
            $table->enum('transaction_status', ['menunggu', 'dijadwalkan', 'selesai', 'dibatalkan'])
                  ->default('menunggu');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
```

### 5.9 `create_transaction_items_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id('transaction_item_id');
            $table->foreignId('transaction_id')->constrained('transactions', 'transaction_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('agreed_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
```

### 5.10 `create_ratings_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id('rating_id');
            $table->foreignId('transaction_id')->constrained('transactions', 'transaction_id')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('reviewed_user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->tinyInteger('stars');
            $table->text('review')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
```

### 5.11 `create_wishlist_requests_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories', 'category_id')->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('budget', 12, 2);
            $table->dateTime('expired_at');
            $table->enum('status', ['aktif', 'terpenuhi', 'kedaluwarsa'])->default('aktif');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_requests');
    }
};
```

### 5.12 `create_request_responses_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_responses', function (Blueprint $table) {
            $table->id('response_id');
            $table->foreignId('request_id')->constrained('wishlist_requests', 'request_id')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()
                  ->constrained('products', 'product_id')->nullOnDelete();
            $table->text('response_message');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_responses');
    }
};
```

### 5.13 `create_conversations_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id('conversation_id');
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
```

### 5.14 `create_messages_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->foreignId('conversation_id')->constrained('conversations', 'conversation_id')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->text('message_text');
            $table->dateTime('sent_at')->useCurrent();
            $table->dateTime('read_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
```

### 5.15 `create_message_attachments_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->foreignId('message_id')->constrained('messages', 'message_id')->cascadeOnDelete();
            $table->enum('media_type', ['foto', 'video']);
            $table->string('media_url');
            $table->integer('file_size');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
```

### 5.16 `create_negotiations_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negotiations', function (Blueprint $table) {
            $table->id('negotiation_id');
            $table->foreignId('conversation_id')->constrained('conversations', 'conversation_id')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users', 'user_id')->cascadeOnDelete();
            // tawaran sebelumnya (kalau ini tawaran balik), boleh kosong
            $table->foreignId('parent_offer_id')->nullable()
                  ->constrained('negotiations', 'negotiation_id')->nullOnDelete();
            $table->decimal('offered_price', 12, 2);
            $table->integer('quantity')->default(1);
            $table->enum('status', ['menunggu', 'diterima', 'ditolak', 'tawaran_balik'])->default('menunggu');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('responded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negotiations');
    }
};
```

### 5.17 `create_notifications_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('notification_type');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

### 5.18 `create_reward_records_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_records', function (Blueprint $table) {
            $table->id('reward_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->integer('points')->default(0);
            $table->enum('reward_type', ['poin', 'badge']);
            $table->string('description');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_records');
    }
};
```

## Step 6: Jalankan migrasi

```bash
php artisan migrate:fresh
```

Cek hasilnya:

```bash
php artisan migrate:status
php artisan db:show
```

Hasil yang benar: 18 migrasi berstatus **Ran**, tanpa error.

## Step 7: Tes dengan data dummy (non-interaktif)

Agar bisa dijalankan agent tanpa membuka tinker, dummy dibuat sebagai file seeder sementara.

Buat file:

```bash
php artisan make:seeder DummySeeder
```

Ganti seluruh isi `database/seeders/DummySeeder.php` dengan:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummySeeder extends Seeder
{
    public function run(): void
    {
        // 1. users, kategori, titik temu
        DB::table('users')->insert([
            ['name' => 'Budi Penjual', 'email' => 'budi@student.unsika.ac.id', 'password' => bcrypt('password123'),
             'campus' => 'UNSIKA', 'faculty' => 'Fasilkom', 'cohort' => '2024',
             'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Siti Pembeli', 'email' => 'siti@student.ubp.ac.id', 'password' => bcrypt('password123'),
             'campus' => 'UBP', 'faculty' => 'Teknik', 'cohort' => '2023',
             'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('categories')->insert(['category_name' => 'Pakaian']);
        DB::table('campus_meeting_points')->insert([
            'campus' => 'UNSIKA', 'point_name' => 'Gerbang Utama',
            'address_description' => 'Depan gerbang utama kampus', 'is_active' => true,
        ]);

        // 2. produk dan media
        DB::table('products')->insert([
            'seller_id' => 1, 'category_id' => 1, 'product_name' => 'Hoodie Hitam',
            'description' => 'Jarang dipakai', 'price' => 75000, 'condition' => 'Baik',
            'size' => 'L', 'usage_period' => '6 bulan', 'status' => 'tersedia',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_media')->insert([
            'product_id' => 1, 'media_type' => 'foto', 'media_url' => 'products/hoodie1.jpg',
            'file_size' => 250000, 'display_order' => 1,
        ]);

        // 3. chat dan nego
        DB::table('conversations')->insert([
            'product_id' => 1, 'buyer_id' => 2, 'seller_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('messages')->insert([
            'conversation_id' => 1, 'sender_id' => 2, 'message_text' => 'Kak, masih ada?',
        ]);
        DB::table('message_attachments')->insert([
            'message_id' => 1, 'media_type' => 'foto', 'media_url' => 'chat/foto1.jpg', 'file_size' => 120000,
        ]);
        DB::table('negotiations')->insert([
            'conversation_id' => 1, 'sender_id' => 2, 'offered_price' => 60000,
            'quantity' => 1, 'status' => 'tawaran_balik', 'responded_at' => now(),
        ]);
        DB::table('negotiations')->insert([
            'conversation_id' => 1, 'sender_id' => 1, 'parent_offer_id' => 1, 'offered_price' => 70000,
            'quantity' => 1, 'status' => 'diterima', 'responded_at' => now(),
        ]);

        // 4. transaksi COD dan rating
        DB::table('transactions')->insert([
            'buyer_id' => 2, 'seller_id' => 1, 'meeting_point_id' => 1, 'total_price' => 70000,
            'agreed_discount' => 5000, 'payment_method' => 'cod',
            'transaction_status' => 'dijadwalkan', 'scheduled_at' => now()->addDay(),
        ]);
        DB::table('transaction_items')->insert([
            'transaction_id' => 1, 'product_id' => 1, 'quantity' => 1,
            'agreed_price' => 70000, 'subtotal' => 70000,
        ]);
        DB::table('ratings')->insert([
            'transaction_id' => 1, 'reviewer_id' => 2, 'reviewed_user_id' => 1,
            'stars' => 5, 'review' => 'Penjual ramah',
        ]);

        // 5. wishlist
        DB::table('wishlist_requests')->insert([
            'user_id' => 2, 'category_id' => 1, 'item_name' => 'Kemeja Putih',
            'budget' => 50000, 'expired_at' => now()->addDays(7), 'status' => 'aktif',
        ]);
        DB::table('request_responses')->insert([
            'request_id' => 1, 'seller_id' => 1, 'product_id' => 1,
            'response_message' => 'Ada kak, cek barang ini',
        ]);

        // 6. notifikasi, reward, token
        DB::table('notifications')->insert([
            'user_id' => 1, 'notification_type' => 'chat', 'title' => 'Pesan baru',
            'message' => 'Siti mengirim pesan', 'is_read' => false,
        ]);
        DB::table('reward_records')->insert([
            'user_id' => 1, 'points' => 10, 'reward_type' => 'poin', 'description' => 'Transaksi selesai',
        ]);
        DB::table('email_verifications')->insert([
            'user_id' => 1, 'token' => 'abc123', 'expires_at' => now()->addHour(),
        ]);
        DB::table('password_resets')->insert([
            'user_id' => 1, 'token' => 'xyz789', 'expires_at' => now()->addHour(),
        ]);

        $this->command->info('Semua data dummy berhasil dimasukkan.');

        // 7. tes foreign key: insert ini HARUS ditolak database
        try {
            DB::table('products')->insert([
                'seller_id' => 999, 'category_id' => 1, 'product_name' => 'Test',
                'description' => 'x', 'price' => 1000, 'condition' => 'Baik',
            ]);
            $this->command->error('GAGAL: foreign key tidak menolak data salah!');
        } catch (\Exception $e) {
            $this->command->info('OK: foreign key menolak seller_id yang tidak ada.');
        }

        // 8. tes join: harus tampil Siti (pembeli) dan Budi (penjual)
        $hasil = DB::table('transactions')
            ->join('users as b', 'transactions.buyer_id', '=', 'b.user_id')
            ->join('users as s', 'transactions.seller_id', '=', 's.user_id')
            ->select('transactions.transaction_id', 'b.name as pembeli', 's.name as penjual', 'transactions.total_price')
            ->get();
        $this->command->info($hasil->toJson());
    }
}
```

Jalankan (database dikosongkan dulu supaya ID mulai dari 1):

```bash
php artisan migrate:fresh
php artisan db:seed --class=DummySeeder
```

Hasil yang benar di terminal:

```
Semua data dummy berhasil dimasukkan.
OK: foreign key menolak seller_id yang tidak ada.
[{"transaction_id":1,"pembeli":"Siti Pembeli","penjual":"Budi Penjual","total_price":"70000.00"}]
```

Cek jumlah baris tiap tabel (opsional):

```bash
php artisan db:table users
php artisan db:table products
php artisan db:table transactions
```

## Step 8: Tes rollback

```bash
php artisan migrate:rollback
php artisan migrate
```

Kalau dua perintah ini jalan tanpa error, semua method `down()` dan urutan foreign key sudah benar.

## Step 9: Bersihkan file dummy

Seeder ini hanya untuk tes, hapus setelah semua langkah lolos:

Linux / Mac / Git Bash:
```bash
rm database/seeders/DummySeeder.php
```

Windows CMD:
```bash
del database\seeders\DummySeeder.php
```

---

## Checklist akhir

- [ ] `.env` terisi benar dan `php artisan db:show` berhasil
- [ ] 18 file migrasi ada di `database/migrations`
- [ ] `php artisan migrate:fresh` tanpa error
- [ ] `php artisan migrate:status` menampilkan 18 baris **Ran**
- [ ] `DummySeeder` berhasil, foreign key menolak data salah
- [ ] `migrate:rollback` lalu `migrate` tanpa error

## Asumsi yang dipakai (boleh diubah)

1. **Nilai enum** tidak ada di ERD, diambil dari PDF:
   - status produk: `tersedia`, `terjual`
   - status transaksi: `menunggu`, `dijadwalkan`, `selesai`, `dibatalkan`
   - status nego: `menunggu`, `diterima`, `ditolak`, `tawaran_balik`
   - status wishlist: `aktif`, `terpenuhi`, `kedaluwarsa`
   - tipe reward: `poin`, `badge`
2. **`negotiations.parent_offer_id`**: di ERD garis "menanggapi" mengarah ke `users`, tetapi namanya menunjukkan tawaran sebelumnya, jadi dibuat relasi ke tabel `negotiations` sendiri. Kalau maksudnya ke `users`, ganti `constrained('negotiations', 'negotiation_id')` menjadi `constrained('users', 'user_id')`.

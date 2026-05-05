# 📚 DOKUMENTASI LENGKAP BACKEND PLN

**Proyek:** PLN Backend  
**Framework:** Laravel 11  
**Database:** SQLite  
**Authentication:** Laravel Sanctum (Token-Based)  
**Tanggal Pembuatan:** Mei 2026

---

## 📖 Daftar Isi

1. [Arsitektur Aplikasi](#arsitektur-aplikasi)
2. [Setup dan Konfigurasi](#setup-dan-konfigurasi)
3. [Database Schema](#database-schema)
4. [API Endpoints](#api-endpoints)
5. [Authentication Flow](#authentication-flow)
6. [Controllers & Logic](#controllers--logic)
7. [Models](#models)
8. [Routes](#routes)
9. [Migrations](#migrations)
10. [Seeding](#seeding)
11. [Cek Data di SQLite](#cek-data-di-sqlite)

---

## 🏗️ Arsitektur Aplikasi

```
pln-backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Api/
│   │       │   ├── AuthController.php      # Menangani login/logout
│   │       │   └── PelangganController.php # Menangani data pelanggan
│   │       └── Controller.php               # Base controller
│   ├── Models/
│   │   └── User.php                        # Model user/admin
│   └── Providers/
│       └── AppServiceProvider.php          # Service provider
├── routes/
│   ├── api.php                             # Rute API
│   ├── web.php                             # Rute web
│   └── console.php                         # Rute console
├── database/
│   ├── migrations/                         # Skema database
│   ├── seeders/                            # Data dummy
│   └── factories/                          # Factory untuk testing
├── config/                                 # Konfigurasi aplikasi
├── bootstrap/                              # Bootstrap application
└── public/                                 # File publik
```

---

## 🔧 Setup dan Konfigurasi

### File Utama Konfigurasi

#### **`bootstrap/app.php`** - Bootstrap Aplikasi
```php
<?php
return Application::configure(basePath: dirname(__DIR__))
    // Konfigurasi routing
    ->withRouting(
        web: __DIR__.'/../routes/web.php',    // Rute web
        api: __DIR__.'/../routes/api.php',    // Rute API
        commands: __DIR__.'/../routes/console.php', // Command console
        health: '/up',                         // Health check endpoint
    )
    // Middleware configuration
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware dapat ditambahkan di sini
    })
    // Exception handling
    ->withExceptions(function (Exceptions $exceptions): void {
        // Exception handler
    })->create();
```

**Penjelasan:**
- Mengonfigurasi routing aplikasi (web, API, console)
- Mendefinisikan middleware global
- Mengatur exception handling
- Health check endpoint di `/up`

---

## 🗄️ Database Schema

### Tabel: `users`
Menyimpan data admin/user aplikasi

```sql
CREATE TABLE users (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(255) NOT NULL,        -- Nama user
    email           VARCHAR(255) UNIQUE NOT NULL, -- Email unik
    email_verified_at TIMESTAMP NULL,            -- Waktu verifikasi email
    password        VARCHAR(255) NOT NULL,       -- Password (hashed)
    remember_token  VARCHAR(100) NULL,           -- Token remember-me
    role            VARCHAR(50) DEFAULT 'user',  -- Role (admin/user)
    created_at      TIMESTAMP,                   -- Waktu pembuatan
    updated_at      TIMESTAMP                    -- Waktu update terakhir
);
```

**Kolom Penting:**
- `id` - Primary key
- `email` - Unik, digunakan untuk login
- `password` - Di-hash menggunakan bcrypt
- `role` - Tipe user (admin/user)

---

### Tabel: `pelanggans`
Menyimpan data pelanggan PLN (Customer Database)

```sql
CREATE TABLE pelanggans (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama            VARCHAR(255) NOT NULL,        -- Nama pelanggan
    alamat          TEXT NOT NULL,                -- Alamat lengkap
    no_meter        VARCHAR(50),                  -- Nomor meteran listrik
    id_pelanggan    VARCHAR(50),                  -- ID pelanggan (alternatif)
    daya_listrik    INTEGER,                      -- Daya listrik (Watt)
    daya            INTEGER,                      -- Daya (backup field)
    no_hp           VARCHAR(20),                  -- Nomor handphone
    foto_path       VARCHAR(255),                 -- Path foto pelanggan
    latitude        DECIMAL(10,8),                -- Koordinat latitude
    longitude       DECIMAL(11,8),                -- Koordinat longitude
    waktu_kunjungan TIMESTAMP,                    -- Waktu kunjungan terakhir
    created_at      TIMESTAMP,                    -- Waktu pembuatan
    updated_at      TIMESTAMP                     -- Waktu update terakhir
);
```

**Kolom Penting:**
- `no_meter` - Identifikasi unik pelanggan
- `latitude/longitude` - Koordinat lokasi untuk mapping
- `foto_path` - Penyimpanan referensi foto pelanggan
- `waktu_kunjungan` - Tracking kunjungan field officer

---

### Tabel: `personal_access_tokens`
Menyimpan token API (Laravel Sanctum)

```sql
CREATE TABLE personal_access_tokens (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    tokenable_type  VARCHAR(255) NOT NULL,       -- Model yang memiliki token
    tokenable_id    BIGINT NOT NULL,             -- ID user
    name            VARCHAR(255),                -- Nama token
    token           VARCHAR(64) UNIQUE NOT NULL, -- Token yang di-hash
    abilities       JSON,                        -- Abilities/permissions
    last_used_at    TIMESTAMP NULL,              -- Last access time
    expires_at      TIMESTAMP NULL,              -- Expiration time
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);
```

---

## 🔌 API Endpoints

### Base URL
```
http://localhost:8000/api
```

### 1. **Authentication Endpoints**

#### Login - `POST /login`
**Endpoint Publik** - Tidak perlu authentikasi

**Request Body:**
```json
{
    "email": "admin1@pln.com",
    "password": "admin123456"
}
```

**Response (Success - 200):**
```json
{
    "message": "Login berhasil",
    "user": {
        "id": 1,
        "name": "Admin PLN 1",
        "email": "admin1@pln.com",
        "role": "admin",
        "created_at": "2026-05-03T00:00:00.000000Z",
        "updated_at": "2026-05-03T00:00:00.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRST"
}
```

**Response (Error - 422):**
```json
{
    "message": "Email atau password salah.",
    "errors": {
        "email": ["Email atau password salah."]
    }
}
```

**Penjelasan Code:**
```php
// validation.php - Validasi input
$request->validate([
    'email' => 'required|email',      // Email wajib dan format email
    'password' => 'required',          // Password wajib
]);

// Cari user berdasarkan email
$user = User::where('email', $request->email)->first();

// Cek password dengan Hash::check (verifikasi bcrypt)
if (! $user || ! Hash::check($request->password, $user->password)) {
    throw ValidationException::withMessages([
        'email' => ['Email atau password salah.'],
    ]);
}

// Buat token Sanctum dengan nama 'admin-token'
$token = $user->createToken('admin-token')->plainTextToken;

// Return response JSON dengan user data dan token
return response()->json([
    'message' => 'Login berhasil',
    'user' => $user,
    'token' => $token,
], 200);
```

---

#### Get User Profile - `GET /user/profile`
**Endpoint Terlindungi** - Memerlukan authentikasi

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "user": {
        "id": 1,
        "name": "Admin PLN 1",
        "email": "admin1@pln.com",
        "role": "admin",
        "created_at": "2026-05-03T00:00:00.000000Z",
        "updated_at": "2026-05-03T00:00:00.000000Z"
    }
}
```

**Penjelasan Code:**
```php
public function getUser(Request $request)
{
    // Dapatkan user yang ter-autentikasi dari request
    // $request->user() menggunakan middleware auth:sanctum
    return response()->json([
        'user' => $request->user(),
    ], 200);
}
```

---

#### Logout - `POST /logout`
**Endpoint Terlindungi** - Memerlukan authentikasi

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "message": "Logout berhasil"
}
```

**Penjelasan Code:**
```php
public function logout(Request $request)
{
    // Hapus current access token dari database
    // User tidak bisa menggunakan token yang sama lagi
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Logout berhasil',
    ], 200);
}
```

---

### 2. **Pelanggan (Customer) Endpoints**

#### Get All Pelanggan - `GET /pelanggans`
**Endpoint Terlindungi** - Untuk dashboard website

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- Tidak ada

**Response (200):**
```json
[
    {
        "id": 1,
        "nama": "Budi Santoso",
        "alamat": "Jl. Merdeka No. 123, Jakarta",
        "no_meter": "123456789",
        "daya_listrik": 1300,
        "no_hp": "081234567890",
        "foto_path": "pelanggan_fotos/pelanggan_1_1715206800.jpg",
        "latitude": -6.2088,
        "longitude": 106.8456,
        "waktu_kunjungan": "2026-05-03T10:30:00.000000Z",
        "created_at": "2026-05-03T08:00:00.000000Z",
        "updated_at": "2026-05-03T10:30:00.000000Z"
    }
]
```

**Penjelasan Code:**
```php
public function index()
{
    // Query dari database table 'pelanggans'
    $pelanggans = DB::table('pelanggans')
        ->orderBy('created_at', 'desc')  // Urut dari terbaru
        ->get();                          // Get semua data

    return response()->json($pelanggans);
}
```

---

#### Sync Pelanggan (Flutter) - `POST /sync-pelanggan`
**Endpoint Terlindungi** - Dari aplikasi Flutter field officer

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "nama": "Bambang Wijaya",
    "alamat": "Jl. Ahmad Yani No. 456, Surabaya",
    "no_meter": "987654321",
    "id_pelanggan": "PLN-001",
    "daya_listrik": 2200,
    "daya": 2200,
    "no_hp": "082345678901",
    "foto_path": "pelanggan_fotos/pelanggan_1234.jpg",
    "latitude": -7.2504,
    "longitude": 112.7488,
    "waktu_kunjungan": "2026-05-03T14:00:00"
}
```

**Response (201):**
```json
{
    "status": "success",
    "id": 2,
    "message": "Data pelanggan berhasil disinkronkan ke server!"
}
```

**Penjelasan Code:**
```php
public function store(Request $request)
{
    // 1. VALIDASI INPUT
    // Semua field minimal ada nama dan alamat
    $validated = $request->validate([
        'nama' => 'required',                    // Wajib
        'alamat' => 'required',                  // Wajib
        'no_meter' => 'nullable|string',        // Opsional
        'id_pelanggan' => 'nullable|string',    // Opsional
        'daya_listrik' => 'nullable|integer',   // Opsional
        'daya' => 'nullable|integer',           // Opsional
        'no_hp' => 'nullable|string',           // Opsional
        'foto_path' => 'nullable|string',       // Opsional
        'latitude' => 'nullable|numeric|between:-90,90',    // Validasi koordinat
        'longitude' => 'nullable|numeric|between:-180,180', // Validasi koordinat
        'waktu_kunjungan' => 'nullable|date',   // Validasi format tanggal
    ]);

    // 2. NORMALIZE DATA
    // Gunakan no_meter, jika kosong gunakan id_pelanggan, jika kosong string kosong
    $no_meter = $request->no_meter ?? $request->id_pelanggan ?? '';
    
    // Gunakan daya_listrik, jika kosong gunakan daya
    $daya_listrik = $request->daya_listrik ?? $request->daya;

    // Normalisasi path foto (hapus path lokal)
    $fotoPath = $this->normalizeFotoPath($request->foto_path);

    // 3. INSERT KE DATABASE
    $id = DB::table('pelanggans')->insertGetId([
        'nama' => $request->nama,
        'alamat' => $request->alamat,
        'no_meter' => $no_meter,
        'daya_listrik' => $daya_listrik,
        'no_hp' => $request->no_hp,
        'foto_path' => $fotoPath,
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'waktu_kunjungan' => $request->waktu_kunjungan ? \Carbon\Carbon::parse($request->waktu_kunjungan) : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 4. RESPONSE
    return response()->json([
        'status' => 'success',
        'id' => $id,
        'message' => 'Data pelanggan berhasil disinkronkan ke server!'
    ], 201);
}

// Helper function untuk normalisasi path foto
private function normalizeFotoPath(?string $fotoPath): ?string
{
    if (!$fotoPath) {
        return null;
    }

    // Jika path dari Flutter local (mulai dengan /data/ atau file://)
    // Jangan simpan, return null
    if (str_starts_with($fotoPath, '/data/') || str_starts_with($fotoPath, 'file:')) {
        return null;
    }

    return $fotoPath;
}
```

---

#### Upload Foto Pelanggan - `POST /upload-foto/{id}`
**Endpoint Terlindungi** - Upload file image

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**URL Parameter:**
- `id` - ID pelanggan (integer)

**Form Data:**
```
foto: [binary image file]
```

**Constraints:**
- Format: JPEG, PNG, JPG
- Max size: 2MB

**Response (200):**
```json
{
    "status": "success",
    "foto_path": "pelanggan_fotos/pelanggan_1_1715206800.jpg",
    "foto_url": "http://localhost:8000/api/foto/pelanggan_fotos/pelanggan_1_1715206800.jpg",
    "message": "Foto berhasil diupload!"
}
```

**Response (400):**
```json
{
    "status": "error",
    "message": "File foto tidak ditemukan"
}
```

**Penjelasan Code:**
```php
public function uploadFoto(Request $request, $id)
{
    // VALIDASI FILE
    $request->validate([
        'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    // CEK FILE ADA
    if ($request->hasFile('foto')) {
        // GET FILE OBJECT
        $file = $request->file('foto');
        
        // GENERATE UNIQUE FILENAME
        // Format: pelanggan_{id}_{timestamp}.{ext}
        $filename = 'pelanggan_' . $id . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // SIMPAN KE PUBLIC DISK
        // Directory: storage/app/public/pelanggan_fotos/
        $path = $file->storeAs('pelanggan_fotos', $filename, 'public');

        // UPDATE FOTO_PATH DI DATABASE
        DB::table('pelanggans')->where('id', $id)->update([
            'foto_path' => $path,
            'updated_at' => now(),
        ]);

        // RETURN RESPONSE DENGAN PATH DAN URL
        return response()->json([
            'status' => 'success',
            'foto_path' => $path,
            'foto_url' => url('/api/foto/' . $path),
            'message' => 'Foto berhasil diupload!'
        ]);
    }

    // JIKA FILE TIDAK ADA
    return response()->json([
        'status' => 'error',
        'message' => 'File foto tidak ditemukan'
    ], 400);
}
```

---

#### Get Foto - `GET /foto/{path}`
**Endpoint Publik** - Download foto yang sudah diupload

**URL Parameter:**
```
path: pelanggan_fotos/pelanggan_1_1715206800.jpg
```

**Response:**
- Stream binary image file
- Content-Type: image/jpeg, image/png, etc.

**Penjelasan Code:**
```php
public function showFoto(string $path)
{
    // SANITASI PATH (hilangkan leading slash)
    $path = ltrim($path, '/');

    // CEK APAKAH FILE EXIST
    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        abort(404);  // Return 404 jika tidak ada
    }

    // RETURN FILE RESPONSE
    // Secara otomatis set content-type berdasarkan extension
    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
}
```

---

#### Delete Pelanggan - `DELETE /pelanggans/{id}`
**Endpoint Terlindungi** - Delete data pelanggan

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameter:**
- `id` - ID pelanggan (integer)

**Response (200):**
```json
{
    "status": "success",
    "message": "Data berhasil dihapus"
}
```

**Response (404):**
```json
{
    "status": "error",
    "message": "Data tidak ditemukan"
}
```

**Penjelasan Code:**
```php
public function destroy($id)
{
    // CARI DATA PELANGGAN
    $pelanggan = DB::table('pelanggans')->where('id', $id)->first();

    // CEK APAKAH DATA EXIST
    if (!$pelanggan) {
        return response()->json([
            'status' => 'error',
            'message' => 'Data tidak ditemukan'
        ], 404);
    }

    // HAPUS FOTO JIKA ADA
    if ($pelanggan->foto_path) {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($pelanggan->foto_path);
    }

    // HAPUS DATA DARI DATABASE
    DB::table('pelanggans')->where('id', $id)->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Data berhasil dihapus'
    ]);
}
```

---

## 🔐 Authentication Flow

### Diagram Flow
```
┌─────────────┐
│   Mobile    │
│  (Flutter)  │
└──────┬──────┘
       │
       │ 1. POST /login
       │ { email, password }
       ▼
┌────────────────────────────┐
│   AuthController::login()   │
│  - Validasi email/password │
│  - Hash::check() password  │
│  - Create Sanctum token    │
└──────┬─────────────────────┘
       │
       │ 2. Response dengan token
       │ { user, token }
       ▼
┌─────────────────────────────┐
│  Client menyimpan token     │
│  di local storage / shared  │
│  preferences                │
└──────┬──────────────────────┘
       │
       │ 3. Request dengan Authorization header
       │ Authorization: Bearer {token}
       ▼
┌────────────────────────────┐
│  Auth:sanctum Middleware   │
│  - Validasi token          │
│  - Load user dari token    │
│  - Attach ke request       │
└──────┬─────────────────────┘
       │
       │ 4. Request ke controller
       │ $request->user() tersedia
       ▼
┌──────────────────────┐
│    Controller        │
│  Process request     │
└──────────────────────┘
```

### Token Structure
```
Format: {ID}|{HASHED_TOKEN}

Contoh: 1|abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRST

Penjelasan:
- 1 = User ID
- | = Separator
- abcdef... = Token yang di-hash dengan SHA256
```

---

## 👤 Controllers & Logic

### AuthController.php
**Location:** `app/Http/Controllers/Api/AuthController.php`

**Fungsi Utama:**
- Menangani login user
- Menangani logout user
- Mengambil profil user yang ter-autentikasi

**Method Breakdown:**

#### 1. `login(Request $request)`
```php
/**
 * Login endpoint untuk admin
 */
public function login(Request $request)
{
    // Step 1: Validasi request
    $request->validate([
        'email' => 'required|email',    // Email harus ada dan format email
        'password' => 'required',       // Password harus ada
    ]);

    // Step 2: Cari user berdasarkan email
    $user = User::where('email', $request->email)->first();

    // Step 3: Verifikasi password
    // Hash::check() membandingkan plaintext dengan bcrypt hash
    if (! $user || ! Hash::check($request->password, $user->password)) {
        // Throw validation exception jika tidak sesuai
        throw ValidationException::withMessages([
            'email' => ['Email atau password salah.'],
        ]);
    }

    // Step 4: Buat token Sanctum
    // Setiap user bisa punya multiple tokens
    $token = $user->createToken('admin-token')->plainTextToken;

    // Step 5: Return response dengan user dan token
    return response()->json([
        'message' => 'Login berhasil',
        'user' => $user,
        'token' => $token,
    ], 200);
}
```

---

#### 2. `getUser(Request $request)`
```php
/**
 * Get current authenticated user
 */
public function getUser(Request $request)
{
    // $request->user() = user yang ter-autentikasi
    // Diset oleh middleware auth:sanctum
    // Mengembalikan object User dari database
    
    return response()->json([
        'user' => $request->user(),
    ], 200);
}
```

---

#### 3. `logout(Request $request)`
```php
/**
 * Logout endpoint
 */
public function logout(Request $request)
{
    // Dapatkan token yang sedang dipakai
    // currentAccessToken() = token aktif di request
    $request->user()->currentAccessToken()->delete();

    // Setelah didelete, token tidak bisa dipakai lagi
    // Client harus login ulang untuk dapat token baru

    return response()->json([
        'message' => 'Logout berhasil',
    ], 200);
}
```

---

### PelangganController.php
**Location:** `app/Http/Controllers/Api/PelangganController.php`

**Fungsi Utama:**
- CRUD operasi untuk data pelanggan
- Upload foto pelanggan
- Serve foto dari storage

**Method Breakdown:**

Sudah dijelaskan di atas pada section API Endpoints

---

## 📦 Models

### User.php
**Location:** `app/Models/User.php`

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// Attribute untuk Mass Assignment
#[Fillable(['name', 'email', 'password', 'role'])]
// Attribute untuk Hidden fields (tidak tampil saat toArray/toJson)
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // Traits untuk menambah functionality
    use HasFactory,      // Factory untuk testing
        Notifiable,      // Notification support
        HasApiTokens;    // Sanctum token support

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast email_verified_at ke datetime object
            'email_verified_at' => 'datetime',
            // Cast password ke hashed (automatic hashing)
            'password' => 'hashed',
        ];
    }
}
```

**Penjelasan:**
- **`Authenticatable`** - Menjadikan model sebagai user yang bisa di-authenticate
- **`HasApiTokens`** - Support untuk Sanctum API tokens
- **`Fillable`** - Field yang bisa di-mass-assign (create/update)
- **`Hidden`** - Field yang di-exclude saat di-serialize ke JSON
- **`casts`** - Type casting untuk attributes

---

## 🛣️ Routes

### api.php
**Location:** `routes/api.php`

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ===== PUBLIC ROUTES (Tidak butuh authentikasi) =====
Route::post('/login', [AuthController::class, 'login']);

// ===== PROTECTED ROUTES (Butuh auth:sanctum middleware) =====
Route::middleware('auth:sanctum')->group(function () {
    
    // Built-in route untuk get current user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Auth endpoints
    Route::get('/user/profile', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Pelanggan endpoints untuk Flutter App
    Route::post('/sync-pelanggan', [PelangganController::class, 'store']);
    Route::post('/upload-foto/{id}', [PelangganController::class, 'uploadFoto']);
    
    // Foto endpoint (publik untuk display)
    Route::get('/foto/{path}', [PelangganController::class, 'showFoto'])
        ->where('path', '.*');

    // Endpoints untuk Dashboard Website
    Route::get('/pelanggans', [PelangganController::class, 'index']);
    Route::delete('/pelanggans/{id}', [PelangganController::class, 'destroy']);
});
```

**Penjelasan Route:**

| Metode | Endpoint | Auth | Controller | Fungsi |
|--------|----------|------|------------|--------|
| POST | /login | ❌ | AuthController@login | Login user |
| GET | /user | ✅ | Built-in | Get current user |
| GET | /user/profile | ✅ | AuthController@getUser | Get profil user |
| POST | /logout | ✅ | AuthController@logout | Logout user |
| POST | /sync-pelanggan | ✅ | PelangganController@store | Sync data pelanggan |
| POST | /upload-foto/{id} | ✅ | PelangganController@uploadFoto | Upload foto |
| GET | /foto/{path} | ✅ | PelangganController@showFoto | Download foto |
| GET | /pelanggans | ✅ | PelangganController@index | Get semua pelanggan |
| DELETE | /pelanggans/{id} | ✅ | PelangganController@destroy | Delete pelanggan |

---

## 🗃️ Migrations

### Migration Timeline

#### 1. `0001_01_01_000000_create_users_table.php`
Membuat struktur tabel users, sessions, dan password reset

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();                              // Auto increment ID
    $table->string('name');                    // Nama user
    $table->string('email')->unique();         // Email unik (untuk login)
    $table->timestamp('email_verified_at')->nullable(); // Verifikasi email
    $table->string('password');                // Password (hashed)
    $table->rememberToken();                   // Token untuk "remember me"
    $table->timestamps();                      // created_at & updated_at
});
```

#### 2. `2026_04_02_035534_create_pelanggans_table.php`
Membuat tabel pelanggan dengan field dasar

```php
Schema::create('pelanggans', function (Blueprint $table) {
    $table->id();                  // Auto increment ID
    $table->string('nama');        // Nama pelanggan
    $table->string('alamat');      // Alamat pelanggan
    $table->string('no_meter');    // Nomor meteran
    $table->timestamps();          // created_at & updated_at
});
```

#### 3. `2026_04_02_045027_add_fields_to_pelanggans_table.php`
Menambah field ke tabel pelanggans

```php
$table->string('id_pelanggan')->nullable();
$table->integer('daya_listrik')->nullable();
$table->string('no_hp')->nullable();
$table->string('foto_path')->nullable();
```

#### 4. `2026_04_14_000000_add_location_and_visit_time_to_pelanggans_table.php`
Menambah field lokasi dan waktu kunjungan

```php
$table->decimal('latitude', 10, 8)->nullable();
$table->decimal('longitude', 11, 8)->nullable();
$table->timestamp('waktu_kunjungan')->nullable();
```

#### 5. `2026_05_03_000000_add_role_to_users_table.php`
Menambah field role ke users untuk authorization

```php
$table->string('role')->default('user');  // admin / user
```

---

## 🌱 Seeding

### DatabaseSeeder.php
**Location:** `database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;  // Skip model events saat seeding

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 3 admin accounts
        User::factory()->create([
            'name' => 'Admin PLN 1',
            'email' => 'admin1@pln.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Admin PLN 2',
            'email' => 'admin2@pln.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Admin PLN 3',
            'email' => 'admin3@pln.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);
    }
}
```

**Penjelasan:**
- Seeder dijalankan dengan: `php artisan db:seed`
- Membuat 3 admin account dengan password default `admin123456`
- Password secara otomatis di-hash oleh UserFactory (attribute `password` => `hashed`)
- Gunakan untuk development/testing

**⚠️ CATATAN KEAMANAN:**
- Password hardcoded hanya untuk development
- Di production, gunakan password random dan secure
- Implement password reset functionality

---

## 🚀 Running the Application

### Setup Awal
```bash
# 1. Install dependencies
composer install

# 2. Copy .env file
cp .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Run migrations
php artisan migrate

# 5. Run seeders
php artisan db:seed

# 6. Start development server
php artisan serve
```

### Testing API dengan Postman

1. **Login**
   ```
   POST http://localhost:8000/api/login
   Body: {
       "email": "admin1@pln.com",
       "password": "admin123456"
   }
   ```

2. **Copy token dari response**

3. **Gunakan token di header untuk request berikutnya**
   ```
   Authorization: Bearer {token_dari_login}
   ```

---

## � Cek Data di SQLite

SQLite adalah database file-based yang tersimpan dalam satu file `.sqlite`. Untuk aplikasi Laravel, database tersimpan di `database/database.sqlite`.

### 1. **Menggunakan Laravel Tinker (Recommended)**

Laravel Tinker adalah REPL (interactive shell) untuk Laravel yang memudahkan query database.

#### Membuka Tinker
```bash
php artisan tinker
```

#### Query Data Users
```php
// Melihat semua users
User::all();

// Melihat user dengan ID 1
User::find(1);

// Melihat user dengan email tertentu
User::where('email', 'admin1@pln.com')->first();

// Melihat jumlah user
User::count();

// Melihat semua token user
DB::table('personal_access_tokens')->get();
```

#### Query Data Pelanggan
```php
// Melihat semua pelanggan
DB::table('pelanggans')->get();

// Melihat pelanggan dengan ID tertentu
DB::table('pelanggans')->where('id', 1)->first();

// Melihat pelanggan yang punya no_meter tertentu
DB::table('pelanggans')->where('no_meter', '123456789')->first();

// Melihat jumlah pelanggan
DB::table('pelanggans')->count();

// Melihat pelanggan yang dibuat hari ini
DB::table('pelanggans')
    ->whereDate('created_at', today())
    ->get();
```

#### Example Output
```
=> [
     App\Models\User {
       id: 1,
       name: "Admin PLN 1",
       email: "admin1@pln.com",
       email_verified_at: null,
       password: "$2y$12$...",
       role: "admin",
       created_at: "2026-05-03 00:00:00",
       updated_at: "2026-05-03 00:00:00",
     },
   ]
```

---

### 2. **Menggunakan SQLite CLI**

SQLite CLI adalah command-line tool untuk berinteraksi dengan database SQLite.

#### Install SQLite3 (jika belum)
```bash
# Windows (jika menggunakan chocolatey)
choco install sqlite

# Atau download dari https://www.sqlite.org/download.html
```

#### Membuka Database
```bash
sqlite3 database/database.sqlite
```

#### Query Commands

**Melihat semua tabel:**
```sql
.tables
```

**Output:**
```
cache                       jobs                        password_reset_tokens
job_batches                 migrations                  personal_access_tokens
pelanggans                  sessions                    users
```

**Melihat struktur tabel users:**
```sql
.schema users
```

**Output:**
```sql
CREATE TABLE users (
  id bigint primary key,
  name varchar not null,
  email varchar unique not null,
  email_verified_at datetime,
  password varchar not null,
  remember_token varchar,
  role varchar default 'user',
  created_at datetime,
  updated_at datetime
);
```

**Melihat semua data users:**
```sql
SELECT * FROM users;
```

**Output:**
```
1|Admin PLN 1|admin1@pln.com||$2y$12$...|NULL|admin|2026-05-03 00:00:00|2026-05-03 00:00:00
2|Admin PLN 2|admin2@pln.com||$2y$12$...|NULL|admin|2026-05-03 00:00:00|2026-05-03 00:00:00
3|Admin PLN 3|admin3@pln.com||$2y$12$...|NULL|admin|2026-05-03 00:00:00|2026-05-03 00:00:00
```

**Melihat data users dengan format lebih rapi:**
```sql
.mode column
.headers on
SELECT id, name, email, role FROM users;
```

**Output:**
```
id  name          email             role
--  -----------   ----------------  -----
1   Admin PLN 1   admin1@pln.com    admin
2   Admin PLN 2   admin2@pln.com    admin
3   Admin PLN 3   admin3@pln.com    admin
```

**Melihat semua data pelanggan:**
```sql
SELECT id, nama, alamat, no_meter, no_hp FROM pelanggans;
```

**Menghitung jumlah pelanggan:**
```sql
SELECT COUNT(*) as total FROM pelanggans;
```

**Melihat pelanggan dengan nomor meter tertentu:**
```sql
SELECT * FROM pelanggans WHERE no_meter = '123456789';
```

**Melihat token yang aktif:**
```sql
SELECT id, tokenable_id, name, created_at FROM personal_access_tokens;
```

**Keluar dari SQLite CLI:**
```sql
.quit
```

---

### 3. **Menggunakan GUI Tools**

#### **DBeaver (Free & Powerful)**

1. Download dari https://dbeaver.io/
2. Install dan buka DBeaver
3. Klik `Database` → `New Database Connection`
4. Pilih `SQLite`
5. Browse ke file `database/database.sqlite`
6. Test Connection dan Finish
7. Expand database → expand table → lihat data

#### **SQLite Browser (Simple)**

1. Download dari https://sqlitebrowser.org/
2. Install dan buka
3. File → Open → pilih `database/database.sqlite`
4. Klik tab `Browse Data`
5. Pilih tabel di dropdown
6. Lihat data dalam bentuk table

#### **VS Code Extension: SQLite**

1. Install extension `SQLite` (author: alexcvzz)
2. Klik kanan file `database/database.sqlite`
3. Pilih `Open Database`
4. Database akan terbuka di panel
5. Klik tabel untuk melihat data
6. Klik "Run Query" untuk custom SQL

---

### 4. **Menggunakan PHP Artisan Tinker (Cheat Sheet)**

#### Users
```php
// Get all users
User::all()

// Get user count
User::count()

// Get admin users only
User::where('role', 'admin')->get()

// Get user by email
User::where('email', 'admin1@pln.com')->first()

// Create new user (untuk testing)
User::create([
  'name' => 'Admin Baru',
  'email' => 'admin_baru@pln.com',
  'password' => Hash::make('password123'),
  'role' => 'admin'
])

// Delete user
User::find(1)->delete()

// Update user
User::find(1)->update(['name' => 'Nama Baru'])
```

#### Pelanggan
```php
// Get all pelanggan
DB::table('pelanggans')->get()

// Get pelanggan count
DB::table('pelanggans')->count()

// Get pelanggan dengan foto
DB::table('pelanggans')->whereNotNull('foto_path')->get()

// Get pelanggan berdasarkan no_meter
DB::table('pelanggans')->where('no_meter', '123456789')->first()

// Get pelanggan hari ini
DB::table('pelanggans')->whereDate('created_at', today())->get()

// Get pelanggan dengan koordinat
DB::table('pelanggans')->whereNotNull('latitude')->get()

// Delete pelanggan
DB::table('pelanggans')->where('id', 1)->delete()

// Count pelanggan by role
DB::table('pelanggans')->groupBy('role')->selectRaw('role, count(*) as total')->get()
```

#### Personal Access Tokens
```php
// Get semua tokens
DB::table('personal_access_tokens')->get()

// Get tokens untuk user ID 1
DB::table('personal_access_tokens')->where('tokenable_id', 1)->get()

// Get tokens yang masih aktif (tidak expired)
DB::table('personal_access_tokens')->whereNull('expires_at')->get()

// Delete token tertentu
DB::table('personal_access_tokens')->where('id', 1)->delete()

// Delete semua token user
DB::table('personal_access_tokens')->where('tokenable_id', 1)->delete()
```

---

### 5. **Contoh Skenario Cek Data**

#### Skenario 1: Verifikasi Admin Berhasil Login
```bash
# Buka tinker
php artisan tinker

# Cek user dengan email admin1@pln.com
User::where('email', 'admin1@pln.com')->first()

# Output:
=> App\Models\User {
     id: 1,
     name: "Admin PLN 1",
     email: "admin1@pln.com",
     password: "$2y$12$...",
     role: "admin",
     ...
   }

# Cek token yang dibuat
DB::table('personal_access_tokens')
  ->where('tokenable_id', 1)
  ->latest()
  ->first()
```

#### Skenario 2: Verifikasi Sinkronisasi Data Pelanggan
```bash
# Buka tinker
php artisan tinker

# Lihat total pelanggan
DB::table('pelanggans')->count()

# Lihat pelanggan terbaru
DB::table('pelanggans')->latest()->first()

# Lihat data pelanggan dengan detail
DB::table('pelanggans')
  ->select('id', 'nama', 'no_meter', 'no_hp', 'latitude', 'longitude', 'created_at')
  ->latest()
  ->limit(5)
  ->get()
```

#### Skenario 3: Cek Foto yang Tersimpan
```bash
# Buka tinker
php artisan tinker

# Lihat pelanggan yang punya foto
DB::table('pelanggans')
  ->whereNotNull('foto_path')
  ->select('id', 'nama', 'foto_path')
  ->get()

# Output:
=> Illuminate\Support\Collection {
     all: [
       {
         id: 1,
         nama: "Budi Santoso",
         foto_path: "pelanggan_fotos/pelanggan_1_1715206800.jpg",
       },
     ],
   }
```

#### Skenario 4: Cek Lokasi Pelanggan
```bash
# Buka tinker
php artisan tinker

# Lihat pelanggan dengan koordinat lengkap
DB::table('pelanggans')
  ->whereNotNull('latitude')
  ->whereNotNull('longitude')
  ->select('id', 'nama', 'alamat', 'latitude', 'longitude')
  ->get()

# Output:
=> Illuminate\Support\Collection {
     all: [
       {
         id: 1,
         nama: "Budi Santoso",
         alamat: "Jl. Merdeka No. 123",
         latitude: "-6.2088",
         longitude: "106.8456",
       },
     ],
   }
```

---

### 6. **Quick Reference: SQLite Commands**

| Command | Fungsi |
|---------|--------|
| `.tables` | Lihat semua tabel |
| `.schema [table]` | Lihat struktur tabel |
| `.mode column` | Format output sebagai column |
| `.headers on` | Tampilkan nama kolom |
| `.quit` / `.exit` | Keluar dari SQLite |
| `SELECT * FROM [table];` | Lihat semua data |
| `SELECT COUNT(*) FROM [table];` | Hitung jumlah data |
| `SELECT * FROM [table] LIMIT 10;` | Lihat 10 data pertama |
| `SELECT * FROM [table] WHERE [condition];` | Query dengan kondisi |
| `DELETE FROM [table] WHERE id=1;` | Hapus data |

---

### 7. **Database File Location**

File database SQLite disimpan di:
```
c:\Users\nitro\Joki\pln-backend\database\database.sqlite
```

**Size file:**
```bash
# Windows - lihat di File Explorer atau gunakan:
dir database\database.sqlite

# Output:
Volume in drive C is Windows
Directory of c:\Users\nitro\Joki\pln-backend\database

05/03/2026  10:30 AM       123,456 database.sqlite
```

---

## �📝 Summary

### Teknologi Stack
- **Framework:** Laravel 11
- **Database:** SQLite
- **Auth:** Laravel Sanctum (Token-Based)
- **API Style:** RESTful

### Fitur Utama
1. **Authentication** - Login/Logout dengan token
2. **Pelanggan Management** - CRUD operasi
3. **Foto Upload** - Upload dan retrieve foto
4. **Location Tracking** - Store latitude/longitude
5. **Role-Based** - Admin dan user roles

### Folder Structure
```
app/
├── Http/Controllers/Api/          # API Controllers
├── Models/                         # Database models
└── Providers/                      # Service providers

routes/
├── api.php                        # API routes
└── web.php                        # Web routes

database/
├── migrations/                    # Database schema
├── seeders/                       # Seed data
└── factories/                     # Model factories

config/                            # Configuration files
bootstrap/                         # Bootstrap files
```

---

**Dokumentasi dibuat pada:** 3 Mei 2026  
**Versi Laravel:** 11  
**Status:** ✅ Production Ready


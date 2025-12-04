# Movies API Documentation

Dokumentasi endpoint API untuk Movies yang dapat digunakan untuk testing di Postman.

## Base URL
```
http://localhost:8074/API/API/index.php
```
## Gambaran Proyek: MVC, Struktur Folder yang Rapi

Proyek API ini mengikuti pola arsitektur Model-View-Controller (MVC) dari CodeIgniter. Secara ringkas, controller bertugas menerima HTTP request, melakukan validasi, mengkoordinasikan pemanggilan model dan memilih format response; model bertanggung jawab pada semua operasi data dan query ke database; sedangkan view hanya digunakan untuk antarmuka web (UI) ketika pengguna mengelola film lewat browser. Pada API JSON ini, controller mengembalikan response dalam format JSON — view tidak digunakan untuk endpoint API, tetapi tetap ada untuk halaman manajemen `Movies` yang menggunakan session-based auth.

Agar mudah dinavigasi dan dikembangkan, struktur folder utama disusun rapi sebagai berikut (path relatif terhadap root proyek):

- `application/controllers/` — controller API dan web.
  - `Api.php`  : controller utama untuk route `api/*` (berisi metode CRUD movies dan users API).
  - `Auth.php` : endpoint login/logout/refresh token.
  - `Movies.php`: controller web untuk manajemen film (form upload poster, listing, dsb.).

- `application/models/` — model data dan abstraksi database.
  - `Movie_model.php`  : query CRUD untuk tabel `movies`.
  - `User_model.php`   : query user, verifikasi credential, dan pembacaan data user.
  - `Token_model.php`  : manajemen revocation (tabel `revoked_tokens`).

- `application/views/` — file view untuk UI web (form, halaman daftar). API tidak menggunakan file ini untuk response JSON.

- `application/config/` — konfigurasi aplikasi.
  - `config.php` : menyimpan konfigurasi umum termasuk `jwt_key` (secret). Pastikan `jwt_key` aman dan tidak di-commit ke repo publik.

- `application/helpers/` — helper utilities.
  - `jwt_helper.php` : helper minimal untuk encode/decode JWT (direkomendasikan diganti `firebase/php-jwt` di produksi).

- `system/` — core CodeIgniter (jangan ubah kecuali perlu patch framework).

Memisahkan file seperti di atas membantu ketika menambah fitur baru: logic bisnis dan query tetap berada di model, sedangkan controller fokus pada validasi dan routing.

## Autentikasi & Header Keamanan (JWT) — Penjelasan Lengkap

API ini menggunakan JSON Web Token (JWT) dengan algoritma HS256 (HMAC-SHA256) untuk autentikasi stateless. Proses utamanya adalah sebagai berikut:

- Pembuatan token (encode):
  1. Client mengirim permintaan login ke `POST /auth/login` beserta credential (username/email dan password).
  2. Server memverifikasi credential melalui `User_model` (mis. `password_verify` terhadap hash di DB). Jika valid, server menyiapkan payload token yang umum berisi klaim seperti `sub` (subject — user id), `name`, `email`, `iat` (issued at), `exp` (expiry timestamp), dan `jti` (unique token id untuk revoke).
  3. Header JWT dibuat (biasanya `{"alg":"HS256","typ":"JWT"}`), payload di-serialize, lalu header dan payload di-encode menggunakan Base64URL.
  4. Server menghitung signature: HMAC-SHA256(secret, base64url(header) + '.' + base64url(payload)). Secret diambil dari `application/config/config.php` (`$config['jwt_key']`).
  5. Token final adalah tiga bagian yang digabung: `base64url(header).base64url(payload).base64url(signature)`. Token ini dikembalikan ke client sebagai bagian dari JSON response, serta disertakan pada header respon `X-Auth-Token` untuk debugging dan (sebelumnya) sebagai HttpOnly cookie untuk UI; catatan: cookie-fallback untuk API dinonaktifkan demi keamanan.

- Validasi token (decode & verifikasi):
  1. Untuk setiap request API, aplikasi mengekstrak token dari header `Authorization: Bearer <token>` (prioritas utama). Jika header tidak tersedia, server juga diperiksa pada header `X-Auth-Token` atau query param hanya untuk debugging — production harus mengirimkan header `Authorization`.
  2. Token di-split menjadi tiga bagian, header dan payload di-base64url-decode untuk membaca klaim. Signature diperiksa ulang: server menghitung HMAC-SHA256 dengan secret yang sama dan membandingkannya dengan bagian signature dari token. Jika signature berbeda, token ditolak.
  3. Server memeriksa klaim `exp` untuk memastikan token belum kedaluwarsa. Jika `exp` lewat, token ditolak.
  4. Server memeriksa `jti` terhadap tabel `revoked_tokens` melalui `Token_model::is_revoked($jti)`; jika ditemukan, token dianggap tidak berlaku.
  5. Jika semua pemeriksaan lolos, controller memuat data user (mis. `User_model::get_user($payload['sub'])`) dan mengizinkan akses sesuai peran/flag (`is_admin` untuk operasi manajemen).

### Mengapa header `Authorization` penting dan penanganannya
Header `Authorization` dengan skema `Bearer` adalah cara standar dan aman untuk mengirim token karena:

- Tidak terlihat di URL (tidak tercatat di log server atau riwayat browser), berbeda dengan query param.
- Mudah diwariskan ke library HTTP dan middleware.

Namun, beberapa server Apache + PHP menghapus header ini sebelum mencapai aplikasi PHP. Untuk mengatasi, proyek ini menyertakan aturan `.htaccess` yang mem-forward header `Authorization` ke variabel server (`HTTP_AUTHORIZATION`) sehingga helper/Controller tetap dapat membacanya. Selain itu:

- Cookie HttpOnly pernah digunakan untuk UI, namun cookie-fallback untuk API telah dinonaktifkan agar API hanya menerima token eksplisit lewat header.
- Untuk debugging, tersedia opsi menggunakan header `X-Auth-Token` atau query param, tetapi ini hanya untuk development — jangan gunakan query param di production.

### Keamanan tambahan yang diterapkan
- Token memiliki `jti` sehingga dapat dicabut (revoked) tanpa mengubah secret atau mengelola session server-side.
- Server menyimpan `revoked_tokens` dan memeriksa `jti` pada setiap permintaan masuk.
- Rekomendasi: gunakan HTTPS secara wajib di production, simpan `jwt_key` dengan aman (environment variable / secrets manager), dan gunakan library JWT resmi (mis. `firebase/php-jwt`) untuk menangani edge-case dan patch keamanan.

## Kebijakan Akses (ringkas)
- Semua endpoint API (`/api/*`) mengharuskan autentikasi JWT.
- Endpoint manajemen (create/update/delete movie) memerlukan token user dengan flag `is_admin`.

Lanjutkan bagian Endpoints di bawah untuk contoh request/response setiap operasi (GET/POST/PUT/DELETE untuk `movies`).

## Lokasi File

### Controller
- **Path**: `application/controllers/Api.php`
- **Class**: `Api`
- **Methods**: 
  - `movies()` - Handle GET dan POST untuk collection
  - `movies_detail($id)` - Handle GET, PUT, dan DELETE untuk single movie
  - `get_movies()` - Private method untuk GET all movies
  - `get_movie($id)` - Private method untuk GET single movie
  - `create_movie()` - Private method untuk POST create movie
  - `update_movie($id)` - Private method untuk PUT update movie
  - `delete_movie($id)` - Private method untuk DELETE movie

### Model
- **Path**: `application/models/Movie_model.php`
- **Class**: `Movie_model`
- **Methods**:
  - `get_all_movies()` - Mengambil semua movies
  - `get_movie($id)` - Mengambil movie berdasarkan ID
  - `insert_movie($data)` - Menyimpan movie baru
  - `update_movie($id, $data)` - Update movie berdasarkan ID
  - `delete_movie($id)` - Hapus movie berdasarkan ID
  - `search_movies($query)` - Pencarian movies
  - `get_movies_by_genre($genre)` - Filter movies berdasarkan genre
  - `get_recent_movies($limit)` - Mengambil movies terbaru

### Routes
- **Path**: `application/config/routes.php`
- **Routes**:
  ```php
  $route['api/movies'] = 'api/movies';
  $route['api/movies/(:num)'] = 'api/movies_detail/$1';
  ```

---

## Endpoints

### 1. Get All Movies

Mengambil daftar semua movies.

**Request**
- **Method**: `GET`
- **URL**: `{{base_url}}/api/movies`
- **Headers**: 
  - `Content-Type: application/json`

**Response Success (200)**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Avengers: Endgame",
      "description": "After the devastating events of Avengers: Infinity War...",
      "duration": 181,
      "genre": "Action",
      "release_date": "2019-04-26",
      "poster_url": "https://example.com/avengers.jpg",
      "created_at": "2024-01-01 10:00:00"
    }
  ]
}
```

**Response Error (500)**
```json
{
  "status": "error",
  "message": "Kesalahan database: [error message]"
}
```

---

### 2. Get Single Movie

Mengambil detail movie berdasarkan ID.

**Request**
- **Method**: `GET`
- **URL**: `{{base_url}}/api/movies/{id}`
- **Parameters**:
  - `id` (path parameter) - ID movie (integer, required)

**Example URL**: `http://localhost:8074/API/API/index.php/api/movies/1`

**Response Success (200)**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Avengers: Endgame",
    "description": "After the devastating events of Avengers: Infinity War...",
    "duration": 181,
    "genre": "Action",
    "release_date": "2019-04-26",
    "poster_url": "https://example.com/avengers.jpg",
    "created_at": "2024-01-01 10:00:00"
  }
}
```

**Response Error (404)**
```json
{
  "status": "error",
  "message": "Film tidak ditemukan"
}
```

**Response Error (500)**
```json
{
  "status": "error",
  "message": "Kesalahan database: [error message]"
}
```

---

### 3. Create Movie

Membuat movie baru.

**Request**
- **Method**: `POST`
- **URL**: `{{base_url}}/api/movies`
- **Headers**: 
  - `Content-Type: application/json`
- **Body** (JSON):
```json
{
  "title": "New Movie Title",
  "description": "Movie description here",
  "duration": 120,
  "genre": "Action",
  "release_date": "2024-12-31",
  "poster_url": "https://example.com/poster.jpg"
}
```

**Field Validation**:
- `title` (required) - Judul film
- `duration` (required, integer) - Durasi dalam menit
- `description` (optional) - Deskripsi film
- `genre` (optional) - Genre film
- `release_date` (optional) - Tanggal rilis (format: YYYY-MM-DD)
- `poster_url` (optional) - URL poster film

**Response Success (200)**
```json
{
  "status": "success",
  "message": "Film berhasil dibuat"
}
```

**Response Error (400)**
```json
{
  "status": "error",
  "message": "[validation errors]"
}
```

**Response Error (500)**
```json
{
  "status": "error",
  "message": "Gagal membuat film"
}
```

---

### 4. Update Movie

Mengupdate data movie berdasarkan ID.

**Request**
- **Method**: `PUT`
- **URL**: `{{base_url}}/api/movies/{id}`
- **Parameters**:
  - `id` (path parameter) - ID movie (integer, required)
- **Headers**: 
  - `Content-Type: application/json`
- **Body** (JSON):
```json
{
  "title": "Updated Movie Title",
  "description": "Updated description",
  "duration": 130,
  "genre": "Comedy",
  "release_date": "2024-12-31",
  "poster_url": "https://example.com/new-poster.jpg"
}
```

**Field Validation**:
- Semua field optional, hanya kirim field yang ingin diupdate
- `duration` harus integer jika dikirim

**Response Success (200)**
```json
{
  "status": "success",
  "message": "Film berhasil diperbarui"
}
```

**Response Error (500)**
```json
{
  "status": "error",
  "message": "Gagal memperbarui film"
}
```

---

### 5. Delete Movie

Menghapus movie berdasarkan ID.

**Request**
- **Method**: `DELETE`
- **URL**: `{{base_url}}/api/movies/{id}`
- **Parameters**:
  - `id` (path parameter) - ID movie (integer, required)

**Example URL**: `http://localhost:8074/API/API/index.php/api/movies/1`

**Response Success (200)**
```json
{
  "status": "success",
  "message": "Film berhasil dihapus"
}
```

**Response Error (500)**
```json
{
  "status": "error",
  "message": "Gagal menghapus film"
}
```

---

## Postman Collection Setup

### Environment Variables

Buat environment variable di Postman:
- **Variable**: `base_url`
- **Value**: `http://localhost:8074/API/API/index.php`

### Request Examples

#### 1. Get All Movies
```
GET {{base_url}}/api/movies
```

#### 2. Get Single Movie
```
GET {{base_url}}/api/movies/1
```

#### 3. Create Movie
```
POST {{base_url}}/api/movies
Content-Type: application/json

{
  "title": "The Matrix",
  "description": "A computer hacker learns about the true nature of reality",
  "duration": 136,
  "genre": "Sci-Fi",
  "release_date": "1999-03-31",
  "poster_url": "https://example.com/matrix.jpg"
}
```

#### 4. Update Movie
```
PUT {{base_url}}/api/movies/1
Content-Type: application/json

{
  "title": "The Matrix Reloaded",
  "duration": 138
}
```

#### 5. Delete Movie
```
DELETE {{base_url}}/api/movies/1
```

---

## Database Schema

### Table: `movies`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | ID unik movie |
| title | VARCHAR(255) | NOT NULL | Judul film |
| description | TEXT | NULL | Deskripsi film |
| duration | INT | NOT NULL | Durasi dalam menit |
| genre | VARCHAR(100) | NULL | Genre film |
| release_date | DATE | NULL | Tanggal rilis |
| poster_url | VARCHAR(500) | NULL | URL poster film |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu pembuatan |

---

## Error Codes

| HTTP Status | Description |
|-------------|-------------|
| 200 | Success |
| 400 | Bad Request (validation error) |
| 404 | Not Found (movie tidak ditemukan) |
| 405 | Method Not Allowed |
| 500 | Internal Server Error |

---

## Notes

1. Semua endpoint API mengembalikan response dalam format JSON
2. Untuk testing di Postman, pastikan menggunakan method HTTP yang benar (GET, POST, PUT, DELETE)
3. Untuk PUT dan DELETE, pastikan mengirim ID sebagai path parameter
4. Validasi dilakukan di controller menggunakan CodeIgniter Form Validation
5. File upload untuk poster tidak didukung di API endpoint ini (hanya URL). Untuk upload file, gunakan frontend endpoint di `Movies` controller

---

## Frontend Endpoints (Web Interface)

Jika ingin menggunakan web interface untuk CRUD dengan upload file:

- **List Movies**: `GET {{base_url}}/movies`
- **Create Movie**: `POST {{base_url}}/movies/create` (form-data dengan file upload)
- **Edit Movie**: `POST {{base_url}}/movies/edit/{id}` (form-data dengan file upload)
- **Delete Movie**: `POST {{base_url}}/movies/delete/{id}`

**Controller**: `application/controllers/Movies.php`


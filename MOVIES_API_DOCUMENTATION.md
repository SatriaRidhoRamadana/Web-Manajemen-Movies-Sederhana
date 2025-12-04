# Movies API Documentation

Dokumentasi endpoint API untuk Movies yang dapat digunakan untuk testing di Postman.

## Base URL
```
http://localhost:8074/API/API/index.php
```

## Gambaran Proyek: MVC dan Struktur Folder

Proyek API ini dibangun menggunakan pola arsitektur Model-View-Controller (MVC) standar CodeIgniter:

- **Model**: menangani akses dan logika data (mis. `application/models/Movie_model.php`, `User_model.php`, `Token_model.php`). Semua query ke database berada di layer ini.
- **View**: berisi tampilan web (HTML) yang dipakai oleh controller `Movies.php` untuk interface manajemen. Endpoint API sendiri mengembalikan JSON, bukan view.
- **Controller**: menerima HTTP request, melakukan validasi, memanggil model, dan mengembalikan response JSON. Contoh controller API adalah `application/controllers/Api.php` (menangani route `api/movies`) dan `application/controllers/Auth.php` (login, logout, refresh token).

Struktur folder utama (ringkas):

- `application/controllers/`  — controller untuk API dan web (mis. `Api.php`, `Movies.php`, `Auth.php`).
- `application/models/`       — model untuk entitas (mis. `Movie_model.php`, `User_model.php`, `Token_model.php`).
- `application/views/`        — view untuk tampilan web (form, daftar movie, dll.).
- `application/config/`       — konfigurasi aplikasi (termasuk `config.php` yang menyimpan `jwt_key`).
- `application/helpers/`      — helper, termasuk `jwt_helper.php` (fungsi sederhana untuk encode/decode JWT).
- `system/`                  — core CodeIgniter.

Memahami pemisahan ini penting saat menambah fitur atau memperbaiki bug: perubahan business logic → model; routing/flow → controller; tampilan → view.

## Autentikasi & Header Keamanan (JWT)

API ini menggunakan JSON Web Token (JWT) bertanda tangan HS256 untuk autentikasi stateless. Ringkasan penggunaannya:

- Saat login (`POST /auth/login`) server mengembalikan JWT yang berisi klaim utama seperti `sub` (user id), `name`, `email`, `iat`, `exp`, dan `jti`.
- Semua request ke endpoint API harus menyertakan token di header `Authorization` dengan format berikut:

```
Authorization: Bearer <JWT>
```

- Contoh alur singkat dari client (PowerShell/curl.exe):

```powershell
# 1) Login (dapatkan token)
curl.exe -i -X POST "http://localhost:8074/API/API/index.php/auth/login" -H "Content-Type: application/json" -d '{"username":"admin","password":"admin123"}'

# 2) Gunakan token pada header Authorization untuk panggil API
curl.exe -i -H "Authorization: Bearer <TOKEN>" "http://localhost:8074/API/API/index.php/api/movies"
```

- Catatan teknis penting:
  - Pada beberapa konfigurasi Apache/PHP, header `Authorization` dapat dihilangkan oleh server. Proyek ini sudah menambahkan aturan `.htaccess` untuk meneruskan header Authorization ke PHP; jika token tidak diterima oleh aplikasi, periksa konfigurasi server/`.htaccess`.
  - API hanya menerima token lewat header (`Authorization`) di jalur produksi. Untuk tujuan debugging, server juga menerima header `X-Auth-Token` atau query param token, tetapi cookie-fallback telah dinonaktifkan untuk API karena alasan keamanan.
  - Token dapat dicabut (revoked) dan mekanisme revocation disimpan pada tabel `revoked_tokens` melalui `Token_model`.

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


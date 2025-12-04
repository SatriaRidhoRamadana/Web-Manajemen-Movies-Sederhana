# API & Security Documentation

Dokumentasi singkat untuk project API (CodeIgniter 3) — fokus pada endpoint API (`movies`) dan header keamanan.

---

## Lokasi dan Base URL
- Root project: `c:\xampp\htdocs\API\API`
- Base URL (sesuai `application/config/config.php`): `http://localhost:8074/API/API/`
- Endpoint API umum (prefix): `http://localhost:8074/API/API/index.php/api/`

Contoh endpoint movies:
- GET all movies: `GET /index.php/api/movies`
- POST create movie: `POST /index.php/api/movies` (JSON body)
- GET movie by id: `GET /index.php/api/movies/{id}`
- PUT update movie: `PUT /index.php/api/movies/{id}` (JSON body)
- DELETE movie: `DELETE /index.php/api/movies/{id}`

> Catatan: route default diset di `application/config/routes.php`.

---

## Authentication — Bearer Token (JWT)
- Jenis: JSON Web Token (JWT), algoritma tanda tangan HMAC-SHA256 (HS256).
- Secret key tersimpan di `application/config/config.php` → `$config['jwt_key']` (ganti ke secret yang kuat untuk produksi).
- Token dibuat di `application/controllers/Auth.php` pada method `login()` memakai helper `application/helpers/jwt_helper.php` (fungsi `jwt_encode()`).
- Token diverifikasi di constructor `application/controllers/Api.php` (dan juga `Movies.php`), menggunakan `jwt_decode()`.

Payload yang disertakan (claims):
- `sub` : user id (integer)
- `name`: nama user
- `email`: email user
- `iat` : issued at (timestamp)
- `exp` : expiry (timestamp)
- `jti` : JWT ID (digunakan untuk revocation)

Revocation: ada model `application/models/Token_model.php` yang menyimpan `jti` yang dibatalkan (blacklist). `Auth::logout()` dan `Auth::refresh()` mencatat revocation.

---

## Header yang digunakan / Diperlukan
- `Authorization: Bearer <JWT>`
  - Header utama untuk otentikasi API. `Api.php` mencari token di beberapa sumber (dengan preferensi header ini).
- `X-Auth-Token: <JWT>`
  - Header alternatif yang dipakai untuk debugging dan kompatibilitas (server akan menerima ini jika disertakan).
- `token` (query param)
  - Fallback untuk pengujian cepat: `?token=<JWT>` — tidak direkomendasikan untuk produksi.

Response / login headers (server):
- `X-Auth-Token: <JWT>`
  - Disertakan oleh `Auth::login()` untuk inspeksi di Network tab.
- `Set-Cookie: api_token=<JWT>; HttpOnly` — login juga menetapkan cookie HttpOnly, tetapi server-side API cookie-fallback telah di-removed supaya API mensyaratkan header eksplisit.
- `Cache-Control: no-store, no-cache, must-revalidate` — mencegah caching token di client/proxy.

Keamanan header HTTP yang disarankan (belum semuanya dipasang):
- `X-Content-Type-Options: nosniff`  (recommended)
- `X-Frame-Options: DENY`  (recommended)
- `Content-Security-Policy` (sesuaikan)  (recommended)
- `Referrer-Policy: no-referrer`  (recommended)
- `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` (aktifkan untuk HTTPS di produksi)
- `Permissions-Policy` (optional)

> Catatan: saya sudah menambahkan `.htaccess` rewrite rule untuk meneruskan `Authorization` header ke PHP:
> ```apache
> RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
> SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
> ```
> Ini memastikan Apache/PHP tidak memblok header `Authorization`.

---

## Implementasi — file kunci
- Token encode/decode: `application/helpers/jwt_helper.php`
  - `jwt_encode(array $payload, $key, $exp_seconds = 3600)`
  - `jwt_decode(string $jwt, $key)` → mengembalikan payload array atau `null` jika invalid/expired.
- Login / issue token: `application/controllers/Auth.php` → `login()`
  - Memanggil `jwt_encode()` setelah validasi kredensial.
  - Mengembalikan JSON `{ token, expires_in, user }` dan men-set header `X-Auth-Token` serta cookie `api_token`.
- Token verification: `application/controllers/Api.php` (constructor)
  - Membaca token dari header / server var / `X-Auth-Token` / query param; decode via `jwt_decode()`; cek revocation `Token_model->is_revoked()`; load user via `User_model->get_user(sub)`.
- Revocation list: `application/models/Token_model.php` (menyimpan `jti` ke tabel `revoked_tokens`).

---

## Perilaku akses / Policy saat ini
- Hanya endpoint `api/movies` (dan `api/users` untuk admin) yang aktif.
- API memerlukan token JWT di header (Authorization) — cookie fallback dihapus untuk endpoint API.
- Semua endpoint API memeriksa bahwa user yang terautentikasi memiliki `is_admin = 1` (project sudah diubah untuk membuat admin tunggal). Jika user bukan admin, akses akan ditolak (403).
- Routes `api/showtimes` dan `api/bookings` telah dinonaktifkan dan terkait model-file telah dihapus.

---

## Contoh penggunaan (Postman / curl)
1) Login (POST form-url-encoded)
- URL: `POST http://localhost:8074/API/API/index.php/auth/login`
- Headers: `Content-Type: application/x-www-form-urlencoded`
- Body (form): `name=Admin`, `password=admin123`

2) Panggil API movies (GET) menggunakan token yang didapat:
- Header:
  - `Authorization: Bearer <JWT_TOKEN>`
- Endpoint:
  - `GET http://localhost:8074/API/API/index.php/api/movies`

Contoh curl (Windows PowerShell menggunakan `curl.exe`):
```powershell
curl.exe -i -X POST "http://localhost:8074/API/API/index.php/auth/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data "name=Admin&password=admin123"

# lalu (copy token dari response)
curl.exe -i -H "Authorization: Bearer <JWT_TOKEN>" "http://localhost:8074/API/API/index.php/api/movies"
```

---

## Deployment & Hardening Recommendations
- Ganti `$config['jwt_key']` di `application/config/config.php` menjadi secret panjang dan acak.
- Jalankan aplikasi di HTTPS, aktifkan `Strict-Transport-Security`.
- Pastikan cookie `api_token` yang diset memiliki `Secure; HttpOnly; SameSite=Strict` bila masih digunakan untuk UI.
- Pertimbangkan menggunakan library JWT yang stabil (mis. `firebase/php-jwt`) untuk fitur tambahan (alg check, leeway, nbf, kid, dll.).
- Nonaktifkan `expose_php` / `X-Powered-By` pada server produksi.
- Tambahkan unit/integration tests untuk alur auth (login, refresh, revoked token).

---

## Catatan tambahan
- Jika Anda ingin mengizinkan user non-admin mengakses `GET /api/movies` (mis. read-only), saya bisa ubah kebijakan akses untuk memperbolehkan peran lain.
- Jika Anda ingin file dokumentasi lain (Postman collection JSON, atau README yang lebih lengkap), saya bisa buatkan.

---

Dokumentasi ini dibuat otomatis oleh asisten pengembang; bila ada hal yang ingin ditambah atau disesuaikan (bahasa, level detail, contoh), beri tahu saya.
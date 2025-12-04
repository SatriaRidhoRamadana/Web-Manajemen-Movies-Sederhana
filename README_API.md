# API Booking Tiket Film

API untuk sistem booking tiket film menggunakan CodeIgniter 3.

## Setup

1. Import skema database dari file `database_schema.sql`
2. Konfigurasi database di `application/config/database.php`
3. Jalankan aplikasi di web server (misalnya Apache dengan WAMP/XAMPP)

## Endpoint API

### Film

- `GET /api/movies` - Mendapatkan semua film
- `GET /api/movies/{id}` - Mendapatkan detail film
- `POST /api/movies` - Membuat film baru
- `PUT /api/movies/{id}` - Update film
- `DELETE /api/movies/{id}` - Hapus film

### Jadwal Tayang

- `GET /api/showtimes` - Mendapatkan semua jadwal tayang
- `GET /api/showtimes?movie_id={id}` - Mendapatkan jadwal tayang berdasarkan film
- `GET /api/showtimes/{id}` - Mendapatkan detail jadwal tayang
- `POST /api/showtimes` - Membuat jadwal tayang baru
- `PUT /api/showtimes/{id}` - Update jadwal tayang
- `DELETE /api/showtimes/{id}` - Hapus jadwal tayang

### Booking

- `GET /api/bookings` - Mendapatkan semua booking
- `GET /api/bookings?user_id={id}` - Mendapatkan booking berdasarkan user
- `GET /api/bookings/{id}` - Mendapatkan detail booking
- `POST /api/bookings` - Membuat booking baru
- `PUT /api/bookings/{id}` - Update booking
- `DELETE /api/bookings/{id}` - Batal booking

### User

- `GET /api/users` - Mendapatkan semua user
- `GET /api/users/{id}` - Mendapatkan detail user
- `POST /api/users` - Membuat user baru
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Hapus user

## Tutorial Membuat Data Baru

### 1. Membuat User Baru

**Endpoint:** `POST /api/users`

**Header:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "name": "Ahmad Rahman",
  "email": "ahmad.rahman@example.com",
  "phone": "081234567890"
}
```


**Response:**
```json
{
  "status": "success",
  "message": "User created successfully"
}
```

### 2. Membuat Movie Baru

**Endpoint:** `POST /api/movies`

**Header:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "title": "The Dark Knight",
  "description": "When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.",
  "duration": 152,
  "genre": "Action",
  "release_date": "2008-07-18",
  "poster_url": "https://example.com/dark-knight.jpg"
}
```


**Response:**
```json
{
  "status": "success",
  "message": "Movie created successfully"
}
```

### 3. Membuat Showtime Baru

**Endpoint:** `POST /api/showtimes`

**Header:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "movie_id": 1,
  "show_date": "2024-12-01",
  "show_time": "19:00:00",
  "theater": "Cinema XXI Grand Indonesia",
  "price": 75000.00
}
```

**Catatan:** `total_seats` akan otomatis di-set ke 100, dan `available_seats` juga 100.


**Response:**
```json
{
  "status": "success",
  "message": "Showtime created successfully"
}
```

### 4. Membuat Booking Baru

**Endpoint:** `POST /api/bookings`

**Header:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "user_id": 1,
  "showtime_id": 1,
  "seats_booked": 3
}
```

**Catatan:** Sistem akan otomatis menghitung `total_amount` berdasarkan harga showtime × jumlah kursi yang dipesan.


**Response:**
```json
{
  "status": "success",
  "message": "Booking created successfully"
}
```

## Contoh Request

### Membuat Booking

```json
POST /api/bookings
Content-Type: application/json

{
  "user_id": 1,
  "showtime_id": 1,
  "seats_booked": 2
}
```

### Response

```json
{
  "status": "success",
  "message": "Booking berhasil dibuat"
}
```

## Testing dengan Browser

### Menggunakan Browser untuk GET Requests

Karena browser hanya mendukung GET requests, Anda dapat mengakses endpoint berikut langsung di browser:

#### 1. Melihat Semua Film
```
http://localhost/API/index.php/api/movies
```

#### 2. Melihat Detail Film Tertentu
```
http://localhost/API/index.php/api/movies/1
```
(Ganti `1` dengan ID film yang diinginkan)

#### 3. Melihat Semua Jadwal Tayang
```
http://localhost/API/index.php/api/showtimes
```

#### 4. Melihat Jadwal Tayang Berdasarkan Film
```
http://localhost/API/index.php/api/showtimes?movie_id=1
```
(Ganti `1` dengan ID film yang diinginkan)

#### 5. Melihat Detail Jadwal Tayang
```
http://localhost/API/index.php/api/showtimes/1
```
(Ganti `1` dengan ID showtime yang diinginkan)

#### 6. Melihat Semua Booking
```
http://localhost/API/index.php/api/bookings
```

#### 7. Melihat Booking Berdasarkan User
```
http://localhost/API/index.php/api/bookings?user_id=1
```
(Ganti `1` dengan ID user yang diinginkan)

#### 8. Melihat Detail Booking
```
http://localhost/API/index.php/api/bookings/1
```
(Ganti `1` dengan ID booking yang diinginkan)

#### 9. Melihat Semua User
```
http://localhost/API/index.php/api/users
```

#### 10. Melihat Detail User
```
http://localhost/API/index.php/api/users/1
```
(Ganti `1` dengan ID user yang diinginkan)

### Catatan untuk Browser Testing:
- Browser akan menampilkan response dalam format JSON
- Untuk request POST, PUT, DELETE, Anda perlu menggunakan tools seperti Postman, cURL, atau aplikasi lain
- Pastikan server Apache/XAMPP/WAMP sudah berjalan
- URL base mungkin berbeda tergantung setup server Anda (misalnya `http://localhost:8080/API/index.php`)

## Testing dengan Postman

1. Import collection Postman atau buat request manual
2. Set base URL ke `http://localhost/API/index.php`
3. Gunakan method sesuai endpoint
4. Untuk POST/PUT, set header `Content-Type: application/json`
5. Kirim data dalam format JSON

## Skema Database

- `users`: id, name, email, phone, created_at
- `movies`: id, title, description, duration, genre, release_date, poster_url, created_at
- `showtimes`: id, movie_id, show_date, show_time, theater, total_seats, available_seats, price, created_at
- `bookings`: id, user_id, showtime_id, seats_booked, total_amount, booking_date, status
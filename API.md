# Cinema API Documentation

## Base URL
```
http://localhost:8000/api/v1/
```
(Replace with production URL when deployed)

## Authentication
Uses Laravel Sanctum (Bearer Token).

1. **Login**: `POST /login`
   ```
   {
     \"email\": \"user@example.com\",
     \"password\": \"password\"
   }
   ```
   Response: `{ \"token\": \"...\" }`

2. **Register**: `POST /register`
   Similar to login.

3. **Use token**: Add `Authorization: Bearer {token}` to protected requests.

4. **Logout**: `POST /logout` (protected)

## Public Endpoints

### Cinemas
- `GET /cinemas` - List cinemas
- `GET /cinemas/{cinema_id}` - Show cinema

### Movies
- `GET /movies` - List movies
- `GET /movies/{movie_id}` - Show movie
- `GET /movies/{movie_id}/showtimes` - Showtimes for movie

### Showtimes
- `GET /showtimes` - List showtimes
- `GET /showtimes/{showtime_id}` - Show showtime
- `GET /showtimes/{showtime_id}/seats` - Get seats
- `GET /showtimes/{showtime_id}/availability` - Seat availability

### Others
- `GET /combos` - List combos
- `GET /genres` - List genres
- `GET /genres/{genre_id}` - Show genre

## Protected Endpoints (auth:sanctum)

### User Profile
- `GET /users/me` - Get profile
- `PUT /users/me` - Update profile

### Seat Holds (pre-booking)
- `POST /showtimes/{showtime_id}/holds` - Hold seats
- `GET /showtimes/{showtime_id}/holds` - User holds
- `DELETE /holds/{hold_id}` - Release hold

### Bookings
- `POST /bookings` - Create booking
- `GET /bookings/{booking_id}` - Show booking
- `GET /users/me/bookings` - User bookings

### Payments
- `POST /bookings/{booking_id}/payment` - Process payment
- `GET /bookings/{booking_id}/payment` - Show payment

### Manager Routes (`/manager`)
Requires `manager` middleware:
- `GET|POST|PUT|DELETE /manager/staff`
- `GET|POST|PUT|DELETE /manager/rooms`
- `GET|POST|PUT|DELETE /manager/showtimes`
- `GET /manager/rooms/{room_id}/seats`
- `POST /manager/rooms/{room_id}/seats/bulk`

### Staff Routes (`/staff`)
Requires `staff` middleware:
- `GET /staff/bookings`
- `GET /staff/bookings/{booking_id}`
- `PATCH /staff/bookings/{booking_id}`

### Admin Routes (`/admin`)
Requires `admin` middleware:
- `GET|POST|PUT|DELETE /admin/movies`
- `GET|POST|PUT|DELETE /admin/genres`
- `GET|POST|PUT|DELETE /admin/managers`

## Setup for Frontend

### 1. Run API Server
```bash
cd q:/Cinema_API
php artisan serve
```

### 2. Setup CORS
Add to `bootstrap/app.php` ->withMiddleware():

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->cors([
        'paths' => ['api/*', 'sanctum/csrf-cookie'],
        'allowed_methods' => ['*'],
        'allowed_origins' => ['http://localhost:3000'], // Frontend URL
        'allowed_origins_patterns' => [],
        'allowed_headers' => ['*'],
        'exposed_headers' => [],
        'max_age' => 0,
        'supports_credentials' => false,
    ]);
})
```

Or install/publish CORS config:
```bash
composer require fruitcake/laravel-cors
php artisan vendor:publish --provider=\"Fruitcake\\Cors\\CorsServiceProvider\"
```
Edit `config/cors.php` to allow frontend origin.

### 3. Frontend Integration
- Use Axios/Fetch with baseURL.
- First login/register to get token.
- Include token in headers.
- For SPA, get CSRF cookie: GET /sanctum/csrf-cookie

## Testing
Use Postman/Insomnia:
1. Import this doc or generate collection.
2. Test public endpoints first.
3. Login, copy token, use in Bearer.

## Deployment
- Deploy to server (VPS/Heroku/Vercel).
- Set APP_URL in .env.
- Update CORS allowed_origins.
- Run migrations/seeds.

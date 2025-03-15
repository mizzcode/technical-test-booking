# PlayStation Rental Application

## Overview

A web application for PlayStation rental services that allows users to browse available consoles, select dates, make payments, The application is built with Laravel 12, Alpine.js, TailwindCss and integrates with Midtrans payment gateway.

## Features

- **Service Selection**: Choose between PS4 and PS5 consoles
- **Dynamic Date Selection**: Calendar interface to pick booking dates
- **Weekend Pricing**: Automatic price adjustment for weekend bookings (+50,000)
- **Payment Integration**: Online payments via Midtrans
- **Responsive Design**: Works on mobile and desktop devices

## Technology Stack

- **Backend**: PHP 8.x, Laravel 12.x
- **Frontend**: HTML, CSS, JavaScript, Alpine.js, Tailwind CSS
- **Database**: MySQL
- **Payment Gateway**: Midtrans

## Installation and Setup

### Prerequisites

- PHP >= 8.0
- Composer
- MySQL
- Node.js and NPM

### Installation Steps

1. Clone the repository:
   ```bash
   git clone https://github.com/mizzcode/technical-test-booking.git
   cd technical-test-booking
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JavaScript dependencies:
   ```bash
   npm install
   ```

3. Copy the environment file and configure it:
   ```bash
   cp .env.example .env
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Configure your database in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_username
   DB_PASSWORD=your_database_password
   ```

6. Configure Midtrans in the `.env` file:
   ```
   MIDTRANS_SERVER_KEY=your_server_key
   MIDTRANS_CLIENT_KEY=your_client_key
   MIDTRANS_IS_PRODUCTION=false
   MIDTRANS_SANITIZE=true
   MIDTRANS_3DS=true
   ```

7. Run the migrations:
   ```bash
   php artisan migrate --seed
   ```

8. Run storage link for image public
   ```bash
   php artisan storage:link
   ```

9.  Start the development server:
    ```bash
    composer run dev
    ```

## Database Structure

### Users Table
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name', '50');
    $table->string('email', '50')->unique();
    $table->string('phone', '50');
    $table->string('password', '100')->nullable();
    $table->timestamps();
});
```

### Services Table
```php
Schema::create('services', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->timestamps();
});
```

### Bookings Table
```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->integer('total_price');
    $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
    $table->string('transaction_id')->nullable();
    $table->json('payment_details')->nullable();
    $table->timestamps();
    $table->foreign('user_id')->references('id')->on('users');
});
```

### Schedules Table
```php
Schema::create('schedules', function (Blueprint $table) {
    $table->id();
    $table->date('date');
    $table->string('status', '50');
    $table->integer('price');
    $table->unsignedBigInteger('booking_id');
    $table->unsignedBigInteger('service_id');
    $table->timestamps();
    $table->foreign('booking_id')->references('id')->on('bookings');
    $table->foreign('service_id')->references('id')->on('services');
});
```

## Controllers and Routes

### Service Controller
Handles the display of services and available booking slots.

### Booking Controller
Manages the booking process including checkout, payment processing, and confirmation.

### Key Routes

```php
// Service Routes
Route::get('/service/{id}', [ServiceController::class, 'getById'])->name('service.byid');

// Checkout Routes
Route::post('/checkout-payment', [BookingController::class, 'checkout'])->name('checkout');
Route::get('/checkout-payment', [BookingController::class, 'checkoutPayment'])->name('checkout.payment');

// Payment Routes
Route::post('/pay-with-midtrans', [BookingController::class, 'payWithMidtrans'])->name('payment.midtrans');
Route::post('/midtrans-callback', [BookingController::class, 'handleMidtransCallback'])->name('midtrans.callback');
Route::post('/update-payment-status', [BookingController::class, 'updatePaymentStatus'])->name('payment.update-status');
Route::get('/payment/success', [BookingController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/booking/confirmation/{id}', [BookingController::class, 'bookingConfirmation'])->name('booking.confirmation');
```

## Midtrans Payment Integration

The application uses Midtrans Snap for payment processing. Key features:

1. **Server-Side Integration**:
   - Snap token generation
   - Transaction payload preparation
   - Callback handling

2. **Client-Side Integration**:
   - Snap.js for payment UI
   - Payment result handling
   - Cart clearing on successful payment

3. **Payment Status Handling**:
   - Success: Update booking status to 'paid', create schedules
   - Pending: Show waiting status
   - Error: Show error message with retry option

## User Flow

1. **Browse Services**:
   - User selects PS4 or PS5 from the service page

2. **Select Date and Time**:
   - User picks a date from calendar
   - Views available slots for the next 6 days
   - Adds desired slots to cart (with weekend pricing automatically applied)

3. **Checkout Process**:
   - User reviews cart items
   - Fills in customer details (name, phone, email)
   - Submits payment request

4. **Payment**:
   - Midtrans payment popup appears
   - User completes payment
   - System processes payment result

5. **Confirmation**:
   - Successful payment: Show booking confirmation
   - Pending payment: Show waiting message
   - Failed payment: Show error with retry option

## License

[MIT License](LICENSE) 

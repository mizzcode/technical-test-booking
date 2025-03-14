<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    return view('welcome');
});

Route::get('/service/{id}', [ServiceController::class, 'getById'])->name('service.byid');

Route::post('/checkout-payment', [BookingController::class, 'checkout'])->name('checkout');
Route::get('/checkout-payment', [BookingController::class, 'checkoutPayment'])->name('checkout.payment');

// Midtrans Payment Routes
Route::post('/pay-with-midtrans', [BookingController::class, 'payWithMidtrans'])->name('payment.midtrans');
Route::post('/midtrans-callback', [BookingController::class, 'handleMidtransCallback'])->name('midtrans.callback');
Route::post('/update-payment-status', [BookingController::class, 'updatePaymentStatus'])->name('payment.update-status');

// Payment Result Routes
Route::get('/payment/success', [BookingController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/booking/confirmation/{id}', [BookingController::class, 'bookingConfirmation'])->name('booking.confirmation');
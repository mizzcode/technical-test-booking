<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class BookingController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.sanitize');
        Config::$is3ds = config('midtrans.enable_3ds');
    }

    public function checkout(Request $request)
    {
        // Process cart items from the request
        $items = $request->input('items', []);
        $parsedItems = [];
        $totalPrice = 0;

        // Group items by service type
        $groupedItems = [];

        foreach ($items as $item) {
            $parsedItem = json_decode($item, true);
            $parsedItems[] = $parsedItem;
            $totalPrice += $parsedItem['price'];

            $service = $parsedItem['service'];
            if (!isset($groupedItems[$service])) {
                // Determine base price based on service type
                $basePrice = strpos($service, 'PS 4') !== false ? 30000 : 40000;

                $groupedItems[$service] = [
                    'items' => [],
                    'count' => 0,
                    'weekendCount' => 0,
                    'subtotal' => 0,
                    'basePrice' => $basePrice,
                    'totalWeekendSurcharge' => 0
                ];
            }

            // Check if this is a weekend booking
            $basePrice = $groupedItems[$service]['basePrice'];
            $weekendSurcharge = $parsedItem['price'] > $basePrice ? $parsedItem['price'] - $basePrice : 0;

            // Add weekend tracking
            if ($weekendSurcharge > 0) {
                $groupedItems[$service]['weekendCount']++;
                $groupedItems[$service]['totalWeekendSurcharge'] += $weekendSurcharge;
            }

            $parsedItem['weekendSurcharge'] = $weekendSurcharge;
            $parsedItem['basePrice'] = $basePrice;

            $groupedItems[$service]['items'][] = $parsedItem;
            $groupedItems[$service]['count']++;
            $groupedItems[$service]['subtotal'] += $parsedItem['price'];
        }

        session()->put('checkout_data', [
            'parsedItems' => $parsedItems,
            'groupedItems' => $groupedItems,
            'totalPrice' => $totalPrice
        ]);

        return redirect()->route('checkout.payment');
    }

    public function checkoutPayment()
    {
        // Retrieve data from session
        $checkoutData = session('checkout_data', []);
        $parsedItems = $checkoutData['parsedItems'] ?? [];
        $groupedItems = $checkoutData['groupedItems'] ?? [];
        $totalPrice = $checkoutData['totalPrice'] ?? 0;

        // If no items in session, redirect to service page
        if (empty($parsedItems)) {
            return redirect()->route('service.byid', ['id' => 1])
                ->with('error', 'Keranjang kosong. Silakan pilih jadwal booking terlebih dahulu.');
        }

        // Render the view with the data
        return view('bookings.payment', compact('parsedItems', 'groupedItems', 'totalPrice'));
    }

    public function payWithMidtrans(Request $request)
    {
        // Validate the form data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
        ]);

        // Get checkout data from session
        $checkoutData = session('checkout_data', []);
        $parsedItems = $checkoutData['parsedItems'] ?? [];
        $totalPrice = $checkoutData['totalPrice'] ?? 0;

        if (empty($parsedItems)) {
            return response()->json([
                'error' => 'Keranjang kosong. Silakan pilih jadwal booking terlebih dahulu.'
            ], 400);
        }

        // Find or create user based on email
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone']
                // You might want to set a default password or leave it null
            ]);
        }

        // Generate unique order ID
        $orderId = 'BOOKING-PS-' . time();

        // Prepare transaction details for Midtrans
        $transactionDetails = [
            'order_id' => $orderId,
            'gross_amount' => $totalPrice,
        ];

        // Prepare customer details
        $customerDetails = [
            'first_name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ];

        // Prepare item details
        $itemDetails = [];
        foreach ($parsedItems as $item) {
            $itemDetails[] = [
                'id' => $item['service_id'],
                'price' => $item['price'],
                'quantity' => 1,
                'name' => $item['service'] . ' (' . $item['date'] . ')',
            ];
        }

        // Create transaction payload
        $transaction = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails,
        ];

        try {
            // Get Snap Payment Page URL
            $snapToken = Snap::getSnapToken($transaction);

            // Create a new booking entry with pending status
            $booking = Booking::create([
                'user_id' => $user->id,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'transaction_id' => $orderId,
                'payment_details' => json_encode([
                    'items' => $parsedItems,
                    'customer' => $customerDetails
                ])
            ]);

            // Store booking ID in session for future reference
            session()->put('booking_id', $booking->id);

            Log::info(['snapToken: ' => $snapToken, 'booking_id' => $booking->id]);

            // Return the Snap Token
            return response()->json([
                'snap_token' => $snapToken,
                'booking_id' => $booking->id
            ]);
        } catch (Exception $e) {
            Log::error('Midtrans Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function handleMidtransCallback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed == $request->signature_key) {
            // Find the booking by transaction_id
            $booking = Booking::where('transaction_id', $request->order_id)->first();

            if (!$booking) {
                Log::error('Booking not found for transaction: ' . $request->order_id);
                return response()->json(['success' => false], 404);
            }

            // Update payment details
            $paymentDetails = json_decode($booking->payment_details, true);
            $paymentDetails['midtrans_response'] = $request->all();
            $booking->payment_details = json_encode($paymentDetails);

            if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
                // Payment successful - update booking status
                $booking->status = 'paid';
                $booking->save();

                // Create schedule entries for each item in the booking
                $items = json_decode($booking->payment_details, true)['items'] ?? [];

                foreach ($items as $item) {
                    Schedule::create([
                        'date' => $item['date'],
                        'status' => 'booked',
                        'price' => $item['price'],
                        'booking_id' => $booking->id,
                        'service_id' => $item['service_id']
                    ]);
                }

                return response()->json(['success' => true]);
            } elseif ($request->transaction_status == 'deny' || $request->transaction_status == 'cancel' || $request->transaction_status == 'expire') {
                // Payment failed
                $booking->status = 'cancelled';
                $booking->save();
                return response()->json(['success' => true]);
            } elseif ($request->transaction_status == 'pending') {
                // Payment pending - booking already marked as pending
                $booking->save();
                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false], 403);
    }

    public function bookingConfirmation($id)
    {
        $booking = Booking::findOrFail($id);

        // Only show confirmed bookings
        if ($booking->status !== 'paid') {
            return redirect()->route('service.byid', ['id' => 1])
                ->with('error', 'Booking belum dikonfirmasi atau telah dibatalkan.');
        }

        // Get booking details from payment_details JSON
        $bookingDetails = json_decode($booking->payment_details, true);
        $items = $bookingDetails['items'] ?? [];
        $customer = $bookingDetails['customer'] ?? [];

        // Get schedules related to this booking
        $schedules = Schedule::where('booking_id', $booking->id)->get();

        return view('bookings.confirmation', compact('booking', 'items', 'customer', 'schedules'));
    }

    public function updatePaymentStatus(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'transaction_id' => 'required|string',
            'status' => 'required|in:paid,cancelled,pending',
            'payment_details' => 'required'
        ]);

        try {
            // Find the booking
            $booking = Booking::findOrFail($validated['booking_id']);

            // Update booking status
            $booking->status = $validated['status'];

            // Update payment details
            $paymentDetails = json_decode($booking->payment_details, true) ?: [];
            $paymentDetails['client_response'] = $validated['payment_details'];
            $paymentDetails['transaction_id'] = $validated['transaction_id'];
            $booking->payment_details = json_encode($paymentDetails);

            // Save the booking
            $booking->save();

            // If payment is successful, create schedules
            if ($validated['status'] === 'paid') {
                $items = $paymentDetails['items'] ?? [];

                foreach ($items as $item) {
                    // Check if a schedule for this booking already exists to prevent duplicates
                    $existingSchedule = Schedule::where('booking_id', $booking->id)
                        ->where('service_id', $item['service_id'])
                        ->where('date', $item['date'])
                        ->first();

                    if (!$existingSchedule) {
                        Schedule::create([
                            'date' => $item['date'],
                            'status' => 'booked',
                            'price' => $item['price'],
                            'booking_id' => $booking->id,
                            'service_id' => $item['service_id']
                        ]);
                    }
                }
            }

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            Log::error('Failed to update booking status: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $bookingId = $request->input('booking_id') ?? session('booking_id');

        if (!$bookingId) {
            return redirect()->route('service.byid', ['id' => 1])
                ->with('error', 'Informasi booking tidak ditemukan.');
        }

        return redirect()->route('booking.confirmation', ['id' => $bookingId]);
    }
}
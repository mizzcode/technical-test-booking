<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
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

        $recalculatedTotalPrice = 0;

        // Group items by service type
        $groupedItems = [];

        foreach ($items as $item) {
            $parsedItem = json_decode($item, true);
            $parsedItems[] = $parsedItem;
            $totalPrice += $parsedItem['price'];

            $service = Service::find($parsedItem['service_id']);

            $calculatePrice = calculatePrice($service, $parsedItem['date']);

            $recalculatedTotalPrice += $calculatePrice;

            if ($calculatePrice !== $parsedItem['price']) {
                Log::warning('price manipulation detected', [
                    'service_name' => $service->name,
                    'correct_price' => $calculatePrice,
                    'wrong_price' => $parsedItem['price']
                ]);

                return redirect()->back()->with('error', 'price manipulation detected');
            }

            // Check if total price matches
            if ($totalPrice !== $recalculatedTotalPrice) {
                Log::warning('Total price manipulation detected', [
                    'submitted_total' => $totalPrice,
                    'correct_total' => $recalculatedTotalPrice,
                ]);

                return redirect()->back()->with('error', 'total price manipulation detected');
            }

            if (!isset($groupedItems[$service->name])) {
                $basePrice = $service->price;

                $groupedItems[$service->name] = [
                    'items' => [],
                    'count' => 0,
                    'weekendCount' => 0,
                    'subtotal' => 0,
                    'basePrice' => $basePrice,
                    'totalWeekendSurcharge' => 0
                ];
            }

            // Check if this is a weekend booking
            $basePrice = $groupedItems[$service->name]['basePrice'];
            $weekendSurcharge = $parsedItem['price'] > $basePrice ? $parsedItem['price'] - $basePrice : 0;

            // Add weekend tracking
            if ($weekendSurcharge > 0) {
                $groupedItems[$service->name]['weekendCount']++;
                $groupedItems[$service->name]['totalWeekendSurcharge'] += $weekendSurcharge;
            }

            $parsedItem['weekendSurcharge'] = $weekendSurcharge;
            $parsedItem['basePrice'] = $basePrice;

            $groupedItems[$service->name]['items'][] = $parsedItem;
            $groupedItems[$service->name]['count']++;
            $groupedItems[$service->name]['subtotal'] += $parsedItem['price'];
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
        ]);

        // Get checkout data from session
        $checkoutData = session('checkout_data', []);
        $parsedItems = $checkoutData['parsedItems'] ?? [];
        $clientTotalPrice = $checkoutData['totalPrice'] ?? 0;

        if (empty($parsedItems)) {
            return response()->json([
                'error' => 'Keranjang kosong. Silakan pilih jadwal booking terlebih dahulu.'
            ], 400);
        }

        $recalculatedTotalPrice = 0;
        $validatedItems = [];

        foreach ($parsedItems as $item) {
            $service = Service::find($item['service_id']);
            if (!$service) {
                return response()->json([
                    'error' => 'Layanan tidak valid.'
                ], 400);
            }

            // Get base price from service table
            $basePrice = $service->price;

            // Validate that the service has a valid price
            if (!$basePrice || $basePrice <= 0) {
                Log::error('Invalid service price in database', [
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'price from cart' => $item['price'],
                    'price from db' => $basePrice
                ]);

                return response()->json([
                    'error' => 'Layanan tidak memiliki harga yang valid.'
                ], 400);
            }

            $calculatePrice = calculatePrice($service, $item['date']);
            $weekendSurcharge = isWeekend($item['date'])
                ? getWeekendSurcharge()
                : 0;

            // Validate that item price matches the correct calculated price
            if ($item['price'] !== $calculatePrice) {
                Log::warning('Price manipulation detected', [
                    'user_email' => $validated['email'],
                    'service' => $service->name,
                    'date' => $item['date'],
                    'submitted_price' => $item['price'],
                    'correct_price' => $calculatePrice,
                ]);

                return response()->json([
                    'error' => 'Harga tidak valid. Silakan refresh halaman dan coba lagi.'
                ], 400);
            }

            // Add to recalculated total
            $recalculatedTotalPrice += $calculatePrice;

            // Store validated item
            $validatedItems[] = [
                'service_id' => $item['service_id'],
                'service' => $service->name,
                'date' => $item['date'],
                'price' => $calculatePrice,
                'basePrice' => $basePrice,
                'weekendSurcharge' => $weekendSurcharge
            ];
        }

        // Check if total price matches
        if ($clientTotalPrice !== $recalculatedTotalPrice) {
            Log::warning('Total price manipulation detected', [
                'user_email' => $validated['email'],
                'submitted_total' => $clientTotalPrice,
                'correct_total' => $recalculatedTotalPrice,
                'wrong_total' => $clientTotalPrice
            ]);

            return response()->json([
                'error' => 'Total harga tidak valid. Silakan refresh halaman dan coba lagi.'
            ], 400);
        }

        $user = User::where('email', $validated['email'])->first();

        $fullName = $validated['first_name'] . ' ' . ($validated['last_name'] ?? '');

        if (!$user) {
            $user = User::create([
                'name' => trim($fullName),
                'email' => $validated['email'],
                'phone' => $validated['phone']
            ]);
        }

        // Generate unique order ID
        $orderId = 'BOOKING-PS-' . time();

        // Prepare transaction details for Midtrans using validated prices
        $transactionDetails = [
            'order_id' => $orderId,
            'gross_amount' => $recalculatedTotalPrice,
        ];

        // Prepare customer details
        $customerDetails = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ];

        // Prepare item details
        $itemDetails = [];
        foreach ($validatedItems as $item) {
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
                'total_price' => $recalculatedTotalPrice,
                'status' => 'pending',
                'transaction_id' => $orderId,
                'payment_details' => json_encode([
                    'items' => $validatedItems,
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

    public function bookingHistory(Request $request)
    {
        // Get search parameters
        $email = $request->input('email');
        $transactionId = $request->input('transaction_id');

        // Initialize bookings as empty collection by default
        $bookings = collect([]);

        // Only perform query if search parameters are provided
        if ($email || $transactionId) {
            $query = Booking::with('user');

            // Filter by email if provided
            if ($email) {
                $query->whereHas('user', function ($q) use ($email) {
                    $q->where('email', 'like', '%' . $email . '%');
                });
            }

            // Filter by transaction ID if provided
            if ($transactionId) {
                $query->where('transaction_id', 'like', '%' . $transactionId . '%');
            }

            // Get bookings and paginate
            $bookings = $query->latest()->paginate(10);
        } else {
            // Provide empty paginator when no search is performed
            $bookings = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                10,
                1,
                [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                ]
            );
        }

        return view('bookings.history', compact('bookings', 'email', 'transactionId'));
    }

    public function resumePayment($id)
    {
        // Find the booking
        $booking = Booking::findOrFail($id);

        // Only proceed if booking is pending
        if ($booking->status !== 'pending') {
            return redirect()->route('booking.history')
                ->with('error', 'Pembayaran hanya dapat dilanjutkan untuk booking dengan status PENDING.');
        }

        // Get booking details
        $bookingDetails = json_decode($booking->payment_details, true);
        $items = $bookingDetails['items'] ?? [];
        $customer = $bookingDetails['customer'] ?? [];

        $recalculatedTotal = 0;
        $validatedItems = [];

        foreach ($items as $item) {
            Log::info(['item pada resume payment: ' => $item]);
            $service = Service::find($item['service_id']);
            if (!$service) {
                return redirect()->route('booking.history')
                    ->with('error', 'Layanan tidak valid dalam booking ini.');
            }

            // Get base price from service table
            $basePrice = $service->price;

            // Validate that the service has a valid price
            if (!$basePrice || $basePrice <= 0) {
                Log::error('Invalid service price in database', [
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'price' => $basePrice,
                    'wrong_price' => $item['price']
                ]);

                return redirect()->route('booking.history')
                    ->with('error', 'Layanan tidak memiliki harga yang valid.');
            }

            // Use PriceHelper to calculate the correct price
            $calculatePrice = calculatePrice($service, $item['date']);
            $weekendSurcharge = isWeekend($item['date'])
                ? getWeekendSurcharge()
                : 0;

            // Validate that item price matches the correct calculated price
            if ($item['price'] !== $calculatePrice) {
                Log::warning('Price manipulation detected in resume payment', [
                    'booking_id' => $booking->id,
                    'service' => $service->name,
                    'date' => $item['date'],
                    'correct_price' => $calculatePrice,
                    'wrong_price' => $item['price']
                ]);

                return redirect()->route('booking.history')
                    ->with('error', 'Harga dalam booking tidak valid. Silakan hubungi admin.');
            }

            // Add to recalculated total
            $recalculatedTotal += $calculatePrice;

            // Save validated item
            $validatedItems[] = [
                'id' => $item['service_id'],
                'price' => $calculatePrice,
                'quantity' => 1,
                'name' => $service->name . ' (' . $item['date'] . ')',
            ];
        }

        // Check if booking total price matches recalculated total
        if ($booking->total_price !== $recalculatedTotal) {
            Log::warning('Total price manipulation detected in resume payment', [
                'booking_id' => $booking->id,
                'stored_total' => $booking->total_price,
                'correct_total' => $recalculatedTotal
            ]);

            return redirect()->route('booking.history')
                ->with('error', 'Total harga dalam booking tidak valid. Silakan hubungi admin.');
        }

        // Prepare transaction details for Midtrans
        $transactionDetails = [
            'order_id' => $booking->transaction_id,
            'gross_amount' => $recalculatedTotal,
        ];

        // Prepare customer details
        $customerDetails = [
            'first_name' => $customer['first_name'] ?? '',
            'last_name' => $customer['last_name'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone' => $customer['phone'] ?? '',
        ];

        // Create transaction payload
        $transaction = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $validatedItems,
        ];

        try {
            // Get Snap Payment Page URL
            $snapToken = Snap::getSnapToken($transaction);

            // Store booking ID in session for future reference
            session()->put('booking_id', $booking->id);

            // Return view with Snap token
            return view('bookings.resume-payment', compact('booking', 'snapToken', 'customer', 'items'));
        } catch (Exception $e) {
            Log::error('Midtrans Error: ' . $e->getMessage());
            return redirect()->route('booking.history')
                ->with('error', 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage());
        }
    }
}
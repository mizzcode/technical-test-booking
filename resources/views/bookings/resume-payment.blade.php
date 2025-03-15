@extends('layouts.app')

@section('title', 'Lanjutkan Pembayaran')

@section('header')
    @include('components.navbar')
    <img class="fixed top-16 z-10 shadow-lg w-full" src="{{ asset('storage/stick.png') }}" alt="stick">
@endsection

@section('content')
    <div class="mx-5 max-w-7xl sm:mx-auto max-w-max-w-6.5xl mt-24 md:mt-32">
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-4">Lanjutkan Pembayaran</h1>
            <p class="mb-6">Silakan klik tombol di bawah untuk melanjutkan proses pembayaran.</p>

            <div class="mb-6">
                <h2 class="font-bold text-lg mb-2">Detail Booking</h2>
                <p><span class="font-medium">ID Transaksi:</span> {{ $booking->transaction_id }}</p>
                <p><span class="font-medium">Total:</span> Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                <p><span class="font-medium">Tanggal Booking:</span> {{ $booking->created_at->format('d M Y, H:i') }} WIB
                </p>
            </div>

            <div class="flex justify-center">
                <button id="payNow"
                    class="bg-my-red text-white font-bold py-2 px-6 rounded-lg hover:bg-red-800 cursor-pointer">
                    Bayar Sekarang
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Load Midtrans Snap JS library -->
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('midtrans.client_key') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const payButton = document.getElementById('payNow');
            const snapToken = "{{ $snapToken }}";
            const bookingId = "{{ $booking->id }}";

            payButton.addEventListener('click', function() {
                payButton.disabled = true;
                payButton.textContent = 'Memproses...';

                // Get the CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Open Snap payment page
                snap.pay(snapToken, {
                    onSuccess: function(result) {
                      console.log('Payment success:', result);
                      localStorage.removeItem('cart');

                        // Make API call to update booking status
                        fetch('{{ route('payment.update-status') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    booking_id: bookingId,
                                    transaction_id: result.transaction_id || result
                                        .order_id,
                                    status: 'paid',
                                    payment_details: result
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.href =
                                        '{{ route('booking.confirmation', ['id' => $booking->id]) }}';
                                } else {
                                    alert(
                                        'Pembayaran berhasil, tetapi gagal memperbarui status. Mohon hubungi admin.'
                                        );
                                    window.location.href =
                                        '{{ route('booking.confirmation', ['id' => $booking->id]) }}';
                                }
                            })
                            .catch(error => {
                                console.error('Error updating booking:', error);
                                window.location.href =
                                    '{{ route('booking.confirmation', ['id' => $booking->id]) }}';
                            });
                    },
                    onPending: function(result) {
                        console.log('Payment pending:', result);
                        alert('Pembayaran sedang diproses. Mohon tunggu konfirmasi dari bank.');
                        window.location.href =
                            '{{ route('booking.confirmation', ['id' => $booking->id]) }}';
                    },
                    onError: function(result) {
                        console.error('Payment Error:', result);
                        alert('Pembayaran gagal. Silakan coba lagi.');
                        payButton.disabled = false;
                        payButton.textContent = 'Bayar Sekarang';
                    },
                    onClose: function() {
                        payButton.disabled = false;
                        payButton.textContent = 'Bayar Sekarang';
                        alert('Pembayaran dibatalkan. Silakan coba lagi ketika Anda siap.');
                    }
                });
            });
        });
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Pembayaran Online')

@section('header')
    @include('components.navbar')
    <img class="fixed top-16 z-10 shadow-lg w-full" src="{{ asset('storage/stick.png') }}" alt="stick">
@endsection

@section('content')
    @if (session('error'))
        @include('components.alert-error')
    @endif
    <div class="mx-5 max-w-7xl sm:mx-auto max-w-max-w-6.5xl mt-24 md:mt-32">
        <div class="flex flex-col md:flex-row md:justify-between md:space-x-5 space-y-5 md:space-y-0 mb-12">
            <div class="md:h-[35rem] md:w-7/12 ">
                <div class="bg-my-grey py-6 px-10 rounded-xl">
                    <h1 class="font-bold text-lg mb-3">Customer Details</h1>
                    <form id="paymentForm" class="border-t border-white">
                        @csrf
                        <div class="space-y-6 border-gray-100 mt-5">
                            <!-- Name fields in two columns -->
                            <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row md:justify-between md:space-x-3">
                                <div class="w-full">
                                    <label class="mb-1.5 block text-sm font-medium">
                                        First Name
                                    </label>
                                    <input type="text" placeholder="Masukan Nama Depan" name="first_name"
                                        class="bg-white shadow-theme-xs h-11 w-full rounded-lg border-my-red border-t-2 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden" />
                                    @error('first_name')
                                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="w-full">
                                    <label class="mb-1.5 block text-sm font-medium">
                                        Last Name
                                    </label>
                                    <input type="text" placeholder="Masukan Nama Belakang" name="last_name"
                                        class="bg-white shadow-theme-xs h-11 w-full rounded-lg border-my-red border-t-2 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden" />
                                    @error('last_name')
                                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex flex-col space-y-6 md:space-y-0 md:flex-row md:justify-between md:space-x-3">
                                <!-- Phone field -->
                                <div class="w-full">
                                    <label class="mb-1.5 block text-sm font-medium">
                                        Phone
                                    </label>
                                    <div x-data="{
                                        selectedCountry: 'ID',
                                        countryCodes: {
                                            'ID': '+62',
                                        },
                                        phoneNumber: '62'
                                    }" class="relative">
                                        <div class="absolute">
                                            <select x-model="selectedCountry"
                                                @change="phoneNumber = countryCodes[selectedCountry]"
                                                class="appearance-none rounded-l-lg border-0 border-r border-gray-200 bg-none py-3 pr-8 pl-3.5 leading-tight focus:ring-3 focus:outline-hidden flex items-center">
                                                <option value="ID" class="flex items-center">
                                                    ID
                                                </option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                                <svg class="stroke-current" width="20" height="20"
                                                    viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke=""
                                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </div>
                                        </div>
                                        <input placeholder="62" x-model="phoneNumber" type="tel" name="phone"
                                            class="bg-white shadow-theme-xs h-11 w-full rounded-lg border-my-red border-t-2 py-3 pr-4 pl-[84px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden" />
                                        @error('phone')
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Email field -->
                                <div class="w-full">
                                    <label class="mb-1.5 block text-sm font-medium">
                                        Email
                                    </label>
                                    <div class="relative">
                                        <span
                                            class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M3.04175 7.06206V14.375C3.04175 14.6511 3.26561 14.875 3.54175 14.875H16.4584C16.7346 14.875 16.9584 14.6511 16.9584 14.375V7.06245L11.1443 11.1168C10.457 11.5961 9.54373 11.5961 8.85638 11.1168L3.04175 7.06206ZM16.9584 5.19262C16.9584 5.19341 16.9584 5.1942 16.9584 5.19498V5.20026C16.9572 5.22216 16.946 5.24239 16.9279 5.25501L10.2864 9.88638C10.1145 10.0062 9.8862 10.0062 9.71437 9.88638L3.07255 5.25485C3.05342 5.24151 3.04202 5.21967 3.04202 5.19636C3.042 5.15695 3.07394 5.125 3.11335 5.125H16.8871C16.9253 5.125 16.9564 5.15494 16.9584 5.19262ZM18.4584 5.21428V14.375C18.4584 15.4796 17.563 16.375 16.4584 16.375H3.54175C2.43718 16.375 1.54175 15.4796 1.54175 14.375V5.19498C1.54175 5.1852 1.54194 5.17546 1.54231 5.16577C1.55858 4.31209 2.25571 3.625 3.11335 3.625H16.8871C17.7549 3.625 18.4584 4.32843 18.4585 5.19622C18.4585 5.20225 18.4585 5.20826 18.4584 5.21428Z"
                                                    fill="#667085" />
                                            </svg>
                                        </span>
                                        <input type="text" placeholder="booking-ps@gmail.com" name="email"
                                            class="border-my-red border-t-2 bg-white shadow-theme-xs h-11 w-full rounded-lg px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden" />
                                        @error('email')
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="md:w-5/12 mt-5 md:mt-0" x-data="{
                get items() {
                    return $store.cart.items || [];
                },
            
                // Group items by service
                get groupedItems() {
                    const groups = {};
                    this.items.forEach(item => {
                        const basePrice = item.service.includes('PS 4') ? 30000 : 40000;
                        const weekendSurcharge = item.price > basePrice ? item.price - basePrice : 0;
            
                        if (!groups[item.service]) {
                            groups[item.service] = {
                                items: [],
                                count: 0,
                                weekendCount: 0,
                                subtotal: 0,
                                basePrice: basePrice,
                                basePriceTotal: 0,
                                totalWeekendSurcharge: 0
                            };
                        }
                        groups[item.service].items.push({
                            ...item,
                            basePrice: basePrice,
                            weekendSurcharge: weekendSurcharge
                        });
                        groups[item.service].count++;
                        groups[item.service].subtotal += item.price;
                        groups[item.service].basePriceTotal += basePrice;
            
                        if (weekendSurcharge > 0) {
                            groups[item.service].weekendCount++;
                            groups[item.service].totalWeekendSurcharge += weekendSurcharge;
                        }
                    });
                    return groups;
                },
            
                // Get total price of all items
                get totalPrice() {
                    return this.items.reduce((sum, item) => sum + item.price, 0);
                },
            
                // Format price in IDR
                formatPrice(price) {
                    return $store.cart.formatPrice(price);
                },
            
                getCartCount() {
                    return $store.cart.getCartCount();
                }
            }">
                <div class="h-max py-6 px-10 shadow-xl rounded-lg border-my-red border-t-2">
                    <h1 class="font-bold text-lg mb-3">Rincian Biaya</h1>
                    <div class="border-t border-gray-300 mb-3"></div>

                    <template x-for="(group, service) in groupedItems" :key="service">
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between font-semibold">
                                <p>Layanan</p>
                                <p x-text="service"></p>
                            </div>
                            <div class="flex justify-between">
                                <p>Biaya Rental</p>
                                <p x-text="formatPrice(group.basePrice) + ' × ' + group.count">
                                </p>
                            </div>
                            <template x-if="group.totalWeekendSurcharge > 0">
                                <div class="flex justify-between">
                                    <p>Biaya tambahan weekend</p>
                                    <p x-text="formatPrice(50000) + ' × ' + group.weekendCount">
                                    </p>
                                </div>
                            </template>
                            <div class="flex justify-between">
                                <p>Subtotal</p>
                                <p x-text="formatPrice(group.subtotal)"></p>
                            </div>
                            <div class="border-b border-gray-300 my-3"></div>
                        </div>
                    </template>

                    <div class="space-y-3">
                        <div class="flex justify-between font-semibold">
                            <p>Total Booking</p>
                            <p x-text="getCartCount()"></p>
                        </div>
                        <div class="flex justify-between font-bold text-lg">
                            <p>Total Bayar</p>
                            <p x-text="formatPrice(totalPrice)"></p>
                        </div>
                    </div>
                </div>

                <p class="text-sm my-6">Dengan mengklik tombol berikut, Anda menyetujui <a href="#"
                        class="text-my-red">Syarat dan Ketentuan</a> serta
                    <a href="#" class="text-my-red">Kebijakan privasi.</a>
                </p>
                <button type="button" id="payNow"
                    class="w-full bg-my-red font-bold text-xl text-white py-2 rounded-lg hover:bg-red-800 cursor-pointer">
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
            let payButton = document.getElementById('payNow');
            let paymentForm = document.getElementById('paymentForm');
            let contentArea = document.querySelector('.mx-5.max-w-7xl');

            payButton.addEventListener('click', function() {
                const firstName = document.querySelector('input[name="first_name"]').value;
                const lastName = document.querySelector('input[name="last_name"]').value;
                const phone = document.querySelector('input[name="phone"]').value;
                const email = document.querySelector('input[name="email"]').value;

                if (!firstName || !phone || !email) {
                    alert('Silakan lengkapi data customer.');
                    return;
                }

                payButton.disabled = true;
                payButton.textContent = 'Memproses...';

                // Get the CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content') ||
                    document.querySelector('input[name="_token"]').value;

                // Send AJAX request to get Midtrans token
                fetch('{{ route('payment.midtrans') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            first_name: firstName,
                            last_name: lastName || '',
                            phone: phone,
                            email: email
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            payButton.disabled = false;
                            payButton.textContent = 'Bayar Sekarang';
                            return;
                        }

                        if (data.snap_token) {
                            const bookingId = data.booking_id;

                            // Open Snap payment page with received token
                            snap.pay(data.snap_token, {
                                onSuccess: function(result) {
                                    localStorage.removeItem('cart');
                                    console.log('Payment success:', result);

                                    // Make API call to update booking status and create schedules
                                    fetch('{{ route('payment.update-status') }}', {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': csrfToken,
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                booking_id: bookingId,
                                                transaction_id: result
                                                    .transaction_id || result
                                                    .order_id,
                                                status: 'paid',
                                                payment_details: result
                                            })
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                window.location.href =
                                                    '{{ route('payment.success') }}?booking_id=' +
                                                    bookingId;
                                            } else {
                                                alert(
                                                    'Pembayaran berhasil, tetapi gagal memperbarui status. Mohon hubungi admin.'
                                                );
                                                window.location.href =
                                                    '{{ route('payment.success') }}?booking_id=' +
                                                    bookingId;
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error updating booking:',
                                                error);
                                            window.location.href =
                                                '{{ route('payment.success') }}?booking_id=' +
                                                bookingId;
                                        });
                                },
                                onPending: function(result) {
                                    console.log('Payment pending:', result);

                                    // Show pending message
                                    contentArea.innerHTML = `
                                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 mt-24 md:mt-32">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-7a1 1 0 00-1 1v2a1 1 0 102 0v-2a1 1 0 00-1-1zm0-4a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm text-yellow-700">
                                                        Pembayaran sedang diproses. Mohon tunggu konfirmasi dari bank.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <h1 class="text-2xl font-bold mb-4">Pembayaran Sedang Diproses</h1>
                                            <p class="mb-6">Terima kasih telah melakukan pemesanan. Pembayaran Anda sedang diproses oleh bank.</p>
                                            <p class="mb-6">ID Transaksi: ${result.transaction_id || result.order_id}</p>
                                            <a href="{{ route('service.byid', ['id' => 1]) }}" class="bg-my-red text-white font-bold py-2 px-6 rounded-lg hover:bg-red-800 inline-block">
                                                Kembali ke Layanan
                                            </a>
                                        </div>
                                    `;
                                },
                                onError: function(result) {
                                    // Payment error - show error message in the current view
                                    console.error('Payment Error:', result);

                                    // Show error message
                                    contentArea.innerHTML = `
                                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 mt-24 md:mt-32">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm1-9a1 1 0 00-1 1v4a1 1 0 102 0V5a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm text-red-700">
                                                        Pembayaran gagal. Silakan coba lagi.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <h1 class="text-2xl font-bold mb-4">Pembayaran Gagal</h1>
                                            <p class="mb-6">Terjadi kesalahan saat memproses pembayaran Anda.</p>
                                            <a href="{{ route('checkout.payment') }}" class="bg-gray-500 text-white font-bold py-2 px-6 rounded-lg hover:bg-gray-700 inline-block mr-3">
                                                Coba Lagi
                                            </a>
                                            <a href="{{ route('service.byid', ['id' => 1]) }}" class="bg-my-red text-white font-bold py-2 px-6 rounded-lg hover:bg-red-800 inline-block">
                                                Kembali ke Layanan
                                            </a>
                                        </div>
                                    `;
                                },
                                onClose: function() {
                                    // Customer closed the popup without finishing the payment
                                    payButton.disabled = false;
                                    payButton.textContent = 'Bayar Sekarang';

                                    // Optionally show a message
                                    alert(
                                        'Pembayaran dibatalkan. Silakan coba lagi ketika Anda siap.'
                                    );
                                }
                            });
                        } else {
                            alert('Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.');
                            payButton.disabled = false;
                            payButton.textContent = 'Bayar Sekarang';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.');
                        payButton.disabled = false;
                        payButton.textContent = 'Bayar Sekarang';
                    });
            });
        });
    </script>
@endpush

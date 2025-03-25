@extends('layouts.app')

@section('title', 'Konfirmasi Booking')

@section('header')
    @include('components.navbar')
    <img class="fixed top-16 z-10 shadow-lg w-full" src="{{ asset('storage/stick.png') }}" alt="stick">
@endsection

@section('content')
    @if (session('error'))
        @include('components.alert-error')
    @endif

    <div class="mx-5 max-w-7xl sm:mx-auto max-w-max-w-6.5xl mt-24 md:mt-32">
        @if ($booking->status === 'paid')
            @include('components.alert-success', [
                'message' => 'Pembayaran berhasil! Booking anda telah dikonfirmasi.',
            ])
        @endif
        <div class="flex flex-col md:flex-row md:justify-between md:space-x-5 space-y-5 md:space-y-0 mb-12">
            <div class="md:w-1/2 bg-white shadow-xl rounded-lg p-6 border-t-2 border-my-red">
                <h1 class="font-bold text-xl mb-4">Detail Booking</h1>
                <div class="border-t border-gray-300 mb-4"></div>

                <div class="mb-4">
                    <h2 class="font-semibold mb-2">Informasi Pelanggan</h2>
                    <p><span class="font-medium">Nama:</span> {{ $customer['first_name'] ?? 'N/A' }}
                        {{ $customer['last_name'] ?? 'N/A' }}</p>
                    <p><span class="font-medium">Email:</span> {{ $customer['email'] ?? 'N/A' }}</p>
                    <p><span class="font-medium">Telepon:</span> {{ $customer['phone'] ?? 'N/A' }}</p>
                </div>

                <div class="mb-4">
                    <h2 class="font-semibold mb-2">Detail Transaksi</h2>
                    <p><span class="font-medium">ID Booking:</span> {{ $booking->id }}</p>
                    <p><span class="font-medium">ID Transaksi:</span> {{ $booking->transaction_id }}</p>
                    <p><span class="font-medium">Status:</span>
                        @if ($booking->status == 'paid')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">LUNAS</span>
                        @elseif($booking->status == 'pending')
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">MENUNGGU PEMBAYARAN</span>
                        @else
                            <span
                                class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">{{ strtoupper($booking->status) }}</span>
                        @endif
                    </p>
                    <p><span class="font-medium">Tanggal Booking: {{ $booking->created_at->format('d M Y, H:i') }}
                            WIB</span></p>
                </div>
            </div>

            <div class="md:w-1/2 bg-white shadow-xl rounded-lg p-6 border-t-2 border-my-red">
                <h1 class="font-bold text-xl mb-4">Ringkasan Pemesanan</h1>
                <div class="border-t border-gray-300 mb-4"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b text-left">Layanan</th>
                                <th class="py-2 px-4 border-b text-left">Tanggal</th>
                                <th class="py-2 px-4 border-b text-right">Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="py-2 px-4 border-b">{{ $item['service'] }}</td>
                                    <td class="py-2 px-4 border-b">{{ $item['date'] }}</td>
                                    <td class="py-2 px-4 border-b text-right">Rp
                                        {{ number_format($item['price'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="py-2 px-4 border-b font-bold" colspan="2">Total</td>
                                <td class="py-2 px-4 border-b text-right font-bold">Rp
                                    {{ number_format($booking->total_price, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center mb-8">
            <a href="{{ route('service.byid', ['id' => 1]) }}"
                class="bg-my-red text-white font-bold py-2 px-6 rounded-lg hover:bg-red-800 inline-block">
                Kembali ke Layanan
            </a>
        </div>
    </div>
    </div>
@endsection

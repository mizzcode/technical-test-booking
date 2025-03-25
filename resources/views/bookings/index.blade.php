@extends('layouts.app')

@section('title')
    Booking Rental PS
@endsection

@section('header')
    @include('components.navbar')
    <img class="fixed top-16 z-10 shadow-lg w-full" src="{{ asset('storage/stick.png') }}" alt="stick" class="w-full">
@endsection

@section('content')
    @if (session('error'))
        @include('components.alert-error')
    @endif

    <script>
        // Pre-parse the JSON on the server side to avoid JS parsing issues
        const statusData = @json($status);
    </script>

    <div class="mx-5 mt-24 sm:mx-auto sm:max-w-6xl sm:mt-28">
        <div x-data="{
            date: '{{ date('Y-m-d') }}',
            basePrice: Number('{{ $basePrice }}'),
            bookedDates: statusData,
            formatPrice(price) {
                return 'Rp ' + price.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            },
            openDatePicker() {
                this.$refs.dateInput.showPicker();
            },
            formatDate(date, format = 'default') {
                if (!date) return 'Pilih Tanggal';
                const [year, month, day] = date.split('-');
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const dateObj = new Date(year, month - 1, day);
                if (format === 'schedule') {
                    return `${day} ${months[parseInt(month) - 1]}`;
                }
                if (format === 'day') {
                    return days[dateObj.getDay()];
                }
                return `${day}-${month}-${year}`;
            },
            getNextDates(date, count) {
                const dates = [];
                const [year, month, day] = date.split('-');
                const startDate = new Date(year, month - 1, day);
                for (let i = 1; i <= count; i++) {
                    const nextDate = new Date(startDate);
                    nextDate.setDate(startDate.getDate() + i);
                    const formattedDate = nextDate.toISOString().split('T')[0];
                    dates.push(formattedDate);
                }
                return dates;
            },
            calculatePrice(date) {
                const dateObj = new Date(date);
                const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6; // 0 is Sunday, 6 is Saturday
                const weekendSurcharge = isWeekend ? 50000 : 0;
                return this.basePrice + weekendSurcharge;
            },
            isWeekend(date) {
                const dateObj = new Date(date);
                return dateObj.getDay() === 0 || dateObj.getDay() === 6;
            },
            isBooked(date) {
                return this.bookedDates[date] === 'booked';
            }
        }">
            <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 sm:gap-32 2xl:gap-52">
                <div>
                    <h1 class="mb-5 font-bold text-lg">Pilih Tanggal Booking :</h1>
                    <div class="relative flex items-center justify-between space-x-2 bg-white rounded-xl px-4 cursor-pointer shadow-lg outline"
                        @click="openDatePicker()">
                        <span class="font-bold mx-auto" x-text="formatDate(date)"></span>
                        <img src="{{ asset('storage/calendar.png') }}" alt="calendar">
                        <input type="date" x-ref="dateInput" x-model="date"
                            class="absolute inset-0 w-full h-full opacity-0 pointer-events-none">
                    </div>
                    <div class="mt-8 text-sm">
                        <p>*Periode <span class="text-my-red">Tanggal Booking</span> :</p>
                        <p>*Tap pada kolom waktu untuk memilih <span class="text-my-red">Jam Booking</span></p>
                    </div>
                </div>
                <div>
                    <h1 class="mb-5 font-bold text-lg">Pilih Layanan :</h1>
                    <div x-data="{ selectedService: '{{ $service->name }}', selectedServiceId: '{{ $service->id }}' }"
                        class="relative flex items-center justify-between py-1.5 space-x-2 bg-white rounded-xl px-4 cursor-pointer shadow-lg outline">
                        <span class="font-bold mx-auto" x-text="selectedService"></span>
                        <img src="{{ asset('storage/chevron-down.png') }}" alt="chevron">
                        <select x-model="selectedServiceId"
                            @change="
                                selectedField = Array.from($event.target.selectedOptions).find(option => option.value == selectedServiceId).text;
                                window.location.href = '{{ route('service.byid', '') }}/' + selectedServiceId;
                            "
                            class="absolute inset-0 opacity-0 cursor-pointer">
                            @foreach ($services as $s)
                                <option value="{{ $s->id }}" {{ $s->id === $service->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <div class="grid grid-cols-6 gap-4 mt-8 min-w-max">
                    <template x-for="nextDate in getNextDates(date, 6)" :key="nextDate">
                        <div class="border border-black rounded-lg bg-[#FFF6F6] py-6 px-7 sm:px-9.9 flex flex-col">
                            <p class="font-bold text-lg text-center" x-text="formatDate(nextDate, 'schedule')"></p>
                            <p class="text-sm text-my-red font-semibold text-center" x-text="formatDate(nextDate, 'day')">
                            </p>
                        </div>
                    </template>
                </div>
                <div class="grid grid-cols-6 gap-4 mt-8 min-w-max">
                    <template x-for="nextDate in getNextDates(date, 6)" :key="nextDate">
                        <div @click="!isBooked(nextDate) && $store.cart.addToCart({
                                service_id: Number('{{ $service->id }}'),
                                service: '{{ $service->name }}',
                                date: nextDate,
                                price: calculatePrice(nextDate),
                                status: 'available',
                            })"
                            class="border border-black rounded-lg p-4 flex flex-col"
                            :class="isBooked(nextDate) ? 'bg-[#D1BBBB] cursor-not-allowed' :
                                $store.cart.isInCart(nextDate, '{{ $service->name }}') ?
                                'bg-green-500 cursor-pointer text-white' : 'bg-[#FFF6F6] cursor-pointer'">
                            <p class="text-sm font-semibold text-end mb-3" x-text="formatDate(nextDate, 'schedule')">
                            </p>
                            <p class="text-lg font-semibold text-center" x-text="formatPrice(calculatePrice(nextDate))"></p>
                            <p class="text-sm font-semibold text-center"
                                :class="isBooked(nextDate) ? 'text-black' : $store.cart
                                    .isInCart(nextDate, '{{ $service->name }}') ? 'text-white' :
                                    'text-my-red'"
                                x-text="isBooked(nextDate) ? 'Booked' : 'Available'">
                            </p>
                            <div x-show="isWeekend(nextDate)" class="text-xs text-center text-my-red mt-1"
                                :class="isBooked(nextDate) ? 'text-black' : $store.cart
                                    .isInCart(nextDate, '{{ $service->name }}') ? 'text-white' :
                                    'text-my-red'">
                                *Weekend +50.000
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

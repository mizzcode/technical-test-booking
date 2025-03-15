<nav class="bg-white fixed top-0 z-50 w-full">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-10">
        <div class="flex h-16 items-center justify-between" x-data="{ menu: $store.menu }">
            <div class="flex items-center">
                <span class="font-bold"><a href="{{ route('service.byid', ['id' => 1]) }}">Booking PS</a></span>
            </div>
            <div class="flex md:hidden gap-x-5">
                <div class="relative cursor-pointer cart">
                    <img class="w-10" src="{{ asset('storage/shopping-cart.png') }}" alt="cart">
                    <span class="absolute -top-3 right-0 font-semibold text-my-red"
                        x-text="$store.cart.getCartCount()"></span>
                </div>
            </div>
            <div class="hidden md:block">
                <div class="ml-4 flex items-center md:ml-6 font-bold space-x-6">
                    <span class="font-bold"><a href="{{ route('booking.history') }}">History</a></span>
                    <div class="relative cursor-pointer cart">
                        <img class="w-10" src="{{ asset('storage/shopping-cart.png') }}" alt="cart">
                        <span class="absolute -top-3 right-0 font-semibold text-my-red"
                            x-text="$store.cart.getCartCount()"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="fixed inset-0 z-50 bg-opacity-50 hidden justify-end items-center sidebar" x-data="{ cart: $store.cart }">
    <div class="bg-white rounded-2xl h-full p-5 w-4/5 md:w-2/3 lg:w-1/2  overflow-y-auto">
        <div class="flex justify-between">
            <h1 class="font-bold text-2xl mb-5">Cart</h1>
            <img src="{{ asset('storage/close.png') }}" id="closeSidebar" class="w-8 h-8 cursor-pointer"
                alt="close button">
        </div>

        <form id="checkoutPayment" action="{{ route('checkout') }}" method="POST">
            @csrf
            <template x-for="(item, index) in cart.items" :key="index">
                <div class="mb-4 border border-gray-500 rounded-lg p-4">
                    <div class="flex justify-between items-center" x-data="{ cart: $store.cart }">
                        <p class="font-semibold" x-text="cart.formatDate(item.date)"></p>
                        <img src="{{ asset('storage/close.png') }}" @click="cart.removeFromCart(index)"
                            class="w-8 h-8 cursor-pointer" alt="close button">
                    </div>
                    <p x-text="item.service"></p>
                    <p x-text="cart.formatPrice(item.price)"></p>
                    <input type="hidden" name="items[]" :value="JSON.stringify(item)">
                </div>
            </template>
            <button type="submit"
                class="w-full bg-my-red font-bold text-xl text-white py-2 rounded-lg hover:bg-red-800 cursor-pointer mt-3">
                Lanjutkan Pembayaran
            </button>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cart = document.getElementsByClassName('cart');

            for (let i = 0; i < cart.length; i++) {
                cart[i].addEventListener('click', function() {
                    document.querySelector('.sidebar').classList.remove('hidden');
                    document.querySelector('.sidebar').classList.add('flex');
                });
            }

            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('sidebar')) {
                    document.querySelector('.sidebar').classList.remove('flex');
                    document.querySelector('.sidebar').classList.add('hidden');
                }
            });

            document.getElementById('closeSidebar').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.remove('flex');
                document.querySelector('.sidebar').classList.add('hidden');
            })

            document.getElementById('checkoutPayment').addEventListener('submit', function(e) {
                const cart = JSON.parse(localStorage.getItem('cart'))

                if (cart.length == 0) {
                    e.preventDefault()
                    alert('cart masih kosong')
                    return
                }
            });
        });
    </script>
@endpush

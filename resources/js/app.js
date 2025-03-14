import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine

Alpine.store('cart', {
    items: JSON.parse(localStorage.getItem('cart')) || [],

    addToCart(data) {
        console.log(data)
        if (data.status === 'booked') {
            alert('Slot sudah terisi.');
            return;
        }
        if (this.items.some(item => item.date === data.date && item.service === data.service)) {
            this.items = this.items.filter(item => item.date !== data.date || item.service !== data.service);
        } else {
            this.items.push(data);
        }
        localStorage.setItem('cart', JSON.stringify(this.items));
    },

    removeFromCart(index) {
        const y = confirm('Ingin hapus slot ini ?')

        if (y) {
            if (index !== -1) {
                this.items.splice(index, 1);
                localStorage.setItem('cart', JSON.stringify(this.items));
            }
        }
    },

    formatDate(date) {
        const dateObj = new Date(date);

        const options = {
            weekday: 'long',     // full day name (e.g., Senin)
            day: 'numeric',      // day of the month (e.g., 14)
            month: 'long',       // full month name (e.g., Maret) 
            year: 'numeric'      // 4-digit year (e.g., 2025)
        };

        return dateObj.toLocaleDateString('id-ID', options);
    },

    getCartCount() {
        return this.items.length;
    },

    isInCart(date, service) {
        return this.items.some((item) => item.date === date && item.service === service);
    },
    formatPrice(price) {
        return 'Rp ' + price.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    },
});

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});
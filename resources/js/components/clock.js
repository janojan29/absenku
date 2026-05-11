/**
 * Real-time Clock Alpine.js Component
 * Updates every second, with greeting based on time of day
 */
export default function clockComponent() {
    return {
        time: '',
        date: '',
        greeting: '',
        seconds: '',

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();

            // Format time HH:MM
            this.time = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            });

            // Format seconds
            this.seconds = now.toLocaleTimeString('id-ID', {
                second: '2-digit',
            }).slice(-2);

            // Format date
            this.date = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });

            // Dynamic greeting
            const hour = now.getHours();
            if (hour < 11) {
                this.greeting = 'Selamat Pagi';
            } else if (hour < 14) {
                this.greeting = 'Selamat Siang';
            } else if (hour < 17) {
                this.greeting = 'Selamat Sore';
            } else {
                this.greeting = 'Selamat Malam';
            }
        },

        /**
         * Full datetime string for topbar display
         */
        get fullDateTime() {
            return `${this.date} • ${this.time}`;
        },

        /**
         * Large digital clock format for attendance page
         */
        get digitalClock() {
            return `${this.time}:${this.seconds}`;
        },
    };
}

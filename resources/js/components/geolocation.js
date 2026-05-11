/**
 * Geolocation Alpine.js Component
 * Handles GPS location detection and distance calculation for attendance
 */
export default function geolocationComponent(schoolLat = 0, schoolLng = 0, maxRadius = 100) {
    return {
        latitude: null,
        longitude: null,
        accuracy: null,
        distance: null,
        isInRange: false,
        isLoading: false,
        error: null,
        watchId: null,
        schoolLat: parseFloat(schoolLat),
        schoolLng: parseFloat(schoolLng),
        maxRadius: parseFloat(maxRadius),

        init() {
            this.startWatching();
        },

        destroy() {
            if (this.watchId !== null) {
                navigator.geolocation.clearWatch(this.watchId);
            }
        },

        startWatching() {
            if (!navigator.geolocation) {
                this.error = 'Browser tidak mendukung geolocation.';
                return;
            }

            this.isLoading = true;

            // Initial position
            navigator.geolocation.getCurrentPosition(
                (pos) => this.handlePosition(pos),
                (err) => this.handleError(err),
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );

            // Watch for position changes
            this.watchId = navigator.geolocation.watchPosition(
                (pos) => this.handlePosition(pos),
                (err) => this.handleError(err),
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        },

        handlePosition(pos) {
            this.latitude = pos.coords.latitude;
            this.longitude = pos.coords.longitude;
            this.accuracy = Math.round(pos.coords.accuracy);
            this.isLoading = false;
            this.error = null;

            // Calculate distance to school
            this.distance = this.calculateDistance(
                this.latitude,
                this.longitude,
                this.schoolLat,
                this.schoolLng
            );

            this.isInRange = this.distance <= this.maxRadius;
        },

        handleError(err) {
            this.isLoading = false;
            switch (err.code) {
                case err.PERMISSION_DENIED:
                    this.error = 'Izin lokasi ditolak. Aktifkan GPS dan izinkan akses lokasi.';
                    break;
                case err.POSITION_UNAVAILABLE:
                    this.error = 'Informasi lokasi tidak tersedia.';
                    break;
                case err.TIMEOUT:
                    this.error = 'Waktu permintaan lokasi habis. Coba lagi.';
                    break;
                default:
                    this.error = 'Terjadi kesalahan saat mengambil lokasi.';
            }
        },

        /**
         * Calculate distance between two coordinates using Haversine formula
         * Returns distance in meters
         */
        calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371e3; // Earth's radius in meters
            const rad = Math.PI / 180;
            const dLat = (lat2 - lat1) * rad;
            const dLng = (lng2 - lng1) * rad;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * rad) * Math.cos(lat2 * rad) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return Math.round(R * c);
        },

        /**
         * Attach coordinates to a form and submit
         */
        attachAndSubmit(event, form) {
            event.preventDefault();

            if (this.latitude === null || this.longitude === null) {
                // Fallback: try getting position once
                if (!navigator.geolocation) {
                    alert('Browser tidak mendukung geolocation.');
                    return false;
                }

                this.isLoading = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.handlePosition(pos);
                        form.querySelector('input[name="latitude"]').value = pos.coords.latitude;
                        form.querySelector('input[name="longitude"]').value = pos.coords.longitude;
                        form.submit();
                    },
                    (err) => {
                        this.handleError(err);
                        this.isLoading = false;
                        alert('Gagal mengambil lokasi: ' + this.error);
                    },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                );
                return false;
            }

            form.querySelector('input[name="latitude"]').value = this.latitude;
            form.querySelector('input[name="longitude"]').value = this.longitude;
            form.submit();
        },

        /**
         * Formatted distance string
         */
        get distanceText() {
            if (this.distance === null) return '...';
            if (this.distance >= 1000) {
                return (this.distance / 1000).toFixed(1) + ' km';
            }
            return this.distance + ' m';
        },

        /**
         * Status text for display
         */
        get statusText() {
            if (this.isLoading) return 'Mendeteksi lokasi...';
            if (this.error) return this.error;
            if (this.isInRange) return 'Dalam Jangkauan Sekolah ✓';
            return `Di Luar Jangkauan (${this.distanceText})`;
        },
    };
}

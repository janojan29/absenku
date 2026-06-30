/**
 * Geolocation Alpine.js Component
 * Handles GPS location detection, distance calculation, and fake GPS detection for attendance.
 *
 * Anti-Fake GPS measures:
 * 1. Multi-sample stability check: collects GPS samples and checks variance.
 *    Real GPS has slight fluctuations; fake GPS returns identical coordinates.
 * 2. Accuracy validation: fake GPS often reports impossibly perfect accuracy (< 5m).
 * 3. Server-side accuracy validation as a secondary check.
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

        // Anti-fake GPS: multi-sample tracking
        _samples: [],
        _maxSamples: 5,
        _stabilityChecked: false,
        isSuspicious: false,
        suspiciousReason: '',

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

            // Collect sample for stability check
            this._collectSample(pos.coords);

            // Calculate distance to school
            this.distance = this.calculateDistance(
                this.latitude,
                this.longitude,
                this.schoolLat,
                this.schoolLng
            );

            this.isInRange = this.distance <= this.maxRadius;
        },

        /**
         * Collect GPS sample for fake GPS detection.
         * After collecting enough samples, check for suspicious patterns.
         */
        _collectSample(coords) {
            this._samples.push({
                lat: coords.latitude,
                lng: coords.longitude,
                accuracy: coords.accuracy,
                timestamp: Date.now(),
            });

            // Keep only the last N samples
            if (this._samples.length > this._maxSamples) {
                this._samples.shift();
            }

            // Run stability check once we have enough samples
            if (this._samples.length >= 3 && !this._stabilityChecked) {
                this._checkStability();
            }
        },

        /**
         * Check GPS sample stability to detect fake GPS.
         * Real GPS always has slight variations in coordinates.
         * Fake GPS returns identical coordinates every time.
         */
        _checkStability() {
            // Bypass fake GPS check in local development or ngrok, BUT only for desktop browsers
            const hostname = window.location.hostname;
            const isLocalOrNgrok = hostname === 'localhost' || 
                                   hostname === '127.0.0.1' || 
                                   hostname.endsWith('.ngrok-free.dev') || 
                                   hostname.endsWith('.ngrok-free.app') || 
                                   hostname.endsWith('.ngrok.io');

            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

            if (isLocalOrNgrok && !isMobile) {
                this.isSuspicious = false;
                this._stabilityChecked = true;
                return;
            }

            const samples = this._samples;
            if (samples.length < 3) return;

            // Calculate standard deviation of latitude and longitude
            const lats = samples.map(s => s.lat);
            const lngs = samples.map(s => s.lng);

            const latStdDev = this._stdDev(lats);
            const lngStdDev = this._stdDev(lngs);

            // Check 1: Zero variance = coordinates are exactly the same (very suspicious)
            // Real GPS always has micro-fluctuations (even indoors).
            // Threshold: stddev < 0.0000001 degrees ≈ ~0.01mm — practically impossible for real GPS
            if (latStdDev < 0.0000001 && lngStdDev < 0.0000001) {
                this.isSuspicious = true;
                this.suspiciousReason = 'fake GPS terdeteksi.';
            }

            // Check 2: Accuracy too perfect (< 3m consistently)
            const avgAccuracy = samples.reduce((sum, s) => sum + s.accuracy, 0) / samples.length;
            if (avgAccuracy < 3) {
                this.isSuspicious = true;
                this.suspiciousReason = 'fake GPS terdeteksi.';
            }

            this._stabilityChecked = true;
        },

        /**
         * Calculate standard deviation of an array of numbers.
         */
        _stdDev(values) {
            const n = values.length;
            if (n < 2) return 0;
            const mean = values.reduce((a, b) => a + b, 0) / n;
            const variance = values.reduce((sum, v) => sum + (v - mean) ** 2, 0) / n;
            return Math.sqrt(variance);
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
         * Attach coordinates and accuracy to a form and submit.
         * Blocks submission if fake GPS is suspected.
         */
        attachAndSubmit(event, form) {
            event.preventDefault();

            // Block if fake GPS is suspected
            if (this.isSuspicious) {
                alert('⚠️ Terdeteksi menggunakan fake GPS.\n\n' + this.suspiciousReason + '\n\nMatikan aplikasi fake GPS dan coba lagi.');
                return false;
            }

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

                        // Re-check after getting position
                        if (this.isSuspicious) {
                            this.isLoading = false;
                            alert('⚠️ Terdeteksi menggunakan fake GPS.\n\n' + this.suspiciousReason + '\n\nMatikan aplikasi fake GPS dan coba lagi.');
                            return;
                        }

                        form.querySelector('input[name="latitude"]').value = pos.coords.latitude;
                        form.querySelector('input[name="longitude"]').value = pos.coords.longitude;
                        form.querySelector('input[name="accuracy"]').value = pos.coords.accuracy;
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
            form.querySelector('input[name="accuracy"]').value = this.accuracy;
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
            if (this.isSuspicious) return '⚠️ Fake GPS Terdeteksi';
            if (this.isInRange) return 'Dalam Jangkauan Sekolah ✓';
            return `Di Luar Jangkauan (${this.distanceText})`;
        },
    };
}

/**
 * Animated Number Counter Alpine.js Component
 * Counts up from 0 to target value with easing
 */
export default function counterComponent(target = 0, duration = 800) {
    return {
        current: 0,
        target: typeof target === 'string' ? parseInt(target, 10) : target,
        duration: duration,
        hasAnimated: false,

        init() {
            // Use IntersectionObserver to trigger animation when visible
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && !this.hasAnimated) {
                            this.hasAnimated = true;
                            this.animateCount();
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.3 }
            );

            observer.observe(this.$el);
        },

        animateCount() {
            if (this.target === 0) {
                this.current = 0;
                return;
            }

            const startTime = performance.now();
            const startValue = 0;
            const endValue = this.target;

            const step = (timestamp) => {
                const elapsed = timestamp - startTime;
                const progress = Math.min(elapsed / this.duration, 1);

                // Ease out cubic
                const eased = 1 - Math.pow(1 - progress, 3);
                this.current = Math.round(startValue + (endValue - startValue) * eased);

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    this.current = endValue;
                }
            };

            requestAnimationFrame(step);
        },

        /**
         * Reset and re-animate (useful for Livewire updates)
         */
        reset(newTarget) {
            this.target = typeof newTarget === 'string' ? parseInt(newTarget, 10) : newTarget;
            this.current = 0;
            this.hasAnimated = false;
            this.animateCount();
        },
    };
}

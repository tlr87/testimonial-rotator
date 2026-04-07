document.addEventListener('DOMContentLoaded', function () {
    const rotators = document.querySelectorAll('.testimonial-rotator');

    rotators.forEach(rotator => {
        const items = rotator.querySelectorAll('.testimonial-item');
        if (items.length <= 1) return;

        let current = 0;
        const intervalMs = parseInt(rotator.dataset.interval) || 10000;
        const transitionType = rotator.dataset.transition || 'fade';

        let timer;
        let isPaused = false;

        function startTimer() {
            if (timer) clearInterval(timer);
            timer = setInterval(() => {
                if (!isPaused) rotate();
            }, intervalMs);
        }

        // Show first item
        function showItem(index) {
            items.forEach((item, i) => {
                item.classList.remove('active', 'prev');
                if (i === index) {
                    item.classList.add('active');
                }
            });
        }

        function rotate() {
            let next = (current + 1) % items.length;

            if (transitionType === 'slide') {
                // Prepare previous for slide-out
                items[current].classList.add('prev');
            }

            showItem(next);
            current = next;
        }

        // Initial display
        showItem(0);
        startTimer();

        // Pause on Hover
        rotator.addEventListener('mouseenter', () => {
            isPaused = true;
            if (timer) clearInterval(timer);
        });

        rotator.addEventListener('mouseleave', () => {
            isPaused = false;
            startTimer();
        });
    });
});
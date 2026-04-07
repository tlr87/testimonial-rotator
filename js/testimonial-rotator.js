document.addEventListener('DOMContentLoaded', function () {
    const rotators = document.querySelectorAll('.testimonial-rotator');

    rotators.forEach(rotator => {
        const items = rotator.querySelectorAll('.testimonial-item');
        if (items.length <= 1) return;

        let current = 0;
        const intervalMs = parseInt(rotator.dataset.interval) || 10000;

        // Show first item
        items[0].classList.add('active');

        function rotate() {
            items[current].classList.remove('active');
            current = (current + 1) % items.length;
            items[current].classList.add('active');
        }

        setInterval(rotate, intervalMs);
    });
});
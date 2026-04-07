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

        const prevBtn = rotator.querySelector('.tr-prev');
        const nextBtn = rotator.querySelector('.tr-next');
        const dotsContainer = rotator.querySelector('.tr-dots');

        const showArrows = rotator.dataset.showArrows === '1';
        const showDots   = rotator.dataset.showDots   === '1';

        // Create dots only if enabled
        if (showDots) {
            items.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.classList.add('tr-dot');
                if (index === 0) dot.classList.add('active');
                dot.addEventListener('click', () => goTo(index));
                dotsContainer.appendChild(dot);
            });
        }

        const dots = dotsContainer.querySelectorAll('.tr-dot');

        function updateDots() {
            if (!showDots) return;
            dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
        }

        function showItem(index) {
            items.forEach((item, i) => {
                item.classList.remove('active', 'prev');
                if (i === index) item.classList.add('active');
            });
            updateDots();
        }

        function rotate() {
            if (isPaused) return;
            let next = (current + 1) % items.length;
            if (transitionType === 'slide') items[current].classList.add('prev');
            current = next;
            showItem(current);
        }

        function goTo(index) {
            if (index === current) return;
            if (transitionType === 'slide') items[current].classList.add('prev');
            current = index;
            showItem(current);
            resetTimer();
        }

        function startTimer() {
            if (timer) clearInterval(timer);
            timer = setInterval(rotate, intervalMs);
        }

        function resetTimer() {
            if (timer) clearInterval(timer);
            startTimer();
        }

        // Initialize
        showItem(0);
        startTimer();

        // Button events (only if arrows enabled)
        if (showArrows) {
            prevBtn.addEventListener('click', () => {
                let prev = (current - 1 + items.length) % items.length;
                if (transitionType === 'slide') items[current].classList.add('prev');
                current = prev;
                showItem(current);
                resetTimer();
            });

            nextBtn.addEventListener('click', () => {
                let next = (current + 1) % items.length;
                if (transitionType === 'slide') items[current].classList.add('prev');
                current = next;
                showItem(current);
                resetTimer();
            });
        }

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
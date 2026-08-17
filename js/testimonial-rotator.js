document.addEventListener('DOMContentLoaded', function () {
    const rotators = document.querySelectorAll('.testimonial-rotator');

    rotators.forEach(rotator => {
        const items = rotator.querySelectorAll('.testimonial-item');

        // Handle single testimonial
        if (items.length <= 1) {
            items[0]?.classList.add('active');
            return;
        }

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

        // Create dots
        if (showDots && dotsContainer) {
            items.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.classList.add('tr-dot');
                if (index === 0) dot.classList.add('active');

                dot.addEventListener('click', (e) => {
                    e.stopPropagation();
                    goTo(index);
                });

                dotsContainer.appendChild(dot);
            });
        }

        const dots = showDots ? dotsContainer.querySelectorAll('.tr-dot') : [];

        function updateDots() {
            if (!showDots) return;
            dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
        }

        function showItem(index) {
            items.forEach((item, i) => {
                item.classList.remove('active', 'prev');
                if (i === index) {
                    item.classList.add('active');
                }
            });
            updateDots();
        }

        function rotate() {
            if (isPaused) return;
            let next = (current + 1) % items.length;

            if (transitionType === 'slide') {
                items[current].classList.add('prev');
            }

            current = next;
            showItem(current);
        }

        function goTo(index) {
            if (index === current) return;

            if (transitionType === 'slide') {
                items[current].classList.add('prev');
            }

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

        // Navigation buttons
        if (showArrows) {
            prevBtn?.addEventListener('click', (e) => {
                e.stopPropagation();

                isPaused = true;
                if (timer) clearInterval(timer);

                let prev = (current - 1 + items.length) % items.length;

                if (transitionType === 'slide') {
                    items[current].classList.add('prev');
                }

                current = prev;
                showItem(current);
                resetTimer();
            });

            nextBtn?.addEventListener('click', (e) => {
                e.stopPropagation();

                isPaused = true;
                if (timer) clearInterval(timer);

                let next = (current + 1) % items.length;

                if (transitionType === 'slide') {
                    items[current].classList.add('prev');
                }

                current = next;
                showItem(current);
                resetTimer();
            });
        }

        // Click handler (ignore buttons/links)
        function handleItemClick(e) {
            if (e.target.closest('a, button')) return;

            const activeItem = rotator.querySelector('.testimonial-item.active');
            if (!activeItem) return;

            const link = activeItem.dataset.link;
            if (link) {
                window.open(link, '_blank');
            }
        }

        rotator.addEventListener('click', handleItemClick);

        // Pause on hover
        rotator.addEventListener('mouseenter', () => {
            isPaused = true;
            if (timer) clearInterval(timer);
        });

        rotator.addEventListener('mouseleave', () => {
            isPaused = false;
            startTimer();
        });

        // Swipe support (mobile)
        let startX = 0;
        let endX = 0;

        rotator.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        rotator.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            const diff = startX - endX;

            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    nextBtn?.click();
                } else {
                    prevBtn?.click();
                }
            }
        });

        // Pause when off-screen
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (!isPaused) startTimer();
                } else {
                    if (timer) clearInterval(timer);
                }
            });
        }, { threshold: 0.3 });

        observer.observe(rotator);
    });

document.addEventListener('DOMContentLoaded', function () {

    const button = document.getElementById(
        'tr-copy-shortcode-button'
    );

    const input = document.getElementById(
        'tr-shortcode-field'
    );

    const message = document.getElementById(
        'tr-copy-shortcode-message'
    );


    if (!button || !input || !message) {
        return;
    }


    button.addEventListener('click', function () {

        const shortcode = input.value;


        if (
            navigator.clipboard &&
            window.isSecureContext
        ) {

            navigator.clipboard
                .writeText(shortcode)
                .then(function () {

                    trShowCopyMessage();

                })
                .catch(function () {

                    trFallbackCopy(input);

                });

        } else {

            trFallbackCopy(input);

        }

    });


    function trFallbackCopy(input) {

        input.focus();

        input.select();

        input.setSelectionRange(
            0,
            input.value.length
        );


        try {

            document.execCommand('copy');

        } catch (error) {

            /*
             * If the browser refuses the automatic
             * copy operation, leave the shortcode
             * selected so the user can copy it manually.
             */

        }


        trShowCopyMessage();

    }


    function trShowCopyMessage() {

        message.style.display = 'block';


        window.setTimeout(function () {

            message.style.display = 'none';

        }, 2000);

    }

});
    
});
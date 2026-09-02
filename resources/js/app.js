import './bootstrap';

const profilePhotoInput = document.querySelector('[data-profile-photo-input]');

if (profilePhotoInput) {
    const profilePhotoPreview = document.querySelector('[data-profile-photo-preview]');
    const profilePhotoFallback = document.querySelector('[data-profile-photo-fallback]');
    const profilePhotoStatus = document.querySelector('[data-profile-photo-status]');

    profilePhotoInput.addEventListener('change', () => {
        const [selectedPhoto] = profilePhotoInput.files;

        if (! selectedPhoto) {
            return;
        }

        const previewUrl = URL.createObjectURL(selectedPhoto);

        profilePhotoPreview.src = previewUrl;
        profilePhotoPreview.hidden = false;
        profilePhotoFallback.hidden = true;
        profilePhotoStatus.textContent = `${selectedPhoto.name} selected · save to apply`;

        profilePhotoPreview.addEventListener('load', () => URL.revokeObjectURL(previewUrl), { once: true });
    });
}

// Compact long comic descriptions without hiding short copy.
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-expandable-description]').forEach((description) => {
        const text = description.querySelector('[data-description-text]');
        const toggle = description.querySelector('[data-description-toggle]');

        if (!text || !toggle) return;

        description.setAttribute('data-ready', '');

        const syncToggle = () => {
            if (description.hasAttribute('data-expanded')) return;
            toggle.hidden = text.scrollHeight <= text.clientHeight + 1;
        };

        toggle.addEventListener('click', () => {
            const isExpanded = !description.hasAttribute('data-expanded');

            description.toggleAttribute('data-expanded', isExpanded);
            toggle.setAttribute('aria-expanded', String(isExpanded));
            toggle.firstChild.textContent = isExpanded ? 'Kembali ' : 'Selengkapnya ';
        });

        syncToggle();

        if ('ResizeObserver' in window) {
            const resizeObserver = new ResizeObserver(syncToggle);
            resizeObserver.observe(text);
        }
    });
});

// Accessible catalog carousel with calm autoplay and touch navigation.
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('[data-catalog-slider]');

    if (!slider) return;

    const slides = Array.from(slider.querySelectorAll('[data-catalog-slide]'));
    const dots = Array.from(slider.querySelectorAll('[data-catalog-dot]'));
    const previousButton = slider.querySelector('[data-catalog-prev]');
    const nextButton = slider.querySelector('[data-catalog-next]');
    const currentLabel = slider.querySelector('[data-catalog-current]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let currentIndex = 0;
    let autoplayTimer = null;
    let pointerStartX = null;

    if (slides.length < 2) return;

    const showSlide = (nextIndex, restartAutoplay = false) => {
        currentIndex = (nextIndex + slides.length) % slides.length;

        slides.forEach((slide, index) => {
            const isActive = index === currentIndex;
            slide.toggleAttribute('data-active', isActive);
            slide.setAttribute('aria-hidden', String(!isActive));
            slide.toggleAttribute('inert', !isActive);
        });

        dots.forEach((dot, index) => {
            const isActive = index === currentIndex;
            dot.toggleAttribute('data-active', isActive);
            dot.setAttribute('aria-selected', String(isActive));
        });

        if (currentLabel) {
            currentLabel.textContent = String(currentIndex + 1).padStart(2, '0');
        }

        if (restartAutoplay) startAutoplay();
    };

    const stopAutoplay = () => {
        if (autoplayTimer) window.clearInterval(autoplayTimer);
        autoplayTimer = null;
    };

    const startAutoplay = () => {
        stopAutoplay();

        if (reducedMotion.matches || document.visibilityState === 'hidden') return;

        autoplayTimer = window.setInterval(() => showSlide(currentIndex + 1), 7000);
    };

    previousButton?.addEventListener('click', () => showSlide(currentIndex - 1, true));
    nextButton?.addEventListener('click', () => showSlide(currentIndex + 1, true));

    dots.forEach((dot) => {
        dot.addEventListener('click', () => showSlide(Number(dot.dataset.slideTarget), true));
    });

    slider.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            showSlide(currentIndex - 1, true);
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            showSlide(currentIndex + 1, true);
        }
    });

    slider.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'touch') pointerStartX = event.clientX;
    });

    slider.addEventListener('pointerup', (event) => {
        if (pointerStartX === null || event.pointerType !== 'touch') return;

        const distance = event.clientX - pointerStartX;
        pointerStartX = null;

        if (Math.abs(distance) < 45) return;
        showSlide(currentIndex + (distance < 0 ? 1 : -1), true);
    });

    slider.addEventListener('pointercancel', () => {
        pointerStartX = null;
    });

    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);
    slider.addEventListener('focusin', stopAutoplay);
    slider.addEventListener('focusout', () => {
        window.setTimeout(() => {
            if (!slider.matches(':focus-within')) startAutoplay();
        }, 0);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') stopAutoplay();
        else startAutoplay();
    });

    reducedMotion.addEventListener('change', startAutoplay);
    window.addEventListener('beforeunload', stopAutoplay);
    startAutoplay();
});

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.getElementById('site-menu');

    if (!menuToggle || !navMenu) return;

    const mobileMenu = window.matchMedia('(max-width: 768px)');

    const setMenuState = (isOpen) => {
        menuToggle.setAttribute('aria-expanded', String(isOpen));
        navMenu.setAttribute('data-open', String(isOpen));

        if (mobileMenu.matches) {
            navMenu.setAttribute('aria-hidden', String(!isOpen));
            navMenu.toggleAttribute('inert', !isOpen);
        } else {
            navMenu.removeAttribute('aria-hidden');
            navMenu.removeAttribute('inert');
        }
    };

    setMenuState(false);

    menuToggle.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        setMenuState(!isExpanded);
    });

    navMenu.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') {
            setMenuState(false);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
            setMenuState(false);
            menuToggle.focus();
        }
    });

    mobileMenu.addEventListener('change', function() {
        setMenuState(false);
    });
});

// Near real-time comments with AJAX submission and lightweight polling.
document.addEventListener('DOMContentLoaded', function() {
    const commentsSection = document.querySelector('.comments-section');
    const commentList = commentsSection?.querySelector('[data-comment-list]');
    const status = commentsSection?.querySelector('[data-comment-status]');

    if (!commentsSection || !commentList) return;

    let refreshInProgress = false;

    const hasDraft = () => Array.from(commentsSection.querySelectorAll('textarea'))
        .some((textarea) => textarea.value.trim() !== '');

    const setStatus = (message, isError = false) => {
        if (!status) return;

        status.textContent = message;
        status.toggleAttribute('data-error', isError);
    };

    const refreshComments = async ({ force = false } = {}) => {
        if (refreshInProgress || (!force && hasDraft()) || document.visibilityState === 'hidden') {
            return;
        }

        refreshInProgress = true;
        const openReplyIds = Array.from(commentList.querySelectorAll('[data-reply-for][open]'))
            .map((details) => details.dataset.replyFor);

        try {
            const response = await fetch(commentList.dataset.feedUrl, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('Comments could not be refreshed.');
            }

            commentList.innerHTML = await response.text();

            openReplyIds.forEach((id) => {
                commentList.querySelector(`[data-reply-for="${id}"]`)?.setAttribute('open', '');
            });
        } catch (error) {
            setStatus(error.message, true);
        } finally {
            refreshInProgress = false;
        }
    };

    commentsSection.addEventListener('submit', async function(event) {
        const form = event.target.closest('form[data-comment-action]');

        if (!form) return;

        event.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) submitButton.disabled = true;
        setStatus('Sending…');

        try {
            const response = await fetch(form.action, {
                method: form.method.toUpperCase(),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessage = data.errors
                    ? Object.values(data.errors).flat()[0]
                    : data.message;

                throw new Error(validationMessage || 'The comment could not be saved.');
            }

            if (!form.querySelector('input[name="_method"][value="DELETE"]')) {
                form.reset();
                form.closest('details')?.removeAttribute('open');
            }

            await refreshComments({ force: true });
            setStatus(data.message || 'Comments updated.');
        } catch (error) {
            setStatus(error.message, true);
        } finally {
            if (submitButton?.isConnected) submitButton.disabled = false;
        }
    });

    const pollingTimer = window.setInterval(() => refreshComments(), 5000);

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') refreshComments();
    });

    window.addEventListener('beforeunload', function() {
        window.clearInterval(pollingTimer);
    });
});

// Interactive comic rating with immediate average updates.
document.addEventListener('DOMContentLoaded', function() {
    const ratingForm = document.querySelector('[data-rating-form]');
    const ratingSummary = document.querySelector('[data-rating-summary]');
    const ratingStatus = document.querySelector('[data-rating-status]');

    if (!ratingForm || !ratingSummary) return;

    const setRatingStatus = (message, isError = false) => {
        if (!ratingStatus) return;

        ratingStatus.textContent = message;
        ratingStatus.toggleAttribute('data-error', isError);
    };

    const updateRatingSummary = (average, count) => {
        const score = ratingSummary.querySelector('[data-rating-score]');
        const averageValue = ratingSummary.querySelector('[data-rating-average]');
        const copy = ratingSummary.querySelector('[data-rating-copy]');
        const countValue = ratingSummary.querySelector('[data-rating-count]');
        const label = ratingSummary.querySelector('[data-rating-label]');
        const empty = ratingSummary.querySelector('[data-rating-empty]');

        if (averageValue) averageValue.textContent = Number(average).toFixed(1);
        if (countValue) countValue.textContent = String(count);
        if (label) label.textContent = count === 1 ? 'rating' : 'ratings';
        if (score) score.hidden = count === 0;
        if (copy) copy.hidden = count === 0;
        if (empty) empty.hidden = count > 0;
    };

    ratingForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        const submitButton = ratingForm.querySelector('button[type="submit"]');

        if (submitButton) submitButton.disabled = true;
        ratingForm.toggleAttribute('data-submitting', true);
        setRatingStatus('Saving rating…');

        try {
            const response = await fetch(ratingForm.action, {
                method: ratingForm.method.toUpperCase(),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(ratingForm),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessage = data.errors
                    ? Object.values(data.errors).flat()[0]
                    : data.message;

                throw new Error(validationMessage || 'The rating could not be saved.');
            }

            updateRatingSummary(data.average_rating, data.rating_count);
            setRatingStatus(data.message || 'Your rating has been saved.');

            if (submitButton) submitButton.textContent = 'Update Rating';
        } catch (error) {
            setRatingStatus(error.message, true);
        } finally {
            ratingForm.removeAttribute('data-submitting');
            if (submitButton) submitButton.disabled = false;
        }
    });

    ratingForm.addEventListener('change', function(event) {
        if (event.target.matches('input[name="score"]') && !ratingForm.hasAttribute('data-submitting')) {
            ratingForm.requestSubmit();
        }
    });
});

// Scroll-aware comic reader progress, resume behavior, and distraction-free mode.
document.addEventListener('DOMContentLoaded', function() {
    const reader = document.querySelector('[data-reader]');

    if (!reader) return;

    const pages = Array.from(reader.querySelectorAll('[data-reader-page]'));
    const progressBar = document.querySelector('[data-reader-progress-bar]');
    const currentPosition = document.querySelector('[data-reader-current-position]');
    const saveStatus = document.querySelector('[data-reader-save-status]');
    const focusButton = document.querySelector('[data-reader-focus]');
    const scrollTopButton = document.querySelector('[data-reader-scroll-top]');
    const initialPage = Number(reader.dataset.initialPage);
    const progressUrl = reader.dataset.progressUrl;
    const csrfToken = reader.dataset.csrfToken;
    let activePage = initialPage;
    let lastSavedPage = initialPage;
    let saveTimer = null;
    let scrollFrame = null;
    let restoringPosition = false;

    if (pages.length === 0) return;

    const setSaveStatus = (message, isError = false) => {
        if (!saveStatus) return;

        saveStatus.textContent = message;
        saveStatus.toggleAttribute('data-error', isError);
    };

    const persistProgress = async (pageNumber) => {
        if (!progressUrl || !csrfToken || pageNumber === lastSavedPage) return;

        const formData = new FormData();
        formData.set('_token', csrfToken);
        formData.set('page_number', String(pageNumber));
        setSaveStatus('Saving…');

        try {
            const response = await fetch(progressUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            if (!response.ok) throw new Error('Progress could not be saved.');

            lastSavedPage = pageNumber;
            setSaveStatus(`Page ${pageNumber} saved`);
        } catch (error) {
            setSaveStatus(error.message, true);
        }
    };

    const scheduleProgressSave = (pageNumber) => {
        if (!progressUrl || pageNumber === lastSavedPage) return;

        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(() => persistProgress(pageNumber), 700);
    };

    const setActivePage = (pageElement, shouldSave = true) => {
        const pageNumber = Number(pageElement.dataset.pageNumber);
        const position = pages.indexOf(pageElement) + 1;

        if (!pageNumber || position < 1) return;

        pages.forEach((page) => page.toggleAttribute('data-current-page', page === pageElement));
        activePage = pageNumber;

        if (currentPosition) currentPosition.textContent = String(position);
        if (progressBar) progressBar.style.width = `${(position / pages.length) * 100}%`;

        const url = new URL(window.location.href);
        url.searchParams.set('page', String(pageNumber));
        window.history.replaceState({}, '', url);

        if (shouldSave) scheduleProgressSave(pageNumber);
    };

    const detectActivePage = () => {
        scrollFrame = null;
        if (restoringPosition) return;

        const readingLine = Math.max(150, window.innerHeight * 0.38);
        let candidate = pages[0];

        pages.forEach((page) => {
            if (page.getBoundingClientRect().top <= readingLine) candidate = page;
        });

        if (Number(candidate.dataset.pageNumber) !== activePage) setActivePage(candidate);
    };

    const requestPageDetection = () => {
        if (scrollFrame !== null) return;
        scrollFrame = window.requestAnimationFrame(detectActivePage);
    };

    const initialPageElement = pages.find((page) => Number(page.dataset.pageNumber) === initialPage) || pages[0];
    setActivePage(initialPageElement, false);

    if (initialPageElement !== pages[0]) {
        restoringPosition = true;
        const initialPageIndex = pages.indexOf(initialPageElement);
        const precedingImages = pages
            .slice(0, initialPageIndex + 1)
            .map((page) => page.querySelector('img'))
            .filter(Boolean);
        const restorePosition = () => initialPageElement.scrollIntoView({ behavior: 'auto', block: 'start' });

        precedingImages.forEach((image) => image.setAttribute('loading', 'eager'));

        const settledImages = Promise.all(precedingImages.map((image) => {
            if (image.complete) return Promise.resolve();

            return new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true });
                image.addEventListener('error', resolve, { once: true });
            });
        }));

        Promise.race([
            settledImages,
            new Promise((resolve) => window.setTimeout(resolve, 5000)),
        ]).then(() => {
            restorePosition();
            window.setTimeout(() => {
                restorePosition();
                restoringPosition = false;
                setActivePage(initialPageElement, false);
            }, 150);
        });

        setSaveStatus(`Resumed at page ${initialPage}`);
    }

    window.addEventListener('scroll', requestPageDetection, { passive: true });
    window.addEventListener('resize', requestPageDetection);

    scrollTopButton?.addEventListener('click', () => {
        pages[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    focusButton?.addEventListener('click', () => {
        const enabled = !document.body.hasAttribute('data-reader-focus-mode');

        document.body.toggleAttribute('data-reader-focus-mode', enabled);
        focusButton.setAttribute('aria-pressed', String(enabled));
        focusButton.textContent = enabled ? 'Exit focus' : 'Focus mode';
    });

    window.addEventListener('pagehide', () => {
        if (!progressUrl || !csrfToken || activePage === lastSavedPage || !navigator.sendBeacon) return;

        const formData = new FormData();
        formData.set('_token', csrfToken);
        formData.set('page_number', String(activePage));
        navigator.sendBeacon(progressUrl, formData);
    });
});

// Load the Google Books viewer only when a reader opens the preview panel.
document.addEventListener('DOMContentLoaded', function() {
    const panel = document.querySelector('[data-google-books-preview]');
    const toggle = document.querySelector('[data-google-books-preview-toggle]');

    if (!panel || !toggle) return;

    const closeButton = panel.querySelector('[data-google-books-preview-close]');
    const viewerCanvas = panel.querySelector('[data-google-books-viewer]');
    const status = panel.querySelector('[data-google-books-preview-status]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let previewStarted = false;

    const setStatus = (message, isError = false) => {
        if (!status) return;

        status.textContent = message;
        status.toggleAttribute('data-error', isError);
    };

    const showUnavailable = (message = 'This title does not have an embeddable preview in your region. You can still open its Google Books page.') => {
        viewerCanvas?.setAttribute('hidden', '');
        setStatus(message, true);
    };

    const startPreview = async () => {
        if (previewStarted || !viewerCanvas || !panel.dataset.volumeId) return;

        previewStarted = true;
        viewerCanvas.removeAttribute('hidden');
        setStatus('Loading the official Google Books preview…');

        try {
            const googleBooks = await window.zyxGoogleBooksReady;

            if (!googleBooks?.DefaultViewer) {
                throw new Error('Google Books could not be initialized.');
            }

            const initializationTimeout = window.setTimeout(() => {
                showUnavailable('Google Books did not finish loading the preview. Please use the Google Books link below.');
            }, 12000);

            try {
                const viewer = new googleBooks.DefaultViewer(viewerCanvas);

                viewer.load(
                    panel.dataset.volumeId,
                    () => {
                        window.clearTimeout(initializationTimeout);
                        showUnavailable();
                    },
                    () => {
                        window.clearTimeout(initializationTimeout);
                        viewerCanvas.removeAttribute('hidden');
                        setStatus('Google Books preview loaded.');
                    }
                );
            } catch (error) {
                window.clearTimeout(initializationTimeout);
                showUnavailable();
            }
        } catch (error) {
            viewerCanvas.setAttribute('hidden', '');
            setStatus(error.message || 'Google Books preview could not be loaded.', true);
        }
    };

    const setOpen = (isOpen) => {
        panel.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', String(isOpen));

        if (isOpen) {
            startPreview();
            window.requestAnimationFrame(() => {
                panel.scrollIntoView({
                    behavior: reducedMotion.matches ? 'auto' : 'smooth',
                    block: 'start',
                });
            });
        } else {
            toggle.focus();
        }
    };

    toggle.addEventListener('click', () => setOpen(panel.hidden));
    closeButton?.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) setOpen(false);
    });
});

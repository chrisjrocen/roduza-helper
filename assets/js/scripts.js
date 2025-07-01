function getCurrentSlide() {
    return document.querySelector('.carousel-slide.active');
}

function showSlide(slide) {
    if (!slide) return;
    document.querySelectorAll('.carousel-slide').forEach(el => {
        el.style.display = 'none';
        el.classList.remove('active');
    });
    slide.style.display = 'block';
    slide.classList.add('active');
}

function showNextSlide() {
    const currentSlide = getCurrentSlide();
    const slides = Array.from(document.querySelectorAll('.carousel-slide'));
    let index = slides.indexOf(currentSlide);
    let next = slides[(index + 1) % slides.length];
    showSlide(next);
}

function showPrevSlide() {
    const currentSlide = getCurrentSlide();
    const slides = Array.from(document.querySelectorAll('.carousel-slide'));
    let index = slides.indexOf(currentSlide);
    let prev = slides[(index - 1 + slides.length) % slides.length];
    showSlide(prev);
}
 
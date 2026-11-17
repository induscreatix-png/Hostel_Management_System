(function() {
	var slides = Array.prototype.slice.call(document.querySelectorAll('.home-slide'));
	if (slides.length < 2) return;
	if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	var activeIndex = slides.findIndex(function(slide) {
		return slide.classList.contains('is-active');
	});
	if (activeIndex < 0) activeIndex = 0;

	window.setInterval(function() {
		slides[activeIndex].classList.remove('is-active');
		slides[activeIndex].setAttribute('aria-hidden', 'true');
		activeIndex = (activeIndex + 1) % slides.length;
		slides[activeIndex].classList.add('is-active');
		slides[activeIndex].setAttribute('aria-hidden', 'false');
	}, 2000);
})();

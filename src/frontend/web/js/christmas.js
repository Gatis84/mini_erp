document.addEventListener('DOMContentLoaded', function () {
    var banner = document.getElementById('christmas-banner');
    var closeBtn = document.querySelector('.christmas-banner-close');

    if (banner && closeBtn) {
        closeBtn.addEventListener('click', function () {
            banner.style.display = 'none';
        });
    }
});
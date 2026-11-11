
document.addEventListener("DOMContentLoaded", function () {

    const categoryButtons = document.querySelectorAll('.categories-nav button');
    const galleryItems = document.querySelectorAll('.galerie-grid .galerie-item');

    if (categoryButtons.length > 0) {
        categoryButtons.forEach(button => {
            button.addEventListener('click', function () {
                const rel = this.getAttribute('rel');

                // Update active state
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                // Filter items
                galleryItems.forEach(item => {
                    item.style.display = 'none';
                    item.style.opacity = '0';
                });

                document.querySelectorAll('.galerie-grid .' + rel).forEach(item => {
                    item.style.display = 'block';
                    // Re-trigger animation or transition
                    setTimeout(() => {
                        item.style.opacity = '1';
                    }, 10);
                });
            });
        });
    }

});
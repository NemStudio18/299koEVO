<div class="swiper-container-wrapper {{ prefix }}-wrapper">
    <div class="swiper swiper-{{ prefix }}">
        <div class="swiper-wrapper">
            {% for item in items %}
                <div class="swiper-slide">
                    <img src="{{ item.getUrl() }}" alt="{{ item.getTitle() }}" />
                    {% if item.getTitle() %}
                        <div class="swiper-slide-content">
                            <h3>{{ item.getTitle() }}</h3>
                        </div>
                    {% endif %}
                </div>
            {% endfor %}
        </div>
        <!-- Pagination -->
        <div class="swiper-pagination"></div>
        <!-- Navigation -->
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const swiper{{ prefix }} = new Swiper('.swiper-{{ prefix }}', {
        loop: {{ count(items) > 1 ? 'true' : 'false' }},
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-{{ prefix }} .swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-{{ prefix }} .swiper-button-next',
            prevEl: '.swiper-{{ prefix }} .swiper-button-prev',
        },
        {% if type == 'fade' %}
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        },
        {% elseif type == 'coverflow' %}
        effect: 'coverflow',
        coverflowEffect: {
            rotate: 50,
            stretch: 0,
            depth: 100,
            modifier: 1,
            slideShadows: true,
        },
        {% endif %}
    });
});
</script>

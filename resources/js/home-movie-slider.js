import Swiper from 'swiper';
import { A11y, Navigation, Pagination } from 'swiper/modules';

const sliderElement = document.querySelector('.home-movie-slider');

if (sliderElement) {
    const slideCount = sliderElement.querySelectorAll('.swiper-slide').length;

    new Swiper(sliderElement, {
        modules: [A11y, Navigation, Pagination],
        slidesPerView: 1,
        spaceBetween: 20,
        loop: slideCount > 1,
        watchOverflow: true,
        navigation: {
            prevEl: sliderElement.querySelector('.home-movie-slider__button--prev'),
            nextEl: sliderElement.querySelector('.home-movie-slider__button--next'),
        },
        pagination: {
            el: sliderElement.querySelector('.swiper-pagination'),
            clickable: true,
        },
        a11y: {
            prevSlideMessage: '前の映画を表示',
            nextSlideMessage: '次の映画を表示',
        },
    });
}

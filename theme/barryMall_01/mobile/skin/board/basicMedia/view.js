/*

    view.js

*/

$(function($) {
    var viewDetailSlider = new Swiper('#viewDetailSlide', {
        navigation: {
            nextEl: '#viewDetailSlide .swiper-button-next',
            prevEl: '#viewDetailSlide .swiper-button-prev'
        },
        pagination: {
            el: '#viewDetailSlide .swiper-pagination',
            type: 'bullets',
            clickable: true
        },
        autoplay: false
    });

});

/*


    함수 목록


*/

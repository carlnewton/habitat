import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

if (document.getElementById('gallery') !== null) {
    const lightbox = new PhotoSwipeLightbox({
        gallery: '#gallery',
        children: 'a',
        pswpModule: () => import('photoswipe'),
    });

    lightbox.init();
}

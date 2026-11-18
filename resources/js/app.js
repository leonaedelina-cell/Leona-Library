import QRCode from 'qrcode';
import './library.js';

window.QRCode = QRCode;

if (document.querySelector('[data-pdf-reader]')) {
    import('./ebook-reader.js').catch((error) => {
        console.error('Modul pembaca e-book tidak dapat dimuat.', error);
        document.querySelector('[data-reader-loading]')?.setAttribute('hidden', '');
        const errorMessage = document.querySelector('[data-reader-error]');
        if (errorMessage) errorMessage.hidden = false;
    });
}

import { GlobalWorkerOptions, getDocument } from 'pdfjs-dist';
import workerSource from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

GlobalWorkerOptions.workerSrc = workerSource;

const reader = document.querySelector('[data-pdf-reader]');

if (reader) {
    const stage = reader.querySelector('[data-reader-stage]');
    const loadingMessage = reader.querySelector('[data-reader-loading]');
    const errorMessage = reader.querySelector('[data-reader-error]');
    const pageSurface = reader.querySelector('[data-reader-surface]');
    const canvas = reader.querySelector('[data-reader-canvas]');
    const currentPageLabel = reader.querySelector('[data-reader-current]');
    const totalPagesLabel = reader.querySelector('[data-reader-total]');
    const progress = reader.querySelector('[data-reader-progress]');
    const progressBar = reader.querySelector('[data-reader-progress-bar]');
    const previousButton = reader.querySelector('[data-reader-prev]');
    const nextButton = reader.querySelector('[data-reader-next]');
    const zoomLabel = reader.querySelector('[data-reader-zoom]');
    const zoomOutButton = reader.querySelector('[data-reader-zoom-out]');
    const zoomInButton = reader.querySelector('[data-reader-zoom-in]');
    const themeButtons = reader.querySelectorAll('[data-reader-theme]');

    let documentHandle;
    let currentPage = 1;
    let zoom = 1;
    let renderRequest = 0;
    let renderTask;
    let resizeTimer;

    const showError = (error) => {
        console.error('E-book tidak dapat ditampilkan.', error);
        loadingMessage.hidden = true;
        pageSurface.hidden = true;
        errorMessage.hidden = false;
        previousButton.disabled = true;
        nextButton.disabled = true;
    };

    const updateControls = () => {
        const percentage = Math.round((currentPage / documentHandle.numPages) * 100);

        currentPageLabel.textContent = String(currentPage);
        totalPagesLabel.textContent = String(documentHandle.numPages);
        previousButton.disabled = currentPage <= 1;
        nextButton.disabled = currentPage >= documentHandle.numPages;
        progress.setAttribute('aria-valuenow', String(percentage));
        progressBar.style.width = `${percentage}%`;
    };

    const renderCurrentPage = async () => {
        if (!documentHandle) {
            return;
        }

        const requestId = ++renderRequest;
        renderTask?.cancel();

        const page = await documentHandle.getPage(currentPage);
        if (requestId !== renderRequest) {
            return;
        }

        const outputScale = window.devicePixelRatio || 1;
        const availableWidth = Math.max(280, stage.clientWidth - 40);
        const unscaledViewport = page.getViewport({ scale: 1 });
        const fitScale = Math.min(1.2, availableWidth / unscaledViewport.width);
        const viewport = page.getViewport({ scale: fitScale * zoom });
        const context = canvas.getContext('2d', { alpha: false });

        if (!context) {
            throw new Error('Canvas 2D tidak tersedia pada browser ini.');
        }

        canvas.width = Math.ceil(viewport.width * outputScale);
        canvas.height = Math.ceil(viewport.height * outputScale);
        canvas.style.width = `${viewport.width}px`;
        canvas.style.height = `${viewport.height}px`;

        const transform = outputScale === 1
            ? null
            : [outputScale, 0, 0, outputScale, 0, 0];

        renderTask = page.render({
            canvasContext: context,
            transform,
            viewport,
        });

        try {
            await renderTask.promise;
        } catch (error) {
            if (error.name === 'RenderingCancelledException') {
                return;
            }

            throw error;
        }

        if (requestId !== renderRequest) {
            return;
        }

        loadingMessage.hidden = true;
        pageSurface.hidden = false;
        updateControls();
    };

    const changePage = (pageNumber) => {
        if (!documentHandle) {
            return;
        }

        currentPage = Math.min(documentHandle.numPages, Math.max(1, pageNumber));
        renderCurrentPage().catch(showError);
    };

    reader.addEventListener('contextmenu', (event) => event.preventDefault());
    reader.addEventListener('selectstart', (event) => event.preventDefault());

    reader.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            changePage(currentPage - 1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            changePage(currentPage + 1);
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && ['p', 's'].includes(event.key.toLowerCase())) {
            event.preventDefault();
        }
    });

    previousButton.addEventListener('click', () => changePage(currentPage - 1));
    nextButton.addEventListener('click', () => changePage(currentPage + 1));

    zoomOutButton.addEventListener('click', () => {
        zoom = Math.max(0.7, Math.round((zoom - 0.1) * 10) / 10);
        zoomLabel.textContent = `${Math.round(zoom * 100)}%`;
        renderCurrentPage().catch(showError);
    });

    zoomInButton.addEventListener('click', () => {
        zoom = Math.min(1.6, Math.round((zoom + 0.1) * 10) / 10);
        zoomLabel.textContent = `${Math.round(zoom * 100)}%`;
        renderCurrentPage().catch(showError);
    });

    themeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            reader.dataset.theme = button.dataset.readerTheme;
            themeButtons.forEach((themeButton) => {
                const isActive = themeButton === button;
                themeButton.classList.toggle('active', isActive);
                themeButton.setAttribute('aria-pressed', String(isActive));
            });
        });
    });

    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            if (documentHandle) {
                renderCurrentPage().catch(showError);
            }
        }, 120);
    });

    getDocument({
        url: reader.dataset.pdfUrl,
        withCredentials: true,
    }).promise
        .then((pdfDocument) => {
            documentHandle = pdfDocument;
            return renderCurrentPage();
        })
        .catch(showError);
}

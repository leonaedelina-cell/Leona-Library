document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.main-nav');
    menuToggle?.addEventListener('click', () => {
        const open = nav.classList.toggle('open');
        menuToggle.setAttribute('aria-expanded', String(open));
    });

    document.querySelectorAll('[data-dashboard-layout]').forEach((layout) => {
        const sidebar = layout.querySelector('.dashboard-sidebar');
        const toggle = layout.querySelector('[data-sidebar-toggle]');
        if (!sidebar || !toggle) return;

        const mobileQuery = window.matchMedia('(max-width: 760px)');
        const closeButtons = layout.querySelectorAll('[data-sidebar-close]');
        let isOpen = !mobileQuery.matches;
        layout.classList.add('sidebar-enhanced');

        const updateSidebarState = (open, restoreFocus = false) => {
            isOpen = open;
            const isMobile = mobileQuery.matches;
            const label = isMobile
                ? (isOpen ? 'Tutup menu' : 'Buka menu')
                : (isOpen ? 'Ciutkan sidebar' : 'Buka sidebar');

            layout.classList.toggle('is-sidebar-mobile', isMobile);
            layout.classList.toggle('is-sidebar-open', isMobile && isOpen);
            layout.classList.toggle('is-sidebar-collapsed', !isMobile && !isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
            toggle.setAttribute('aria-label', label);
            const toggleLabel = toggle.querySelector('[data-sidebar-toggle-label]');
            if (toggleLabel) toggleLabel.textContent = label;
            document.body.classList.toggle('dashboard-sidebar-open', isMobile && isOpen);

            if (restoreFocus) {
                if (isMobile && isOpen) sidebar.querySelector('[data-sidebar-close]')?.focus();
                else toggle.focus();
            }
        };

        updateSidebarState(isOpen);
        toggle.addEventListener('click', () => updateSidebarState(!isOpen, true));
        closeButtons.forEach((button) => {
            button.addEventListener('click', () => updateSidebarState(false, true));
        });
        sidebar.querySelectorAll('a[href^="#"]').forEach((link) => {
            link.addEventListener('click', () => {
                if (mobileQuery.matches) updateSidebarState(false);
            });
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && mobileQuery.matches && isOpen) {
                updateSidebarState(false, true);
            }
        });

        const handleViewportChange = () => updateSidebarState(!mobileQuery.matches);
        if (typeof mobileQuery.addEventListener === 'function') {
            mobileQuery.addEventListener('change', handleViewportChange);
        } else {
            mobileQuery.addListener(handleViewportChange);
        }
    });

    document.querySelectorAll('.member-qr[data-qr]').forEach((canvas) => {
        window.QRCode.toCanvas(canvas, canvas.dataset.qr, {
            errorCorrectionLevel: 'M',
            margin: 1,
            width: 66,
            color: { dark: '#263528', light: '#ffffff' },
        }).catch((error) => console.error('Kartu QR gagal dibuat.', error));
    });

    document.querySelectorAll('[data-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('[data-tab]').forEach((item) => item.classList.toggle('active', item === tab));
            document.querySelectorAll('[data-panel]').forEach((panel) => panel.classList.toggle('active', panel.dataset.panel === tab.dataset.tab));
        });
    });

    const openModal = (modal) => {
        if (!modal) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('input:not([type="hidden"])')?.focus();
    };
    const closeModal = (modal) => {
        if (!modal) return;
        modal.hidden = true;
        if (!document.querySelector('.modal-backdrop:not([hidden])')) document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => openModal(document.getElementById(button.dataset.openModal)));
    });
    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.closest('.modal-backdrop')));
    });
    document.querySelectorAll('.modal-backdrop').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal(modal);
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop:not([hidden])').forEach(closeModal);
        }
    });

    const configureResourceModal = (options) => {
        const modal = document.getElementById(options.modalId);
        const form = modal?.querySelector('form');
        const title = document.getElementById(options.titleId);
        const storeUrl = form?.dataset.storeUrl;
        if (!modal || !form || !storeUrl) return;

        const resetForm = () => {
            form.reset();
            form.action = storeUrl;
            form.querySelector('input[name="_method"]')?.remove();
            title.textContent = options.createTitle;
            options.reset?.();
        };

        document.querySelector(options.newButton)?.addEventListener('click', () => {
            resetForm();
            openModal(modal);
        });

        document.querySelectorAll(options.editButtons).forEach((button) => {
            button.addEventListener('click', () => {
                resetForm();
                const data = JSON.parse(button.dataset[options.dataKey]);
                form.action = `${window.location.pathname}${options.path}/${data.id}`;
                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'PUT';
                form.append(method);

                Object.entries(data).forEach(([key, value]) => {
                    const field = form.elements.namedItem(key);
                    if (!field) return;
                    if (field.type === 'checkbox') field.checked = Boolean(value);
                    else if (field.type !== 'file' && field.type !== 'password') field.value = value ?? '';
                });

                title.textContent = options.editTitle;
                options.edit?.(data, form);
                openModal(modal);
            });
        });
    };

    configureResourceModal({
        modalId: 'book-modal',
        titleId: 'book-modal-title',
        newButton: '[data-new-book]',
        editButtons: '[data-edit-book]',
        dataKey: 'editBook',
        path: '/buku',
        createTitle: 'Tambahkan cerita baru.',
        editTitle: 'Perbarui detail buku.',
        reset: () => {
            document.querySelector('[data-current-ebook]')?.replaceChildren();
            const removeEbookWrap = document.querySelector('[data-remove-ebook-wrap]');
            if (removeEbookWrap) removeEbookWrap.hidden = true;
        },
        edit: (book) => {
            const currentEbook = document.querySelector('[data-current-ebook]');
            const removeEbookWrap = document.querySelector('[data-remove-ebook-wrap]');
            if (currentEbook) currentEbook.textContent = book.has_ebook ? 'File PDF tersimpan. Pilih file baru untuk menggantinya.' : 'Belum ada PDF.';
            if (removeEbookWrap) removeEbookWrap.hidden = !book.has_ebook;
        },
    });

    configureResourceModal({
        modalId: 'user-modal',
        titleId: 'user-modal-title',
        newButton: '[data-new-user]',
        editButtons: '[data-edit-user]',
        dataKey: 'editUser',
        path: '/pengguna',
        createTitle: 'Buat akun baru.',
        editTitle: 'Perbarui akun.',
        edit: (_user, form) => {
            const password = form.elements.namedItem('password');
            const confirmation = form.elements.namedItem('password_confirmation');
            password.required = false;
            confirmation.required = false;
            password.placeholder = 'Kosongkan jika tidak ingin mengganti password';
        },
        reset: () => {
            const form = document.querySelector('#user-modal form');
            if (!form) return;
            form.elements.namedItem('password').required = true;
            form.elements.namedItem('password_confirmation').required = true;
            form.elements.namedItem('password').placeholder = '';
        },
    });

    const scannerButton = document.querySelector('[data-start-scanner]');
    const scannerPanel = document.querySelector('[data-scanner-panel]');
    const scannerVideo = document.querySelector('[data-scanner-video]');
    const scannerStatus = document.querySelector('[data-scanner-status]');
    const scannerInput = document.querySelector('[data-member-number]');
    const stopScannerButton = document.querySelector('[data-stop-scanner]');
    let scannerStream = null;
    let scannerFrame = 0;
    let scanning = false;

    const stopScanner = () => {
        scanning = false;
        cancelAnimationFrame(scannerFrame);
        scannerStream?.getTracks().forEach((track) => track.stop());
        scannerStream = null;
        if (scannerVideo) scannerVideo.srcObject = null;
        if (scannerPanel) scannerPanel.hidden = true;
    };

    scannerButton?.addEventListener('click', async () => {
        scannerPanel.hidden = false;

        if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
            scannerStatus.textContent = 'Pemindai kamera tidak tersedia di browser ini. Ketik nomor kartu secara manual.';
            return;
        }

        try {
            const formats = await window.BarcodeDetector.getSupportedFormats();
            const supportedFormats = formats.filter((format) => ['qr_code', 'code_128'].includes(format));
            if (supportedFormats.length === 0) {
                scannerStatus.textContent = 'Browser tidak mendukung QR atau Code 128. Ketik nomor kartu secara manual.';
                return;
            }

            const detector = new window.BarcodeDetector({ formats: supportedFormats });
            scannerStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            scannerVideo.srcObject = scannerStream;
            await scannerVideo.play();
            scanning = true;
            scannerStatus.textContent = 'Arahkan QR atau barcode kartu anggota ke kamera.';

            const scanFrame = async () => {
                if (!scanning) return;
                try {
                    const codes = await detector.detect(scannerVideo);
                    const code = codes.find((item) => item.format === 'qr_code' || item.format === 'code_128');
                    if (code) {
                        scannerInput.value = code.rawValue;
                        scannerStatus.textContent = `Kartu ${code.rawValue} berhasil dipindai.`;
                        stopScanner();
                        return;
                    }
                    scannerFrame = requestAnimationFrame(scanFrame);
                } catch (error) {
                    scannerStatus.textContent = 'Pemindaian gagal. Ketik nomor kartu secara manual.';
                    console.error('Pemindaian barcode gagal.', error);
                    stopScanner();
                }
            };

            scannerFrame = requestAnimationFrame(scanFrame);
        } catch (error) {
            scannerStatus.textContent = error.name === 'NotAllowedError'
                ? 'Akses kamera ditolak. Izinkan kamera atau ketik nomor kartu secara manual.'
                : 'Kamera tidak dapat dibuka. Ketik nomor kartu secara manual.';
            console.error('Kamera pemindai tidak dapat diakses.', error);
            scannerStream?.getTracks().forEach((track) => track.stop());
            scannerStream = null;
            if (scannerVideo) scannerVideo.srcObject = null;
            scanning = false;
        }
    });

    stopScannerButton?.addEventListener('click', stopScanner);
    window.addEventListener('pagehide', stopScanner);
});

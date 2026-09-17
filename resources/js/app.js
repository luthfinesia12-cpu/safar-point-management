const shell = document.querySelector('[data-app-shell]');

document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => shell?.classList.toggle('sidebar-open'));
});

document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const group = button.closest('[data-nav-group]');
        const isOpen = group.classList.toggle('open');
        button.setAttribute('aria-expanded', String(isOpen));
    });
});

document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.dataset.modalOpen);
        modal?.classList.add('open');
        document.body.style.overflow = 'hidden';
        modal?.querySelector('input, select, textarea')?.focus();
    });
});

document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('.modal')?.classList.remove('open');
        document.body.style.overflow = '';
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal.open').forEach((modal) => modal.classList.remove('open'));
        shell?.classList.remove('sidebar-open');
        document.body.style.overflow = '';
    }
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        document.querySelector('[data-global-search]')?.focus();
    }
});

document.querySelectorAll('[data-dismiss-alert]').forEach((button) => {
    button.addEventListener('click', () => button.closest('.alert')?.remove());
});

const globalSearch = document.querySelector('[data-global-search]');
globalSearch?.addEventListener('input', () => {
    const query = globalSearch.value.trim().toLowerCase();
    document.querySelectorAll('tbody tr').forEach((row) => {
        row.hidden = query !== '' && !row.textContent.toLowerCase().includes(query);
    });
});

document.querySelectorAll('[data-table-search]').forEach((input) => {
    input.addEventListener('input', () => {
        const target = document.querySelector(input.dataset.tableSearch);
        const query = input.value.trim().toLowerCase();
        target?.querySelectorAll('tbody tr').forEach((row) => {
            row.hidden = query !== '' && !row.textContent.toLowerCase().includes(query);
        });
    });
});

document.querySelector('[data-pr-select]')?.addEventListener('change', (event) => {
    const product = event.target.selectedOptions[0]?.dataset.product ?? '';
    const input = document.querySelector('[data-pr-product]');
    if (input) input.value = product;
});

document.querySelector('[data-po-select]')?.addEventListener('change', (event) => {
    const product = event.target.selectedOptions[0]?.dataset.product ?? '';
    const input = document.querySelector('[data-po-product]');
    if (input) input.value = product;
});

const scannerModal = document.getElementById('sku-scanner');

if (scannerModal) {
    const video = scannerModal.querySelector('[data-scan-video]');
    const viewport = scannerModal.querySelector('.scanner-viewport');
    const manualInput = scannerModal.querySelector('[data-scan-manual]');
    const status = scannerModal.querySelector('[data-scan-status]');
    const cameraButton = scannerModal.querySelector('[data-scan-camera]');
    const lookupUrl = scannerModal.dataset.scanLookupUrl;
    let activeTrigger = null;
    let mediaStream = null;
    let scanTimer = null;
    let processing = false;

    const setStatus = (message, type = '') => {
        status.textContent = message;
        status.className = `scanner-status ${type}`.trim();
    };

    const stopCamera = () => {
        if (scanTimer) window.clearInterval(scanTimer);
        scanTimer = null;
        mediaStream?.getTracks().forEach((track) => track.stop());
        mediaStream = null;
        video.srcObject = null;
        viewport.classList.remove('camera-active');
        cameraButton.textContent = 'Aktifkan Kamera';
    };

    const closeScanner = () => {
        stopCamera();
        scannerModal.classList.remove('open');
        scannerModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        activeTrigger = null;
        processing = false;
    };

    const selectRelatedDocument = (select, productId) => {
        const option = [...select.options].find((item) => {
            const products = (item.dataset.products || '').split(',').filter(Boolean);
            return products.includes(String(productId));
        });

        if (!option) throw new Error('Produk ditemukan, tetapi tidak tersedia pada dokumen yang dapat dipilih.');
        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const applyCode = async (rawCode) => {
        const code = rawCode.trim();
        if (!code || !activeTrigger || processing) return;
        processing = true;
        setStatus(`Memeriksa ${code}...`);

        try {
            const target = document.querySelector(activeTrigger.dataset.scanTarget);
            if (!target) throw new Error('Kolom tujuan scanner tidak ditemukan.');

            const mode = activeTrigger.dataset.scanMode || 'input';
            if (mode === 'input') {
                target.value = code;
                target.dispatchEvent(new Event('input', { bubbles: true }));
                target.dispatchEvent(new Event('change', { bubbles: true }));
                setStatus(`Kode ${code} berhasil dimasukkan.`, 'success');
            } else {
                const response = await fetch(`${lookupUrl}?code=${encodeURIComponent(code)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Produk tidak ditemukan.');

                if (mode === 'product') {
                    target.value = String(payload.product.id);
                    if (!target.value) throw new Error('Produk ditemukan, tetapi tidak tersedia pada pilihan ini.');
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    selectRelatedDocument(target, payload.product.id);
                }
                setStatus(`${payload.product.sku} — ${payload.product.name} ditemukan.`, 'success');
            }

            stopCamera();
            window.setTimeout(closeScanner, 650);
        } catch (error) {
            setStatus(error.message, 'error');
            manualInput.select();
        } finally {
            processing = false;
        }
    };

    const startCamera = async () => {
        if (mediaStream) {
            stopCamera();
            setStatus('Kamera dihentikan. Scanner fisik tetap dapat digunakan.');
            return;
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Browser ini tidak mendukung akses kamera. Gunakan scanner fisik atau input manual.', 'error');
            return;
        }
        if (!('BarcodeDetector' in window)) {
            setStatus('Pemindaian kamera belum didukung browser ini. Gunakan Chrome terbaru, scanner fisik, atau input manual.', 'error');
            return;
        }

        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } },
                audio: false,
            });
            video.srcObject = mediaStream;
            await video.play();
            viewport.classList.add('camera-active');
            cameraButton.textContent = 'Matikan Kamera';
            setStatus('Kamera aktif. Arahkan kode ke dalam bingkai.');

            const formats = await window.BarcodeDetector.getSupportedFormats();
            const detector = new window.BarcodeDetector({ formats });
            scanTimer = window.setInterval(async () => {
                if (processing || video.readyState < 2) return;
                try {
                    const codes = await detector.detect(video);
                    if (codes[0]?.rawValue) await applyCode(codes[0].rawValue);
                } catch (_) {
                    // Frame berikutnya akan dicoba kembali.
                }
            }, 350);
        } catch (error) {
            stopCamera();
            setStatus('Kamera tidak dapat dibuka. Izinkan akses kamera atau gunakan scanner fisik.', 'error');
        }
    };

    document.querySelectorAll('[data-scan-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            activeTrigger = trigger;
            manualInput.value = '';
            setStatus('Siap menerima SKU atau barcode.');
            scannerModal.classList.add('open');
            scannerModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            window.setTimeout(() => manualInput.focus(), 80);
        });
    });

    scannerModal.querySelectorAll('[data-scan-close]').forEach((button) => button.addEventListener('click', closeScanner));
    scannerModal.querySelector('[data-scan-submit]').addEventListener('click', () => applyCode(manualInput.value));
    cameraButton.addEventListener('click', startCamera);
    manualInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyCode(manualInput.value);
        }
    });
}

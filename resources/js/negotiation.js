document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('[data-nego-dialog]');
    const content = dialog?.querySelector('[data-nego-dialog-content]');

    if (!dialog || !content) {
        return;
    }

    const openDialog = async (url) => {
        content.innerHTML = '<div class="rounded-[24px] bg-white p-8 text-center text-[14px] text-[#718096]">Memuat formulir nego...</div>';
        if (!dialog.open) {
            dialog.showModal();
        }

        try {
            const response = await window.axios.get(url, {
                headers: { Accept: 'text/html' },
            });
            content.innerHTML = response.data;
        } catch (error) {
            const message = error.response?.status === 403
                ? 'Kamu tidak memiliki akses untuk menawar barang ini.'
                : 'Formulir nego gagal dimuat. Silakan coba lagi.';
            content.innerHTML = `
                <div class="rounded-[24px] bg-white p-8 text-center">
                    <p class="text-[14px] text-[#a33c3c]">${message}</p>
                    <div class="mt-5 flex justify-center gap-2">
                        <button type="button" data-nego-retry class="rounded-[8px] bg-[#00523f] px-4 py-2 text-[12px] font-semibold text-white">Coba lagi</button>
                        <button type="button" data-nego-close class="rounded-[8px] border border-[#dce3e6] px-4 py-2 text-[12px] font-semibold text-[#526170]">Tutup</button>
                    </div>
                </div>
            `;
            console.error('Gagal memuat formulir nego.', error);
        }
    };

    document.querySelectorAll('[data-nego-open]').forEach((button) => {
        button.addEventListener('click', () => openDialog(button.dataset.negoUrl));
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
            return;
        }

        if (event.target instanceof Element && event.target.closest('[data-nego-close]')) {
            dialog.close();
            return;
        }

        if (event.target instanceof Element && event.target.closest('[data-nego-retry]')) {
            openDialog(dialog.dataset.negoUrl);
        }
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const discountButton = event.target.closest('[data-offer-discount]');
        if (!discountButton || !dialog.contains(discountButton)) {
            return;
        }

        const priceInput = dialog.querySelector('[data-offer-price]');
        const basePrice = Number(priceInput?.dataset.productPrice);
        const minimumOffer = Number(priceInput?.dataset.minimumOffer);
        const discount = Number(discountButton.dataset.offerDiscount);

        if (
            !priceInput
            || !Number.isFinite(basePrice)
            || !Number.isFinite(minimumOffer)
            || !Number.isFinite(discount)
        ) {
            return;
        }

        priceInput.value = String(Math.max(
            minimumOffer,
            Math.round(basePrice * (100 - discount) / 100),
        ));
        priceInput.dispatchEvent(new Event('input', { bubbles: true }));
    });

    if (dialog.dataset.negoAutoOpen === 'true') {
        openDialog(dialog.dataset.negoUrl);
    }
});

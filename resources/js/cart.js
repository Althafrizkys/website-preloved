const formatRupiah = (value) => 'Rp' + new Intl.NumberFormat('id-ID').format(value);

const initCartPage = () => {
    const page = document.querySelector('[data-cart-page]');
    if (!page) {
        return;
    }

    const items = Array.from(document.querySelectorAll('[data-cart-item]'));
    const checkboxes = items.map((item) => item.querySelector('[data-cart-checkbox]'));
    const selectAllBoxes = Array.from(document.querySelectorAll('[data-cart-select-all]'));
    const groupCount = document.querySelector('[data-cart-group-count]');
    const summary = document.querySelector('[data-cart-selected-summary]');
    const totalLabel = document.querySelector('[data-cart-total]');
    const checkoutForm = document.querySelector('[data-cart-checkout-form]');
    const checkoutButton = document.querySelector('[data-cart-checkout-button]');
    const deleteForm = document.querySelector('[data-cart-delete-selected]');
    const deleteButton = document.querySelector('[data-cart-delete-selected-button]');
    const sellerNotice = document.querySelector('[data-cart-seller-notice]');

    const selectedItems = () => items.filter((item) => item.querySelector('[data-cart-checkbox]')?.checked);

    // Grup aktif = penjual dari barang yang dicentang, atau penjual pertama yang punya barang tersedia.
    const activeSellerId = () => {
        const selected = selectedItems()[0];
        if (selected) {
            return selected.dataset.sellerId;
        }
        const firstAvailable = items.find((item) => !item.querySelector('[data-cart-checkbox]')?.disabled);
        return firstAvailable?.dataset.sellerId ?? null;
    };

    const activeGroupBoxes = () => {
        const sellerId = activeSellerId();
        return items
            .filter((item) => item.dataset.sellerId === sellerId)
            .map((item) => item.querySelector('[data-cart-checkbox]'))
            .filter((box) => box && !box.disabled);
    };

    const refresh = () => {
        const selected = selectedItems();
        const units = selected.reduce((sum, item) => sum + Number(item.dataset.quantity || 0), 0);
        const total = selected.reduce((sum, item) => sum + Number(item.dataset.unitPrice || 0) * Number(item.dataset.quantity || 0), 0);
        const groupBoxes = activeGroupBoxes();
        const allChecked = groupBoxes.length > 0 && groupBoxes.every((box) => box.checked);
        const someChecked = selected.length > 0 && !allChecked;

        selectAllBoxes.forEach((box) => {
            box.checked = allChecked;
            box.indeterminate = someChecked;
        });

        if (groupCount) {
            groupCount.textContent = String(groupBoxes.length);
        }
        if (summary) {
            summary.textContent = `${selected.length} produk dipilih (${units} unit)`;
        }
        if (totalLabel) {
            totalLabel.textContent = formatRupiah(total);
        }
        if (checkoutButton) {
            checkoutButton.disabled = selected.length === 0;
        }
        if (deleteButton) {
            deleteButton.disabled = selected.length === 0;
        }
    };

    // Aturan: hanya satu penjual per checkout. Centang penjual lain melepas pilihan sebelumnya.
    checkboxes.forEach((box) => {
        box?.addEventListener('change', () => {
            if (box.checked) {
                const sellerId = box.closest('[data-cart-item]')?.dataset.sellerId;
                let released = false;
                checkboxes.forEach((other) => {
                    if (other && other !== box && other.checked && other.closest('[data-cart-item]')?.dataset.sellerId !== sellerId) {
                        other.checked = false;
                        released = true;
                    }
                });
                sellerNotice?.classList.toggle('hidden', !released);
            }
            refresh();
        });
    });

    selectAllBoxes.forEach((selectAll) => {
        selectAll.addEventListener('change', () => {
            const groupBoxes = activeGroupBoxes();
            checkboxes.forEach((box) => {
                if (box) {
                    box.checked = false;
                }
            });
            groupBoxes.forEach((box) => {
                box.checked = selectAll.checked;
            });
            refresh();
        });
    });

    const appendSelectedIds = (form) => {
        form.querySelectorAll('input[data-cart-selected-id]').forEach((input) => input.remove());
        selectedItems().forEach((item) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'items[]';
            input.value = item.dataset.itemId;
            input.dataset.cartSelectedId = '';
            form.appendChild(input);
        });
    };

    checkoutForm?.addEventListener('submit', (event) => {
        if (selectedItems().length === 0) {
            event.preventDefault();
            return;
        }
        appendSelectedIds(checkoutForm);
    });

    deleteForm?.addEventListener('submit', (event) => {
        if (selectedItems().length === 0) {
            event.preventDefault();
            return;
        }
        appendSelectedIds(deleteForm);
    });

    // Ubah jumlah tanpa memuat ulang halaman (pilihan centang tetap).
    document.querySelectorAll('[data-cart-qty-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            const button = event.submitter;
            const item = form.closest('[data-cart-item]');
            if (!button || !item || !window.axios) {
                return;
            }
            event.preventDefault();

            const nextQuantity = Number(item.dataset.quantity) + Number(button.dataset.cartQtyStep || 0);
            item.querySelectorAll('[data-cart-qty-step]').forEach((stepButton) => {
                stepButton.disabled = true;
            });

            try {
                const { data } = await window.axios.patch(form.action, { quantity: nextQuantity }, {
                    headers: { Accept: 'application/json' },
                });
                item.dataset.quantity = String(data.quantity);
                item.querySelector('[data-cart-qty]').textContent = String(data.quantity);
                item.querySelector('[data-cart-line-total]').textContent = formatRupiah(Number(item.dataset.unitPrice) * data.quantity);
                item.querySelectorAll('[data-cart-qty-step]').forEach((stepButton) => {
                    const step = Number(stepButton.dataset.cartQtyStep);
                    stepButton.value = String(data.quantity + step);
                    stepButton.disabled = step < 0 ? data.quantity <= 1 : data.quantity >= data.stock;
                });
            } catch (error) {
                form.submit();
                return;
            }

            refresh();
        });
    });

    refresh();
};

// Halaman produk: kirim jumlah yang dipilih bersama tombol Keranjang.
const initProductCartButton = () => {
    const form = document.querySelector('[data-product-cart-form]');
    if (!form) {
        return;
    }

    form.addEventListener('submit', () => {
        const quantityInput = document.querySelector('[data-product-quantity]');
        const hidden = form.querySelector('[data-product-cart-quantity]');
        if (quantityInput && hidden) {
            hidden.value = quantityInput.value || '1';
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initCartPage();
    initProductCartButton();
});

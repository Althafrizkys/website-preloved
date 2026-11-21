document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-checkout-form]');
    if (!form) {
        return;
    }

    const unitPrice = Number(form.dataset.unitPrice || 0);
    const serviceFee = Number(form.dataset.serviceFee || 2000);

    const campusInput = form.querySelector('#selected_campus');
    const campusButtons = form.querySelectorAll('[data-campus-btn]');
    const meetingPointSelect = form.querySelector('#meeting_point_id');

    const qtyInput = form.querySelector('[data-qty-input]');
    const qtyMinus = form.querySelector('[data-qty-minus]');
    const qtyPlus = form.querySelector('[data-qty-plus]');

    const itemSubtotal = form.querySelector('[data-item-subtotal]');
    const summarySubtotal = form.querySelector('[data-summary-subtotal]');
    const summaryTotal = form.querySelector('[data-summary-total]');

    const formatRupiah = (number) => {
        return 'Rp' + new Intl.NumberFormat('id-ID').format(number);
    };

    // Logika Pemilihan Kampus & Filter Titik Temu
    const switchCampus = (campus) => {
        if (campusInput) {
            campusInput.value = campus;
        }

        campusButtons.forEach((btn) => {
            if (btn.dataset.campusBtn === campus) {
                btn.className = 'flex-1 rounded-[12px] py-2.5 text-center text-[14px] font-bold transition bg-[#00523f] text-white';
            } else {
                btn.className = 'flex-1 rounded-[12px] py-2.5 text-center text-[14px] font-bold transition bg-white border border-[#e5e9ed] text-[#526170] hover:border-[#00523f]';
            }
        });

        if (meetingPointSelect) {
            let firstMatchedOption = null;
            Array.from(meetingPointSelect.options).forEach((option) => {
                const match = option.dataset.campus === campus;
                option.hidden = !match;
                option.disabled = !match;
                if (match && !firstMatchedOption) {
                    firstMatchedOption = option;
                }
            });

            // Jika opsi yang terpilih sebelumnya tidak cocok dengan kampus baru, ganti ke opsi pertama yang cocok
            const selectedOpt = meetingPointSelect.selectedOptions[0];
            if (!selectedOpt || selectedOpt.dataset.campus !== campus) {
                if (firstMatchedOption) {
                    firstMatchedOption.selected = true;
                }
            }
        }
    };

    campusButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            switchCampus(btn.dataset.campusBtn);
        });
    });

    // Jalankan filter awal kampus
    if (campusInput) {
        switchCampus(campusInput.value || 'UNSIKA');
    }

    // Logika Pengubahan Jumlah & Hitung Ulang Total
    const updatePrices = () => {
        if (!qtyInput) return;
        let qty = parseInt(qtyInput.value, 10);
        if (isNaN(qty) || qty < 1) {
            qty = 1;
        } else if (qty > 10) {
            qty = 10;
        }
        qtyInput.value = qty;

        const subtotal = unitPrice * qty;
        const total = subtotal + serviceFee;

        if (itemSubtotal) {
            itemSubtotal.textContent = formatRupiah(subtotal);
        }
        if (summarySubtotal) {
            summarySubtotal.textContent = formatRupiah(subtotal);
        }
        if (summaryTotal) {
            summaryTotal.textContent = formatRupiah(total);
        }
    };

    if (qtyMinus && qtyInput) {
        qtyMinus.addEventListener('click', () => {
            let cur = parseInt(qtyInput.value, 10) || 1;
            if (cur > 1) {
                qtyInput.value = cur - 1;
                updatePrices();
            }
        });
    }

    if (qtyPlus && qtyInput) {
        qtyPlus.addEventListener('click', () => {
            let cur = parseInt(qtyInput.value, 10) || 1;
            if (cur < 10) {
                qtyInput.value = cur + 1;
                updatePrices();
            }
        });
    }

    if (qtyInput) {
        qtyInput.addEventListener('input', updatePrices);
        qtyInput.addEventListener('change', updatePrices);
    }
});

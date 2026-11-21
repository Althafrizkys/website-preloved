document.addEventListener('DOMContentLoaded', () => {
    const dialogs = document.querySelectorAll('[data-notification-dialog]');
    const triggers = document.querySelectorAll('[data-notification-trigger]');

    // Sinkronisasi auto-open jika ada parameter ?notifikasi=open di URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('notifikasi') === 'open' && dialogs.length > 0) {
        dialogs[0].showModal();
    }

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            dialogs.forEach((dialog) => {
                if (!dialog.open) {
                    dialog.showModal();
                }
            });
        });
    });

    document.querySelectorAll('[data-notification-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = button.closest('dialog');
            if (dialog) {
                dialog.close();
            }
        });
    });

    dialogs.forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });

    // Inisialisasi logika filter dan aksi notifikasi pada setiap kontainer notifikasi
    // (Bisa di dalam modal dialog atau halaman /notifikasi)
    const contexts = [
        ...dialogs,
        document.querySelector('main .rounded-\\[24px\\]')
    ].filter(Boolean);

    contexts.forEach((context) => {
        let currentTab = 'semua';
        let currentRole = 'penjual';

        const tabButtons = context.querySelectorAll('[data-notification-tab]');
        const roleRadios = context.querySelectorAll('[data-notification-role-radio]');
        const items = context.querySelectorAll('[data-notification-item]');
        const emptyState = context.querySelector('[data-notification-empty]');
        const emptyInitial = context.querySelector('[data-notification-empty-initial]');

        const applyFilter = () => {
            let visibleCount = 0;

            items.forEach((item) => {
                const itemRole = item.dataset.role || 'penjual';
                const isRead = item.dataset.read === 'true';

                const matchesRole = itemRole === currentRole;
                const matchesTab = currentTab === 'semua' || (!isRead && currentTab === 'belum_dibaca');

                if (matchesRole && matchesTab) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            if (emptyInitial) {
                emptyInitial.classList.add('hidden');
            }

            if (emptyState) {
                if (visibleCount === 0) {
                    emptyState.classList.remove('hidden');
                } else {
                    emptyState.classList.add('hidden');
                }
            }
        };

        // Tab Switcher
        tabButtons.forEach((tabBtn) => {
            tabBtn.addEventListener('click', () => {
                currentTab = tabBtn.dataset.notificationTab;

                tabButtons.forEach((btn) => {
                    if (btn.dataset.notificationTab === currentTab) {
                        btn.className = 'flex-1 rounded-full py-1.5 text-center font-semibold bg-white text-[#00523f] shadow-sm transition';
                    } else {
                        btn.className = 'flex-1 rounded-full py-1.5 text-center font-medium text-[#5c6f68] hover:text-[#00523f] transition';
                    }
                });

                applyFilter();
            });
        });

        // Role Radio Switcher
        roleRadios.forEach((radio) => {
            radio.addEventListener('change', () => {
                if (radio.checked) {
                    currentRole = radio.value;

                    // Update UI custom radio
                    context.querySelectorAll('[data-radio-dot]').forEach((dot) => {
                        if (dot.dataset.radioDot === currentRole) {
                            dot.className = 'size-2 rounded-full bg-[#00523f] transition';
                            dot.parentElement.className = 'flex size-4 items-center justify-center rounded-full border-2 border-[#00523f] transition';
                        } else {
                            dot.className = 'size-2 rounded-full bg-transparent transition';
                            dot.parentElement.className = 'flex size-4 items-center justify-center rounded-full border-2 border-[#cbd5e1] transition';
                        }
                    });

                    context.querySelectorAll('[data-radio-label]').forEach((label) => {
                        if (label.dataset.radioLabel === currentRole) {
                            label.className = 'font-semibold text-[#00523f] transition';
                        } else {
                            label.className = 'text-[#526170] transition';
                        }
                    });

                    applyFilter();
                }
            });
        });

        // Jalankan filter awal
        applyFilter();
    });

    // Handle mark as read saat klik chat link
    document.querySelectorAll('[data-chat-link]').forEach((link) => {
        link.addEventListener('click', async (e) => {
            const notifId = link.dataset.notificationId;
            const item = link.closest('[data-notification-item]');
            if (!notifId || !item || item.dataset.read === 'true') {
                return;
            }

            try {
                if (window.axios) {
                    const response = await window.axios.patch(`/notifikasi/${notifId}/baca`);
                    item.dataset.read = 'true';
                    const dot = item.querySelector('[data-unread-dot]');
                    if (dot) dot.remove();

                    updateBadges(response.data?.unread_count);
                }
            } catch (err) {
                console.error('Gagal menandai notifikasi dibaca', err);
            }
        });
    });

    // Handle Tandai Semua Dibaca
    document.querySelectorAll('[data-mark-all-read]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                if (window.axios) {
                    const response = await window.axios.post('/notifikasi/baca-semua');
                    document.querySelectorAll('[data-notification-item]').forEach((item) => {
                        item.dataset.read = 'true';
                        const dot = item.querySelector('[data-unread-dot]');
                        if (dot) dot.remove();
                    });

                    updateBadges(response.data?.unread_count ?? 0);

                    // Re-filter context yang aktif
                    contexts.forEach((c) => {
                        const activeTabBtn = c.querySelector('[data-notification-tab="semua"]');
                        if (activeTabBtn) activeTabBtn.click();
                    });
                }
            } catch (err) {
                console.error('Gagal menandai semua dibaca', err);
            }
        });
    });

    function updateBadges(unreadCount) {
        document.querySelectorAll('[data-notification-badge]').forEach((badge) => {
            const count = Number(unreadCount ?? 0);
            if (count > 0) {
                badge.textContent = count > 9 ? '9+' : String(count);
                badge.classList.remove('hidden');
            } else {
                badge.textContent = '0';
                badge.classList.add('hidden');
            }
        });
    }
});

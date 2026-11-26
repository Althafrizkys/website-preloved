function formatRupiah(value) {
    const amount = Number(String(value).replace(/\D/g, ''));

    return `Rp ${new Intl.NumberFormat('id-ID').format(Number.isFinite(amount) ? amount : 0)}`;
}

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function openPhotoDraftDatabase() {
    return new Promise((resolve, reject) => {
        if (!window.indexedDB) {
            reject(new Error('Penyimpanan foto sementara tidak tersedia di browser ini.'));
            return;
        }

        const request = window.indexedDB.open('seklov-product-upload', 1);
        request.onupgradeneeded = () => {
            if (!request.result.objectStoreNames.contains('photo-drafts')) {
                request.result.createObjectStore('photo-drafts');
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error || new Error('Penyimpanan foto sementara gagal dibuka.'));
    });
}

async function savePhotoDraft(id, files) {
    const database = await openPhotoDraftDatabase();

    try {
        await new Promise((resolve, reject) => {
            const transaction = database.transaction('photo-drafts', 'readwrite');
            transaction.objectStore('photo-drafts').put(files, id);
            transaction.oncomplete = resolve;
            transaction.onerror = () => reject(transaction.error || new Error('Foto sementara gagal disimpan.'));
            transaction.onabort = () => reject(transaction.error || new Error('Penyimpanan foto sementara dibatalkan.'));
        });
    } finally {
        database.close();
    }
}

async function loadPhotoDraft(id) {
    const database = await openPhotoDraftDatabase();

    try {
        return await new Promise((resolve, reject) => {
            const transaction = database.transaction('photo-drafts', 'readonly');
            const request = transaction.objectStore('photo-drafts').get(id);
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error || new Error('Foto sementara gagal dipulihkan.'));
            transaction.oncomplete = () => database.close();
            transaction.onerror = () => reject(transaction.error || new Error('Foto sementara gagal dipulihkan.'));
        });
    } catch (error) {
        database.close();
        throw error;
    }
}

async function deletePhotoDraft(id) {
    const database = await openPhotoDraftDatabase();

    try {
        await new Promise((resolve, reject) => {
            const transaction = database.transaction('photo-drafts', 'readwrite');
            transaction.objectStore('photo-drafts').delete(id);
            transaction.oncomplete = resolve;
            transaction.onerror = () => reject(transaction.error || new Error('Foto sementara gagal dihapus.'));
            transaction.onabort = () => reject(transaction.error || new Error('Penghapusan foto sementara dibatalkan.'));
        });
    } finally {
        database.close();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-product-form]').forEach((form) => {
        const photosInput = form.querySelector('#photos');
        const videoInput = form.querySelector('#video');
        const photoPreview = form.querySelector('[data-new-photo-preview]');
        const videoPreview = form.querySelector('[data-new-video-preview]');
        const photoCount = form.querySelector('[data-photo-count]');
        const photoLimitWarning = form.querySelector('[data-photo-limit-warning]');
        const videoCount = form.querySelector('[data-video-count]');
        const maxPhotos = Number(form.dataset.maxPhotos || 5);
        const existingMediaList = form.querySelector('[data-existing-media-list]');
        const photoDraftError = form.querySelector('[data-photo-draft-error]');
        const photoDraftSessionKey = `seklov:product-photo-draft:${form.dataset.photoDraftScope}`;
        let photoDraftId = null;
        let photoSavePromise = Promise.resolve();
        let pendingPhotoSaves = 0;
        let photoSaveFailed = false;
        let waitingToSubmit = false;
        let submittingAfterSave = false;
        let photoUrls = [];
        let videoUrl = null;
        let selectedPhotos = Array.from(photosInput?.files ?? []);

        const showPhotoDraftError = (error) => {
            photoDraftError?.classList.remove('hidden');
            console.error('Foto produk sementara tidak dapat dipertahankan.', error);
        };

        const createPhotoDraftId = () => {
            if (!photoDraftId) {
                const draftId = window.crypto.randomUUID();
                window.sessionStorage.setItem(photoDraftSessionKey, draftId);
                photoDraftId = draftId;
            }

            return photoDraftId;
        };

        const persistPhotos = () => {
            pendingPhotoSaves++;
            photoSaveFailed = false;
            const files = [...selectedPhotos];

            photoSavePromise = photoSavePromise
                .then(() => savePhotoDraft(createPhotoDraftId(), files))
                .then(() => photoDraftError?.classList.add('hidden'))
                .catch((error) => {
                    photoSaveFailed = true;
                    showPhotoDraftError(error);
                })
                .finally(() => {
                    pendingPhotoSaves--;
                });
        };

        const updateCounts = () => {
            const existingMedia = Array.from(existingMediaList?.querySelectorAll('[data-existing-media]') ?? []);
            const existingPhotos = existingMedia.filter((item) => item.dataset.existingMediaType === 'foto');
            const existingVideos = existingMedia.filter((item) => item.dataset.existingMediaType === 'video');
            const newPhotos = Array.from(photosInput?.files ?? []);
            const newVideo = videoInput?.files?.[0];
            const combinedSize = [
                ...existingMedia.map((item) => Number(item.dataset.existingMediaSize || 0)),
                ...newPhotos.map((file) => file.size),
                ...(newVideo ? [newVideo.size] : []),
            ].reduce((total, size) => total + size, 0);

            if (photoCount) {
                photoCount.textContent = `${existingPhotos.length + newPhotos.length}/${maxPhotos} foto · Total media ${formatFileSize(combinedSize)}`;
            }
            if (videoCount) {
                videoCount.textContent = newVideo
                    ? `1 video baru · ${formatFileSize(newVideo.size)}`
                    : `${existingVideos.length ? '1 video tersimpan' : 'Belum ada video baru.'}`;
            }
        };

        const liveImage = document.querySelector('[data-live-image]');
        const livePlaceholder = document.querySelector('[data-live-placeholder]');
        const livePhotoThumbnails = document.querySelector('[data-live-photo-thumbnails]');
        const liveVideo = document.querySelector('[data-live-video]');
        let activeLivePhotoKey = null;

        const newPhotoKey = (file) => `new:${file.name}:${file.size}:${file.lastModified}`;

        const setLiveImage = (source) => {
            if (!liveImage) {
                return;
            }

            if (source) {
                liveImage.src = source;
                liveImage.classList.remove('hidden');
                livePlaceholder?.classList.add('hidden');
                return;
            }

            liveImage.removeAttribute('src');
            liveImage.classList.add('hidden');
            livePlaceholder?.classList.remove('hidden');
        };

        const renderLivePhotoGallery = (preferredKey = activeLivePhotoKey) => {
            const existingPhotos = Array.from(existingMediaList?.querySelectorAll('[data-existing-media-type="foto"]') ?? [])
                .map((item) => ({
                    key: `existing:${item.dataset.existingMedia}`,
                    source: item.querySelector('img')?.src,
                    alt: 'Foto produk tersimpan',
                }))
                .filter((photo) => photo.source);
            const newPhotos = selectedPhotos.map((file, index) => ({
                key: newPhotoKey(file),
                source: photoUrls[index],
                alt: `Foto produk baru ${index + 1}`,
            }));
            const photos = [...existingPhotos, ...newPhotos];

            if (photos.length === 0) {
                activeLivePhotoKey = null;
                livePhotoThumbnails?.replaceChildren();
                livePhotoThumbnails?.classList.add('hidden');
                livePhotoThumbnails?.classList.remove('flex');
                setLiveImage(null);
                return;
            }

            const activePhoto = photos.find((photo) => photo.key === preferredKey) ?? photos[0];
            activeLivePhotoKey = activePhoto.key;
            setLiveImage(activePhoto.source);

            if (!livePhotoThumbnails) {
                return;
            }

            livePhotoThumbnails.replaceChildren();
            livePhotoThumbnails.classList.toggle('hidden', photos.length < 2);
            livePhotoThumbnails.classList.toggle('flex', photos.length > 1);

            if (photos.length < 2) {
                return;
            }

            photos.forEach((photo, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('aria-label', `Tampilkan foto ${index + 1} dari ${photos.length}`);
                button.setAttribute('aria-pressed', String(photo.key === activePhoto.key));
                button.className = `size-12 shrink-0 overflow-hidden rounded-[7px] border-2 bg-white transition ${photo.key === activePhoto.key ? 'border-[#00523f] ring-2 ring-[#00523f]/20' : 'border-transparent opacity-70 hover:opacity-100'}`;
                const image = document.createElement('img');
                image.src = photo.source;
                image.alt = photo.alt;
                image.className = 'size-full object-cover';
                button.append(image);
                button.addEventListener('click', () => renderLivePhotoGallery(photo.key));
                livePhotoThumbnails.append(button);
            });
        };

        const setLiveVideo = (source) => {
            if (!liveVideo) {
                return;
            }

            if (source) {
                liveVideo.src = source;
                liveVideo.classList.remove('hidden');
                return;
            }

            const existingVideo = existingMediaList?.querySelector('[data-existing-media-type="video"] video');
            if (existingVideo) {
                liveVideo.src = existingVideo.src;
                liveVideo.classList.remove('hidden');
            } else {
                liveVideo.removeAttribute('src');
                liveVideo.classList.add('hidden');
            }
        };

        const syncPhotoInput = () => {
            const transfer = new DataTransfer();
            selectedPhotos.forEach((file) => transfer.items.add(file));
            if (photosInput) {
                photosInput.files = transfer.files;
            }
        };

        const renderPhotoPreviews = () => {
            photoUrls.forEach((url) => URL.revokeObjectURL(url));
            photoUrls = [];
            photoPreview?.replaceChildren();

            const files = [...selectedPhotos];
            files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                photoUrls.push(url);

                const item = document.createElement('div');
                item.className = 'relative size-[76px] overflow-hidden rounded-[8px] border border-[#dce3e6] bg-white';
                const image = document.createElement('img');
                image.src = url;
                image.alt = `Pratinjau foto ${index + 1}`;
                image.className = 'size-full object-cover';
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = '×';
                remove.setAttribute('aria-label', `Hapus foto ${index + 1}`);
                remove.className = 'absolute right-1 top-1 flex size-5 items-center justify-center rounded-full bg-white/95 text-[14px] leading-none text-[#8b3030] shadow';
                remove.addEventListener('click', () => {
                    selectedPhotos = files.filter((_, fileIndex) => fileIndex !== index);
                    syncPhotoInput();
                    renderPhotoPreviews();
                    updateCounts();
                    persistPhotos();
                });
                item.append(image, remove);
                photoPreview?.append(item);
            });

            if (files.length > 0 && files.length < maxPhotos) {
                const addMore = document.createElement('button');
                addMore.type = 'button';
                addMore.textContent = '+';
                addMore.setAttribute('aria-label', 'Tambah foto produk');
                addMore.title = 'Tambah foto produk';
                addMore.className = 'flex size-[76px] items-center justify-center rounded-[8px] border border-dashed border-[#b8c9c4] bg-white text-[30px] font-light leading-none text-[#00523f] transition hover:border-[#00523f] hover:bg-[#f2faf7]';
                addMore.addEventListener('click', () => photosInput?.click());
                photoPreview?.append(addMore);
            }

            if (files[0]) {
                renderLivePhotoGallery(newPhotoKey(files[0]));
            } else {
                renderLivePhotoGallery();
            }
        };

        const renderVideoPreview = () => {
            if (videoUrl) {
                URL.revokeObjectURL(videoUrl);
                videoUrl = null;
            }
            videoPreview?.replaceChildren();

            const file = videoInput?.files?.[0];
            if (file) {
                videoUrl = URL.createObjectURL(file);
                setLiveVideo(videoUrl);
                const wrapper = document.createElement('div');
                wrapper.className = 'relative';
                const video = document.createElement('video');
                video.src = videoUrl;
                video.controls = true;
                video.className = 'max-h-40 w-full rounded-[8px] bg-black';
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = 'Hapus video';
                remove.className = 'mt-2 rounded-[7px] border border-[#d6a0a0] px-3 py-1.5 text-[11px] font-semibold text-[#a33c3c]';
                remove.addEventListener('click', () => {
                    videoInput.value = '';
                    renderVideoPreview();
                });
                wrapper.append(video, remove);
                videoPreview?.append(wrapper);
            } else {
                setLiveVideo(null);
            }

            updateCounts();
        };

        photosInput?.addEventListener('change', () => {
            const selectedKeys = new Set(selectedPhotos.map((file) => `${file.name}:${file.size}:${file.lastModified}`));
            const newPhotos = Array.from(photosInput.files ?? []).filter((file) => {
                const key = `${file.name}:${file.size}:${file.lastModified}`;
                if (selectedKeys.has(key)) {
                    return false;
                }

                selectedKeys.add(key);
                return true;
            });
            const availableSlots = Math.max(0, maxPhotos - selectedPhotos.length);
            selectedPhotos = [...selectedPhotos, ...newPhotos.slice(0, availableSlots)];
            if (newPhotos.length > availableSlots && photoLimitWarning) {
                photoLimitWarning.textContent = availableSlots > 0
                    ? `Maksimal ${maxPhotos} foto. Hanya ${availableSlots} foto tambahan yang dimasukkan.`
                    : `Maksimal ${maxPhotos} foto. Foto tambahan tidak dimasukkan.`;
                photoLimitWarning.classList.remove('hidden');
            } else {
                photoLimitWarning?.classList.add('hidden');
            }
            syncPhotoInput();
            renderPhotoPreviews();
            updateCounts();
            persistPhotos();
        });
        videoInput?.addEventListener('change', renderVideoPreview);

        form.querySelectorAll('[data-remove-existing-media]').forEach((button) => {
            button.addEventListener('click', () => {
                const mediaId = button.dataset.removeExistingMedia;
                const mediaItem = form.querySelector(`[data-existing-media="${mediaId}"]`);
                if (!mediaItem || form.querySelector(`[data-delete-media-id="${mediaId}"]`)) {
                    return;
                }

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'delete_media[]';
                hiddenInput.value = mediaId;
                hiddenInput.dataset.deleteMediaId = mediaId;
                form.append(hiddenInput);
                mediaItem.remove();
                updateCounts();
                if (mediaItem.dataset.existingMediaType === 'foto') {
                    renderLivePhotoGallery();
                } else if (mediaItem.dataset.existingMediaType === 'video') {
                    setLiveVideo(videoUrl);
                }
            });
        });

        const bindPreview = (inputSelector, outputSelector, eventName, format = (value) => value) => {
            const input = form.querySelector(inputSelector);
            const output = document.querySelector(outputSelector);
            if (!input || !output) {
                return;
            }

            const update = () => {
                const value = input.value.trim();
                output.textContent = value ? format(value, input) : output.dataset.emptyText || '';
            };

            input.addEventListener(eventName, update);
            update();
        };

        bindPreview('[name="product_name"]', '[data-live-name]', 'input');
        bindPreview('[name="price"]', '[data-live-price]', 'input', formatRupiah);
        bindPreview('[name="condition"]', '[data-live-condition]', 'change');
        bindPreview('[name="description"]', '[data-live-description]', 'input');
        bindPreview('[name="size"]', '[data-live-size]', 'input');
        bindPreview('[name="usage_period"]', '[data-live-usage_period]', 'input');
        bindPreview('[name="defects"]', '[data-live-defects]', 'input');
        bindPreview('[name="stock"]', '[data-live-stock]', 'input', (value) => `Stok tersedia: ${new Intl.NumberFormat('id-ID').format(Number(value))} unit`);
        bindPreview('[name="is_negotiable"]', '[data-live-negotiable]', 'change', (value) => value === '1' ? 'Bisa nego' : 'Harga pas');
        bindPreview('[name="category_id"]', '[data-live-category]', 'change', (_, input) => input.selectedOptions[0]?.textContent || 'Kategori');

        renderLivePhotoGallery();

        form.addEventListener('submit', (event) => {
            if (submittingAfterSave) {
                submittingAfterSave = false;
                return;
            }

            if (photoSaveFailed) {
                event.preventDefault();
                return;
            }

            if (pendingPhotoSaves === 0 || waitingToSubmit) {
                return;
            }

            event.preventDefault();
            waitingToSubmit = true;
            const submitter = event.submitter;

            const submitWhenSaved = async () => {
                while (pendingPhotoSaves > 0) {
                    await photoSavePromise;
                }

                waitingToSubmit = false;
                if (photoSaveFailed) {
                    return;
                }

                submittingAfterSave = true;
                if (submitter) {
                    form.requestSubmit(submitter);
                } else {
                    form.requestSubmit();
                }
                submittingAfterSave = false;
            };

            submitWhenSaved();
        });

        const restoreOrClearPhotoDraft = async () => {
            try {
                const draftId = window.sessionStorage.getItem(photoDraftSessionKey);
                if (!draftId) {
                    return;
                }

                if (form.dataset.restorePhotos !== 'true') {
                    await deletePhotoDraft(draftId);
                    window.sessionStorage.removeItem(photoDraftSessionKey);
                    photoDraftId = null;
                    return;
                }

                photoDraftId = draftId;
                if (photosInput) {
                    photosInput.disabled = true;
                }

                const files = await loadPhotoDraft(draftId);
                selectedPhotos = files;
                const transfer = new DataTransfer();
                files.forEach((file) => transfer.items.add(file));
                if (photosInput) {
                    photosInput.files = transfer.files;
                }
                renderPhotoPreviews();
                updateCounts();
            } catch (error) {
                showPhotoDraftError(error);
            } finally {
                if (photosInput) {
                    photosInput.disabled = false;
                }
            }
        };

        restoreOrClearPhotoDraft();
        updateCounts();
    });

    const productQuantity = document.querySelector('[data-product-quantity]');
    const buyLink = document.querySelector('[data-product-buy-link]');
    if (productQuantity && buyLink) {
        const updateProductQuantity = (clamp = false) => {
            const minimum = Number(productQuantity.min || 1);
            const maximum = Number(productQuantity.max || 1);
            const parsed = Number.parseInt(productQuantity.value, 10);

            if (Number.isInteger(parsed) && parsed >= minimum && parsed <= maximum) {
                const url = new URL(buyLink.href, window.location.href);
                url.searchParams.set('quantity', String(parsed));
                buyLink.href = url.toString();
            } else if (clamp) {
                const quantity = Math.min(maximum, Math.max(minimum, Number.isInteger(parsed) ? parsed : minimum));
                productQuantity.value = String(quantity);
                const url = new URL(buyLink.href, window.location.href);
                url.searchParams.set('quantity', String(quantity));
                buyLink.href = url.toString();
            }
        };

        productQuantity.addEventListener('input', () => updateProductQuantity());
        productQuantity.addEventListener('change', () => updateProductQuantity(true));
        document.querySelector('[data-product-qty-minus]')?.addEventListener('click', () => {
            productQuantity.value = String(Math.max(Number(productQuantity.min || 1), Number(productQuantity.value || 1) - 1));
            updateProductQuantity();
        });
        document.querySelector('[data-product-qty-plus]')?.addEventListener('click', () => {
            productQuantity.value = String(Math.min(Number(productQuantity.max || 1), Number(productQuantity.value || 1) + 1));
            updateProductQuantity();
        });
        updateProductQuantity(true);
    }

    const galleryThumbs = Array.from(document.querySelectorAll('[data-gallery-thumb]'));
    galleryThumbs.forEach((button, index) => {
        button.addEventListener('click', () => {
            const image = document.querySelector('[data-gallery-main]');
            if (image && button.dataset.galleryThumb) {
                image.src = button.dataset.galleryThumb;
            }

            const counter = document.querySelector('[data-gallery-counter]');
            if (counter) {
                counter.textContent = `${index + 1}/${galleryThumbs.length} foto`;
            }

            galleryThumbs.forEach((thumb, thumbIndex) => {
                const isActive = thumbIndex === index;
                thumb.setAttribute('aria-pressed', String(isActive));
                thumb.classList.toggle('border-2', isActive);
                thumb.classList.toggle('border-[#00523f]', isActive);
                thumb.classList.toggle('border', !isActive);
                thumb.classList.toggle('border-[#e5e9ed]', !isActive);
            });
        });
    });
});

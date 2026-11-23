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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-product-form]').forEach((form) => {
        const photosInput = form.querySelector('#photos');
        const videoInput = form.querySelector('#video');
        const photoPreview = form.querySelector('[data-new-photo-preview]');
        const videoPreview = form.querySelector('[data-new-video-preview]');
        const photoCount = form.querySelector('[data-photo-count]');
        const videoCount = form.querySelector('[data-video-count]');
        const maxPhotos = Number(form.dataset.maxPhotos || 5);
        const existingMediaList = form.querySelector('[data-existing-media-list]');
        let photoUrls = [];
        let videoUrl = null;

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
        const liveVideo = document.querySelector('[data-live-video]');
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

            const existingImage = existingMediaList?.querySelector('[data-existing-media-type="foto"] img');
            if (existingImage) {
                liveImage.src = existingImage.src;
                liveImage.classList.remove('hidden');
                livePlaceholder?.classList.add('hidden');
            } else {
                liveImage.removeAttribute('src');
                liveImage.classList.add('hidden');
                livePlaceholder?.classList.remove('hidden');
            }
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

        const renderPhotoPreviews = () => {
            photoUrls.forEach((url) => URL.revokeObjectURL(url));
            photoUrls = [];
            photoPreview?.replaceChildren();

            const files = Array.from(photosInput?.files ?? []);
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
                    const transfer = new DataTransfer();
                    files.filter((_, fileIndex) => fileIndex !== index).forEach((remainingFile) => transfer.items.add(remainingFile));
                    photosInput.files = transfer.files;
                    renderPhotoPreviews();
                    updateCounts();
                });
                item.append(image, remove);
                photoPreview?.append(item);
            });

            if (files[0]) {
                setLiveImage(photoUrls[0]);
            } else {
                setLiveImage(null);
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
            renderPhotoPreviews();
            updateCounts();
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
                    setLiveImage(photoUrls[0] ?? null);
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

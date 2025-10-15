class AvatarUploader {
    constructor() {
        this.cropper = null;
        this.selectedFile = null;
        this.modal = null;
        this.maxSize = 5 * 1024 * 1024;
    // Minimum crop for images (px)
    this.MIN_CROP_IMG = 100;
        // Small tolerance to account for rounding/scale issues
        this.CROP_TOLERANCE = 1;
        this.init();
    }

    init() {
        const modalElement = document.getElementById('cropAvatarModal');
        if (modalElement) {
            this.modal = new bootstrap.Modal(modalElement);
            modalElement.addEventListener('hidden.bs.modal', () => this.cancelCrop());
        }

        this.bindEvents();
    }

    bindEvents() {
        const fileUploadArea = $('#universalUploadArea');
        const avatarInput = $('#universalFileInput');

        if (!fileUploadArea.length || !avatarInput.length) {
            return;
        }

        if (avatarInput.prop('disabled')) {
            fileUploadArea.attr('title', window.avatarUploadPhrases?.pleaseSelect || 'You cannot upload');
            return;
        }

        fileUploadArea.off('.avatarUpload');
        avatarInput.off('.avatarUpload');
        $('#uploadAvatar').off('.avatarUpload');

        fileUploadArea.on('click.avatarUpload', (e) => {
            e.preventDefault();
            e.stopPropagation();
            avatarInput[0].click();
        });

        avatarInput.on('change.avatarUpload', (e) => {
            const file = e.target.files[0];
            if (file) {
                this.handleFile(file);
            }
        });

        fileUploadArea.on('dragover.avatarUpload', function dragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        fileUploadArea.on('dragleave.avatarUpload', function dragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        fileUploadArea.on('drop.avatarUpload', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.removeClass('dragover');

            const file = e.originalEvent.dataTransfer?.files?.[0];
            if (file) {
                this.handleFile(file);
            } else {
                this.showError(window.avatarUploadPhrases?.pleaseSelect || 'Please select a file');
            }
        });

        $('#uploadAvatar').on('click.avatarUpload', () => {
            this.uploadAvatar();
        });

        // Обработка вставки из буфера обмена (Ctrl+V)
        $(document).on('paste.avatarUpload', (e) => {
            const items = e.originalEvent.clipboardData?.items;
            if (!items) return;

            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                if (item.type.indexOf('image') !== -1) {
                    const blob = item.getAsFile();
                    if (blob) {
                        this.handleFile(blob);
                    }
                    break;
                }
            }
        });
    }

    handleFile(file) {
        // Если это не изображение, пропускаем
        if (!file.type.startsWith('image/')) {
            return;
        }

        if (file.size > this.maxSize) {
            this.showError(window.avatarUploadPhrases?.fileTooLarge || 'File size must not exceed 5MB');
            return;
        }

        this.selectedFile = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            const dataUrl = e.target.result;
            const cropImage = document.getElementById('cropImage');
            if (!cropImage) {
                return;
            }

            // Validate image by loading into a temporary Image
            const tmpImg = new Image();
            let validated = false;

            tmpImg.onload = () => {
                // Check natural dimensions
                if (tmpImg.naturalWidth > 0 && tmpImg.naturalHeight > 0) {
                    validated = true;
                    // assign to actual img used by cropper
                    cropImage.src = dataUrl;

                    if (this.modal) {
                        this.modal.show();
                    }

                    setTimeout(() => {
                        if (this.cropper) {
                            this.cropper.destroy();
                        }

                        this.cropper = new Cropper(cropImage, {
                            aspectRatio: 1,
                            viewMode: 2,
                            dragMode: 'move',
                            autoCropArea: 1,
                            restore: false,
                            guides: true,
                            center: true,
                            highlight: false,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false,
                            minCropBoxWidth: 100,
                            minCropBoxHeight: 100,
                            preview: '.preview',
                            ready: () => $('#cropContainer').addClass('fade-in'),
                        });
                    }, 300);
                } else {
                    this.showError(window.avatarUploadPhrases?.fileReadError || 'Invalid image file');
                }
            };

            tmpImg.onerror = () => {
                if (!validated) {
                    this.showError(window.avatarUploadPhrases?.fileReadError || 'Invalid image file');
                }
            };

            // Start loading the dataUrl into tmpImg
            try {
                tmpImg.src = dataUrl;
            } catch (err) {
                this.showError(window.avatarUploadPhrases?.fileReadError || 'Invalid image file');
            }
        };

        reader.onerror = () => {
            this.showError(window.avatarUploadPhrases?.fileReadError || 'Failed to read file');
        };

        reader.readAsDataURL(file);
    }

    cancelCrop() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }

        this.selectedFile = null;
        $('#universalFileInput').val('');
        $('#cropContainer').removeClass('fade-in');
    }

    destroy() {
        $(document).off('.avatarUpload');
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
    }

    uploadAvatar() {
        if (!this.cropper || !this.selectedFile) {
            this.showError(window.avatarUploadPhrases?.pleaseSelect || 'Please select an image');
            return;
        }

        const cropData = this.cropper.getData(true);
        // Use rounded crop values and a small tolerance to avoid false negatives due to scaling/rounding
        // Compute real pixel size using cropper image data (natural size) to support scaled displays
        // Use displayed crop box size (in CSS/display pixels) to validate min/max
        const displayedW = Math.round(cropData.width);
        const displayedH = Math.round(cropData.height);
        const cropSizeDisplayed = Math.min(displayedW, displayedH);
        const maxCropImg = 1024; // allow up to 1024x1024 for images (display pixels)
        const minAllowed = this.MIN_CROP_IMG - this.CROP_TOLERANCE;
        const maxAllowed = maxCropImg + this.CROP_TOLERANCE;
        if (cropSizeDisplayed < minAllowed || cropSizeDisplayed > maxAllowed) {
            this.showError(window.avatarUploadPhrases?.minCropSize || `Select a square area between ${this.MIN_CROP_IMG}×${this.MIN_CROP_IMG} and ${maxCropImg}×${maxCropImg} pixels`);
            return;
        }

        const uploadBtn = $('#uploadAvatar');
        const originalText = uploadBtn.html();
        const uploadingText = window.avatarUploadPhrases?.uploading || 'Uploading...';
        uploadBtn.prop('disabled', true).html(`<i class="spinner-border spinner-border-sm me-2"></i>${uploadingText}`);

        const formData = new FormData();
        formData.append('avatar', this.selectedFile);
        formData.append('cropData', JSON.stringify(cropData));

        $.ajax({
            url: '/avatar/upload/process',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    uploadBtn.prop('disabled', false).html(originalText);
                    this.showError(response.message || 'Error uploading');
                }
            },
            error: (xhr) => {
                uploadBtn.prop('disabled', false).html(originalText);
                let errorMsg = 'An error occurred while uploading';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                this.showError(errorMsg);
            },
        });
    }

    showError(message) {
        noticeError(message);
    }
}

class VideoAvatarUploader {
    constructor() {
        this.videoUploadArea = $('#videoUploadArea');
        this.videoInput = $('#videoInput');
        this.videoElement = document.getElementById('videoPreview');
        this.videoModalElement = document.getElementById('videoCropModal');
        this.videoModal = this.videoModalElement ? new bootstrap.Modal(this.videoModalElement) : null;

        this.videoCropCanvas = document.getElementById('videoCropCanvas');
        this.avatarPreviewCanvas = document.getElementById('avatarPreviewCanvas');
        this.avatarPreviewVideo = document.getElementById('avatarPreviewVideo');

        // Кастомный двойной слайдер
        this.sliderContainer = document.getElementById('customDualSlider');
        this.sliderHandleStart = document.getElementById('sliderHandleStart');
        this.sliderHandleEnd = document.getElementById('sliderHandleEnd');
        this.sliderTrackActive = document.getElementById('sliderTrackActive');
        this.tooltipStart = document.getElementById('tooltipStart');
        this.tooltipEnd = document.getElementById('tooltipEnd');
        
        this.startTimeLabel = document.getElementById('startTimeLabel');
        this.endTimeLabel = document.getElementById('endTimeLabel');
        this.durationLabel = document.getElementById('durationLabel');

        this.cropSizeInfo = document.getElementById('cropSizeInfo');
        this.clipDurationInfo = document.getElementById('clipDurationInfo');
        this.clipRangeInfo = document.getElementById('clipRangeInfo');

        this.uploadButton = $('#uploadVideoAvatar');
        this.cropper = null;
        this.videoFile = null;
        this.objectUrl = null;
        this.previewInterval = null;
        this.duration = 0;
        this.originalButtonHtml = null;
        this.cropUpdateTimer = null;
        this.currentStartTime = 0;
        this.currentEndTime = 0;
        this._previewReadyHandler = null;
        this._previewTimeUpdateHandler = null;
        
        // Состояние слайдера
        this.isDragging = false;
        this.activeHandle = null;
        this.sliderRect = null;
        this.startValue = 0;
        this.endValue = 4;

        this.config = window.avatarVideoConfig || {};
        this.MIN_DURATION = this.config.minDuration ?? 1;
        this.MAX_DURATION = this.config.maxDuration ?? 6;
        this.MIN_CROP = this.config.minCrop ?? 100;
        this.MAX_CROP = this.config.maxCrop ?? 1024;
        this.MAX_FILE_SIZE = this.config.maxFileSize ?? (200 * 1024 * 1024);
        this.CROP_TOLERANCE = 1;

        if (this.videoModalElement) {
            this.videoModalElement.addEventListener('hidden.bs.modal', () => this.reset());
            this.videoModalElement.addEventListener('shown.bs.modal', () => {
                setTimeout(() => {
                    try {
                        if (this.cropper) {
                            this.cropper.destroy();
                            this.cropper = null;
                        }
                        this.initCropper();
                    } catch (err) {
                        console.warn('Cropper init skipped:', err);
                    }
                }, 60);
            });
        }

        this.bindEvents();
    }

    bindEvents() {
        const universalUploadArea = $('#universalUploadArea');
        const universalFileInput = $('#universalFileInput');

        if (!universalUploadArea.length || !universalFileInput.length) {
            return;
        }

        if (universalFileInput.prop('disabled')) {
            return;
        }

        universalUploadArea.off('.videoUpload');
        universalFileInput.off('.videoUpload');
        this.uploadButton.off('.videoUpload');

        // Обработка выбора файла (только видео)
        universalFileInput.on('change.videoUpload', (e) => {
            const file = e.target.files[0];
            if (file && this.isAllowedVideo(file)) {
                this.handleFile(file);
            }
        });

        // Drag & Drop
        universalUploadArea.on('drop.videoUpload', (e) => {
            const file = e.originalEvent.dataTransfer?.files?.[0];
            if (file && this.isAllowedVideo(file)) {
                this.handleFile(file);
            }
        });

        // Инициализация кастомного двойного слайдера
        this.initCustomSlider();

        this.uploadButton.on('click.videoUpload', () => {
            this.uploadVideo();
        });
    }

    initCustomSlider() {
        if (!this.sliderHandleStart || !this.sliderHandleEnd || !this.sliderContainer) {
            return;
        }

        // Обработка начала перетаскивания
        const handleMouseDown = (e, handle) => {
            e.preventDefault();
            this.isDragging = true;
            this.activeHandle = handle;
            this.sliderRect = this.sliderContainer.getBoundingClientRect();
            // Save previous values to detect expansion vs shrinking
            this.prevStartValue = this.startValue;
            this.prevEndValue = this.endValue;
            
            // Поднимаем активный handle выше
            if (handle === 'start') {
                this.sliderHandleStart.style.zIndex = '20';
                this.sliderHandleEnd.style.zIndex = '10';
            } else {
                this.sliderHandleEnd.style.zIndex = '20';
                this.sliderHandleStart.style.zIndex = '10';
            }
        };

        // Mouse events (only left button)
        this.sliderHandleStart.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return; // ignore non-left clicks
            handleMouseDown(e, 'start');
        });
        this.sliderHandleEnd.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return;
            handleMouseDown(e, 'end');
        });

        // Touch events
        this.sliderHandleStart.addEventListener('touchstart', (e) => {
            e.preventDefault();
            handleMouseDown(e.touches[0], 'start');
        }, { passive: false });

        this.sliderHandleEnd.addEventListener('touchstart', (e) => {
            e.preventDefault();
            handleMouseDown(e.touches[0], 'end');
        }, { passive: false });

        // Обработка перемещения
        const handleMove = (clientX) => {
            if (!this.isDragging || !this.activeHandle || !this.sliderRect) {
                return;
            }

            const x = clientX - this.sliderRect.left;
            const percent = Math.max(0, Math.min(1, x / this.sliderRect.width));
            const value = percent * this.duration;

            if (this.activeHandle === 'start') {
                // Proposed new start, clamped by hard limits (0 .. end - MIN_DURATION)
                let proposedStart = Math.max(0, Math.min(value, this.endValue - this.MIN_DURATION));

                // Determine if this movement would expand the clip (start moved left)
                const expanding = typeof this.prevStartValue === 'number' ? (proposedStart < this.prevStartValue) : (proposedStart < this.startValue);

                // If expanding beyond MAX_DURATION is attempted, block expansion (but allow shrinking)
                if (expanding) {
                    const allowedMinStart = Math.max(0, this.endValue - this.MAX_DURATION);
                    if (proposedStart < allowedMinStart) {
                        proposedStart = allowedMinStart; // block expansion past max length
                    }
                }

                this.startValue = proposedStart;
                this.prevStartValue = this.startValue;
            } else {
                // Proposed new end, clamped by hard limits (start + MIN_DURATION .. duration)
                let proposedEnd = Math.min(this.duration, Math.max(value, this.startValue + this.MIN_DURATION));

                // Determine if this movement would expand the clip (end moved right)
                const expanding = typeof this.prevEndValue === 'number' ? (proposedEnd > this.prevEndValue) : (proposedEnd > this.endValue);

                // If expanding beyond MAX_DURATION is attempted, block expansion (but allow shrinking)
                if (expanding) {
                    const allowedMaxEnd = Math.min(this.duration, this.startValue + this.MAX_DURATION);
                    if (proposedEnd > allowedMaxEnd) {
                        proposedEnd = allowedMaxEnd; // block expansion past max length
                    }
                }

                this.endValue = proposedEnd;
                this.prevEndValue = this.endValue;
            }

            // Ensure clip length still respects MAX_DURATION (fallback safety)
            const clipLength = this.endValue - this.startValue;
            if (clipLength > this.MAX_DURATION) {
                if (this.activeHandle === 'start') {
                    this.startValue = this.endValue - this.MAX_DURATION;
                } else {
                    this.endValue = this.startValue + this.MAX_DURATION;
                }
            }

            this.updateSliderUI();
        };

        document.addEventListener('mousemove', (e) => {
            if (this.isDragging) {
                handleMove(e.clientX);
            }
        });

        document.addEventListener('touchmove', (e) => {
            if (this.isDragging && e.touches.length > 0) {
                e.preventDefault();
                handleMove(e.touches[0].clientX);
            }
        }, { passive: false });

        // Обработка окончания перетаскивания
        const handleEnd = () => {
            if (this.isDragging) {
                this.isDragging = false;
                this.activeHandle = null;
                this.sliderRect = null;
                
                // Обновляем превью и кадр
                this.renderCurrentFrame();
                this.updatePreview();
            }
        };

        document.addEventListener('mouseup', handleEnd);
        document.addEventListener('touchend', handleEnd);
        document.addEventListener('touchcancel', handleEnd);

        // Start dragging by clicking on the track (left button) — supports press+hold to drag
        this.sliderContainer.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return; // only left button
            // ignore if clicked directly on handles — their handlers take over
            if (e.target === this.sliderHandleStart || e.target === this.sliderHandleEnd ||
                e.target.parentElement === this.sliderHandleStart || e.target.parentElement === this.sliderHandleEnd) {
                return;
            }

            // determine nearest handle and start dragging it
            const rect = this.sliderContainer.getBoundingClientRect();
            this.sliderRect = rect;
            const x = e.clientX - rect.left;
            const percent = Math.max(0, Math.min(1, x / rect.width));
            const value = percent * this.duration;

            const distToStart = Math.abs(value - this.startValue);
            const distToEnd = Math.abs(value - this.endValue);

            const chosen = (distToStart < distToEnd) ? 'start' : 'end';
            // initialize prev values
            this.prevStartValue = this.startValue;
            this.prevEndValue = this.endValue;

            // start drag
            this.isDragging = true;
            this.activeHandle = chosen;
            if (chosen === 'start') {
                this.sliderHandleStart.style.zIndex = '20';
                this.sliderHandleEnd.style.zIndex = '10';
            } else {
                this.sliderHandleEnd.style.zIndex = '20';
                this.sliderHandleStart.style.zIndex = '10';
            }

            // move immediately to clicked position but respect MAX_DURATION
            if (chosen === 'start') {
                let proposedStart = Math.max(0, Math.min(value, this.endValue - this.MIN_DURATION));
                const allowedMinStart = Math.max(0, this.endValue - this.MAX_DURATION);
                if (proposedStart < allowedMinStart) proposedStart = allowedMinStart;
                this.startValue = proposedStart;
                this.prevStartValue = this.startValue;
            } else {
                let proposedEnd = Math.min(this.duration, Math.max(value, this.startValue + this.MIN_DURATION));
                const allowedMaxEnd = Math.min(this.duration, this.startValue + this.MAX_DURATION);
                if (proposedEnd > allowedMaxEnd) proposedEnd = allowedMaxEnd;
                this.endValue = proposedEnd;
                this.prevEndValue = this.endValue;
            }

            this.updateSliderUI();
            // prevent text selection while dragging
            e.preventDefault();
        });

        // touchstart on track to begin dragging
        this.sliderContainer.addEventListener('touchstart', (e) => {
            if (!e.touches || e.touches.length === 0) return;
            const touch = e.touches[0];
            // ignore if started on handles (their touchstart takes over)
            if (touch.target === this.sliderHandleStart || touch.target === this.sliderHandleEnd) return;

            const rect = this.sliderContainer.getBoundingClientRect();
            this.sliderRect = rect;
            const x = touch.clientX - rect.left;
            const percent = Math.max(0, Math.min(1, x / rect.width));
            const value = percent * this.duration;

            const distToStart = Math.abs(value - this.startValue);
            const distToEnd = Math.abs(value - this.endValue);

            const chosen = (distToStart < distToEnd) ? 'start' : 'end';
            this.prevStartValue = this.startValue;
            this.prevEndValue = this.endValue;

            this.isDragging = true;
            this.activeHandle = chosen;
            if (chosen === 'start') {
                this.sliderHandleStart.style.zIndex = '20';
                this.sliderHandleEnd.style.zIndex = '10';
            } else {
                this.sliderHandleEnd.style.zIndex = '20';
                this.sliderHandleStart.style.zIndex = '10';
            }

            if (chosen === 'start') {
                let proposedStart = Math.max(0, Math.min(value, this.endValue - this.MIN_DURATION));
                const allowedMinStart = Math.max(0, this.endValue - this.MAX_DURATION);
                if (proposedStart < allowedMinStart) proposedStart = allowedMinStart;
                this.startValue = proposedStart;
                this.prevStartValue = this.startValue;
            } else {
                let proposedEnd = Math.min(this.duration, Math.max(value, this.startValue + this.MIN_DURATION));
                const allowedMaxEnd = Math.min(this.duration, this.startValue + this.MAX_DURATION);
                if (proposedEnd > allowedMaxEnd) proposedEnd = allowedMaxEnd;
                this.endValue = proposedEnd;
                this.prevEndValue = this.endValue;
            }

            this.updateSliderUI();
            e.preventDefault();
        }, { passive: false });

        // prevent right-click menu on slider
        this.sliderContainer.addEventListener('contextmenu', (e) => {
            e.preventDefault();
        });
    }

    updateSliderUI() {
        if (!this.sliderHandleStart || !this.sliderHandleEnd || !this.sliderTrackActive || !this.duration) {
            return;
        }

        const startPercent = (this.startValue / this.duration) * 100;
        const endPercent = (this.endValue / this.duration) * 100;

        // Позиционируем handles
        this.sliderHandleStart.style.left = `${startPercent}%`;
        this.sliderHandleEnd.style.left = `${endPercent}%`;

        // Обновляем активную часть трека
        this.sliderTrackActive.style.left = `${startPercent}%`;
        this.sliderTrackActive.style.width = `${endPercent - startPercent}%`;

        // Обновляем tooltips
        if (this.tooltipStart) {
            this.tooltipStart.textContent = `${this.startValue.toFixed(2)}s`;
        }
        if (this.tooltipEnd) {
            this.tooltipEnd.textContent = `${this.endValue.toFixed(2)}s`;
        }

        // Обновляем метки
        const clipDuration = this.endValue - this.startValue;
        
        const startText = (window.avatarVideoPhrases && window.avatarVideoPhrases.startLabel) ? window.avatarVideoPhrases.startLabel : 'Start';
        const endText = (window.avatarVideoPhrases && window.avatarVideoPhrases.endLabel) ? window.avatarVideoPhrases.endLabel : 'End';
        if (this.startTimeLabel) {
            this.startTimeLabel.innerHTML = `<i class="bi bi-play-circle me-1"></i>${startText}: <strong>${this.startValue.toFixed(2)}s</strong>`;
        }
        if (this.endTimeLabel) {
            this.endTimeLabel.innerHTML = `<i class="bi bi-stop-circle me-1"></i>${endText}: <strong>${this.endValue.toFixed(2)}s</strong>`;
        }
        if (this.durationLabel) {
            this.durationLabel.innerHTML = `<i class="bi bi-hourglass-split me-1"></i>${clipDuration.toFixed(2)}s`;
        }
        if (this.clipDurationInfo) {
            this.clipDurationInfo.textContent = `${clipDuration.toFixed(2)}s`;
        }
        if (this.clipRangeInfo) {
            this.clipRangeInfo.textContent = `${this.startValue.toFixed(2)}s - ${this.endValue.toFixed(2)}s`;
        }
    }

    isAllowedVideo(file) {
        const allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-matroska', 'video/x-msvideo'];
        if (allowedTypes.includes(file.type)) {
            return true;
        }
        const extension = (file.name || '').split('.').pop()?.toLowerCase();
        const allowedExtensions = ['mp4', 'webm', 'mov', 'mkv', 'avi'];
        return allowedExtensions.includes(extension);
    }

    handleFile(file) {
        if (file.size > this.MAX_FILE_SIZE) {
            this.showError(window.avatarVideoPhrases?.fileTooLarge || 'Video file is too large');
            return;
        }

        if (!this.isAllowedVideo(file)) {
            this.showError(window.avatarVideoPhrases?.invalid || 'Unsupported video format');
            return;
        }

        this.videoFile = file;
        this.prepareVideo(file);
    }

    prepareVideo(file) {
        if (!this.videoElement) {
            return;
        }

        if (this.objectUrl) {
            URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = null;
        }

        this.objectUrl = URL.createObjectURL(file);
        this.duration = 0;

        const onLoadedMetadata = () => {
            this.duration = this.videoElement.duration || 0;

            if (!Number.isFinite(this.duration) || this.duration <= 0) {
                this.showError(window.avatarVideoPhrases?.invalid || 'Unable to read video duration');
                return;
            }

            if (this.duration < this.MIN_DURATION) {
                this.showError(window.avatarVideoPhrases?.durationInvalid || 'Clip duration is too short');
                return;
            }

            // Устанавливаем начальные значения слайдера
            const defaultDuration = Math.min(4, Math.min(this.duration, this.MAX_DURATION));
            this.startValue = 0;
            this.endValue = defaultDuration;
            
            this.updateSliderUI();

            if (this.videoModal) {
                this.videoModal.show();
            }
        };

        const onVideoError = () => {
            this.showError(window.avatarVideoPhrases?.invalid || 'Failed to load video');
        };

        this.videoElement.addEventListener('loadedmetadata', onLoadedMetadata, { once: true });
        this.videoElement.addEventListener('error', onVideoError, { once: true });

        this.videoElement.pause();
        this.videoElement.src = this.objectUrl;
        this.videoElement.load();
    }

    initCropper() {
        if (!this.videoElement || !this.videoCropCanvas) {
            return;
        }

        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }

        const createCropper = () => {
            try {
                // Проверяем что canvas имеет содержимое перед созданием cropper
                if (this.videoCropCanvas.width === 0 || this.videoCropCanvas.height === 0) {
                    console.warn('Canvas has no dimensions, skipping cropper creation');
                    return;
                }
                
                this.cropper = new Cropper(this.videoCropCanvas, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'none',
                    autoCropArea: 0.7,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    minCropBoxWidth: 100,
                    minCropBoxHeight: 100,
                    background: false,
                    crop: (event) => this.onCropChange(event),
                    ready: () => {
                        this.updatePreview();
                    },
                });
            } catch (err) {
                console.error('Failed to create Cropper:', err);
            }
        };

        const initWithFrame = () => {
            // Даем видео время полностью инициализироваться
            if (this.videoElement.readyState < 2) {
                console.warn('Video not ready for frame rendering');
                return;
            }
            
            this.renderVideoFrame(0).then(() => {
                // Небольшая задержка перед созданием cropper для стабильности
                setTimeout(() => createCropper(), 100);
            }).catch((err) => {
                console.warn('Initial frame render failed:', err);
                // Не пытаемся создать cropper если рендеринг не удался
            });
        };

        if (this.videoElement.readyState >= 2) {
            initWithFrame();
        } else {
            const onReady = () => {
                // Дополнительная задержка для стабильности
                setTimeout(() => initWithFrame(), 100);
            };
            this.videoElement.addEventListener('loadeddata', onReady, { once: true });
        }
    }

    renderVideoFrame(time) {
        return new Promise((resolve, reject) => {
            if (!this.videoElement || !this.videoCropCanvas) {
                reject(new Error('Video element not ready'));
                return;
            }

            let attempts = 0;
            const MAX_ATTEMPTS = 12;
            const RETRY_MS = 60;

            const drawFrame = () => {
                try {
                    // Если видео ещё не готово — пробуем через небольшой интервал, но не вечно
                    if (this.videoElement.readyState < 2) {
                        attempts++;
                        if (attempts <= MAX_ATTEMPTS) {
                            setTimeout(drawFrame, RETRY_MS);
                            return;
                        } else {
                            reject(new Error('Video not ready'));
                            return;
                        }
                    }

                    const width = this.videoElement.videoWidth;
                    const height = this.videoElement.videoHeight;

                    if (!width || !height) {
                        attempts++;
                        if (attempts <= MAX_ATTEMPTS) {
                            setTimeout(drawFrame, RETRY_MS);
                            return;
                        } else {
                            reject(new Error('Video dimensions unavailable'));
                            return;
                        }
                    }

                    this.videoCropCanvas.width = width;
                    this.videoCropCanvas.height = height;
                    const ctx = this.videoCropCanvas.getContext('2d');
                    ctx.drawImage(this.videoElement, 0, 0, width, height);
                    resolve();
                } catch (err) {
                    reject(err);
                }
            };

            if (this.videoElement.readyState >= 2) {
                const onSeeked = () => {
                    // Небольшая пауза для стабильности перед отрисовкой кадра
                    setTimeout(() => drawFrame(), 50);
                };

                this.videoElement.addEventListener('seeked', onSeeked, { once: true });

                try {
                    const clamped = Math.max(0, Math.min(time, this.duration || 0));
                    this.videoElement.currentTime = clamped;
                } catch (err) {
                    this.videoElement.removeEventListener('seeked', onSeeked);
                    reject(err);
                }
            } else {
                const onReady = () => {
                    // Когда загрузка начнёт происходить — повторно вызываем renderVideoFrame,
                    // но чтобы избежать стековой рекурсии/гонок используем небольшой таймаут.
                    setTimeout(() => {
                        this.renderVideoFrame(time).then(resolve).catch(reject);
                    }, 20);
                };
                this.videoElement.addEventListener('loadeddata', onReady, { once: true });
                this.videoElement.addEventListener('canplay', onReady, { once: true });
            }
        });
    }
    renderCurrentFrame() {
        const time = this.startValue;
        this.renderVideoFrame(time).then(() => {
            if (this.cropper) {
                try {
                    // Try to preserve image-space crop data (more stable when canvas/image
                    // dimensions change). Use getData(true) which returns values in the
                    // natural image coordinate space, then restore via setData after replace.
                    let imgData = null;
                    try {
                        imgData = this.cropper.getData(true);
                    } catch (e) {
                        imgData = null;
                    }

                    this.cropper.replace(this.videoCropCanvas.toDataURL('image/png'));

                    if (imgData) {
                        // Give Cropper a short moment to initialize the new image before restoring
                        setTimeout(() => {
                            try {
                                // Attempt to set data in image coordinates. If the new image's
                                // natural size matches the old one (typical for video frames)
                                // this will restore exact crop position/size.
                                this.cropper.setData(imgData);
                            } catch (err) {
                                // Fallback: if setData fails (size mismatch or other), try
                                // to restore crop box relatively to canvas as a best-effort.
                                try {
                                    const canvasData = this.cropper.getCanvasData();
                                    const cropBox = imgData && canvasData ? {
                                        left: Math.round((imgData.x || 0) / (this.videoElement.videoWidth || 1) * (canvasData.width || 1)) + (canvasData.left || 0),
                                        top: Math.round((imgData.y || 0) / (this.videoElement.videoHeight || 1) * (canvasData.height || 1)) + (canvasData.top || 0),
                                        width: Math.round((imgData.width || 0) / (this.videoElement.videoWidth || 1) * (canvasData.width || 1)),
                                        height: Math.round((imgData.height || 0) / (this.videoElement.videoHeight || 1) * (canvasData.height || 1)),
                                    } : null;
                                    if (cropBox) {
                                        // Ensure fits
                                        if (cropBox.left + cropBox.width > canvasData.width) cropBox.left = Math.max(0, canvasData.width - cropBox.width);
                                        if (cropBox.top + cropBox.height > canvasData.height) cropBox.top = Math.max(0, canvasData.height - cropBox.height);
                                        this.cropper.setCropBoxData(cropBox);
                                    }
                                } catch (innerErr) {
                                    // ignore
                                }
                            }
                        }, 60);
                    }
                } catch (err) {
                    console.warn('Cropper replace failed:', err);
                }
            }
        }).catch((err) => {
            console.error('Error rendering frame:', err);
        });
    }

    onCropChange(event) {
        const width = Math.round(event.detail.width);
        const height = Math.round(event.detail.height);
        const size = Math.min(width, height);

        if (this.cropSizeInfo) {
            this.cropSizeInfo.textContent = `${size} × ${size} px`;
            this.cropSizeInfo.style.color = (size < this.MIN_CROP || size > this.MAX_CROP) ? '#dc3545' : '#198754';
        }

        if (this.cropUpdateTimer) {
            clearTimeout(this.cropUpdateTimer);
        }

        this.cropUpdateTimer = setTimeout(() => this.updatePreview(), 150);
    }

    updatePreview() {
        if (!this.cropper || !this.videoElement || !this.avatarPreviewVideo || !this.avatarPreviewCanvas) {
            return;
        }

        this.currentStartTime = this.startValue;
        this.currentEndTime = this.endValue;

        if (!Number.isFinite(this.currentStartTime)) {
            this.currentStartTime = 0;
        }
        if (!Number.isFinite(this.currentEndTime) || this.currentEndTime <= this.currentStartTime) {
            this.currentEndTime = Math.min(this.duration, this.currentStartTime + this.MIN_DURATION);
        }

        if (this.previewInterval) {
            clearInterval(this.previewInterval);
            this.previewInterval = null;
        }

        const canvas = this.avatarPreviewCanvas;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        const previewWidth = 250;
        const previewHeight = 250;
        canvas.width = previewWidth;
        canvas.height = previewHeight;
        ctx.clearRect(0, 0, previewWidth, previewHeight);

        if (this.avatarPreviewVideo.src !== this.objectUrl) {
            this.avatarPreviewVideo.src = this.objectUrl || '';
            if (this.objectUrl) {
                this.avatarPreviewVideo.load();
            }
        }

        const drawFrame = () => {
            if (!this.cropper) {
                return;
            }

            const sourceVideo = (this.avatarPreviewVideo.readyState >= 2) ? this.avatarPreviewVideo : this.videoElement;
            if (!sourceVideo || !sourceVideo.videoWidth || !sourceVideo.videoHeight) {
                return;
            }

            const cropData = this.cropper.getData(true);
            const currentTime = this.avatarPreviewVideo.currentTime;

            if (currentTime < this.currentStartTime || currentTime >= this.currentEndTime) {
                if (this.avatarPreviewVideo.readyState >= 2) {
                    try {
                        this.avatarPreviewVideo.currentTime = this.currentStartTime;
                    } catch (err) {
                        console.warn('Preview seek error:', err);
                    }
                }
                return;
            }

            try {
                const scaleX = this.videoCropCanvas.width ? (this.videoElement.videoWidth / this.videoCropCanvas.width) : 1;
                const scaleY = this.videoCropCanvas.height ? (this.videoElement.videoHeight / this.videoCropCanvas.height) : 1;

                const sx = Math.round(cropData.x * scaleX);
                const sy = Math.round(cropData.y * scaleY);
                const sw = Math.round(cropData.width * scaleX);
                const sh = Math.round(cropData.height * scaleY);

                ctx.clearRect(0, 0, previewWidth, previewHeight);
                ctx.drawImage(sourceVideo, sx, sy, sw, sh, 0, 0, previewWidth, previewHeight);
            } catch (err) {
                try {
                    ctx.clearRect(0, 0, previewWidth, previewHeight);
                    ctx.drawImage(sourceVideo, 0, 0, sourceVideo.videoWidth, sourceVideo.videoHeight, 0, 0, previewWidth, previewHeight);
                } catch (fallbackErr) {
                    console.warn('Preview draw error:', fallbackErr);
                }
            }
        };

        const startLoop = () => {
            if (this.previewInterval) {
                clearInterval(this.previewInterval);
            }

            drawFrame();

            const playPromise = this.avatarPreviewVideo.play();
            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.catch(() => {
                    this.avatarPreviewVideo.pause();
                });
            }

            this.previewInterval = setInterval(drawFrame, 33);
        };

        if (this._previewReadyHandler) {
            this.avatarPreviewVideo.removeEventListener('loadeddata', this._previewReadyHandler);
            this.avatarPreviewVideo.removeEventListener('canplay', this._previewReadyHandler);
            this._previewReadyHandler = null;
        }
        if (this._previewTimeUpdateHandler) {
            this.avatarPreviewVideo.removeEventListener('timeupdate', this._previewTimeUpdateHandler);
        }

        this._previewTimeUpdateHandler = () => {
            if (this.avatarPreviewVideo.currentTime >= this.currentEndTime) {
                try {
                    this.avatarPreviewVideo.currentTime = this.currentStartTime;
                } catch (err) {
                    console.warn('Preview loop seek error:', err);
                }
            }
        };
        this.avatarPreviewVideo.addEventListener('timeupdate', this._previewTimeUpdateHandler);

        if (this.avatarPreviewVideo.readyState >= 2) {
            startLoop();
        } else if (this.objectUrl) {
            this._previewReadyHandler = startLoop;
            this.avatarPreviewVideo.addEventListener('loadeddata', this._previewReadyHandler, { once: true });
            this.avatarPreviewVideo.addEventListener('canplay', this._previewReadyHandler, { once: true });
        }

        try {
            this.avatarPreviewVideo.currentTime = this.currentStartTime;
        } catch (err) {
            console.warn('Preview initial seek error:', err);
        }
    }

    setUploadingState(isUploading) {
        if (!this.uploadButton.length) {
            return;
        }

        if (isUploading) {
            if (!this.originalButtonHtml) {
                this.originalButtonHtml = this.uploadButton.html();
            }
            const text = window.avatarVideoPhrases?.uploading || 'Uploading...';
            this.uploadButton.prop('disabled', true).html(`<i class="spinner-border spinner-border-sm me-2"></i>${text}`);
        } else {
            this.uploadButton.prop('disabled', false);
            if (this.originalButtonHtml) {
                this.uploadButton.html(this.originalButtonHtml);
            }
        }
    }

    uploadVideo() {
        if (!this.cropper || !this.videoFile) {
            this.showError(window.avatarVideoPhrases?.select || 'Select a video first');
            return;
        }

        // Clamp start/end to valid numeric bounds and apply small tolerance for floating math
        const EPS = 0.001;
        let start = Number.isFinite(this.startValue) ? this.startValue : 0;
        let end = Number.isFinite(this.endValue) ? this.endValue : this.duration || 0;

        // Ensure within [0, duration]
        start = Math.max(0, Math.min(start, this.duration || 0));
        end = Math.max(0, Math.min(end, this.duration || 0));

        // Ensure minimum spacing
        if (end <= start) {
            end = Math.min(this.duration || 0, start + this.MIN_DURATION);
        }

        let clipDuration = end - start;
        // Normalize small floating errors
        if (Math.abs(clipDuration - this.MAX_DURATION) <= EPS) clipDuration = this.MAX_DURATION;
        if (Math.abs(clipDuration - this.MIN_DURATION) <= EPS) clipDuration = this.MIN_DURATION;

        if (clipDuration + EPS < this.MIN_DURATION || clipDuration - EPS > this.MAX_DURATION) {
            this.showError(window.avatarVideoPhrases?.durationInvalid || 'Clip duration must be between 1 and 6 seconds');
            return;
        }

        const cropData = this.cropper.getData(true);
        const scaleX = this.videoCropCanvas.width ? (this.videoElement.videoWidth / this.videoCropCanvas.width) : 1;
        const scaleY = this.videoCropCanvas.height ? (this.videoElement.videoHeight / this.videoCropCanvas.height) : 1;

        const realX = Math.round(cropData.x * scaleX);
        const realY = Math.round(cropData.y * scaleY);
        const realWidth = Math.round(cropData.width * scaleX);
        const realHeight = Math.round(cropData.height * scaleY);
        const cropSize = Math.min(realWidth, realHeight);

        // Use tolerance and rounded sizes to avoid rejecting exact MIN_CROP due to rounding/scale issues
    const minAllowed = this.MIN_CROP - this.CROP_TOLERANCE;
    const maxAllowed = this.MAX_CROP + this.CROP_TOLERANCE;
        if (cropSize < minAllowed || cropSize > maxAllowed) {
            this.showError(`Select a square area between ${this.MIN_CROP}×${this.MIN_CROP} and ${this.MAX_CROP}×${this.MAX_CROP} pixels`);
            return;
        }

        this.setUploadingState(true);

        const formData = new FormData();
    formData.append('video', this.videoFile);
    // send start/end rounded to 2 decimals
    const startStr = start.toFixed(2);
    const endStr = end.toFixed(2);
    formData.append('start', startStr);
    formData.append('end', endStr);
        formData.append('cropX', Math.max(0, realX));
        formData.append('cropY', Math.max(0, realY));
        formData.append('cropSize', cropSize);

        $.ajax({
            url: '/avatar/upload/video',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                this.setUploadingState(false);
                if (response.ok) {
                    window.location.reload();
                } else {
                    this.showError(response.message || 'Error uploading video');
                }
            },
            error: (xhr) => {
                this.setUploadingState(false);
                let errorMsg = 'An error occurred while uploading video';
                try {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        // try parse JSON, otherwise show text
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            if (parsed && parsed.message) errorMsg = parsed.message;
                        } catch (e) {
                            // not JSON, use raw text if short
                            const txt = xhr.responseText.trim();
                            if (txt) errorMsg = txt.length > 200 ? txt.slice(0, 200) + '...' : txt;
                        }
                    }
                } catch (e) {
                    // fallback to generic
                }
                this.showError(errorMsg);
            },
        });
    }

    reset() {
        if (this.cropUpdateTimer) {
            clearTimeout(this.cropUpdateTimer);
            this.cropUpdateTimer = null;
        }

        if (this.previewInterval) {
            clearInterval(this.previewInterval);
            this.previewInterval = null;
        }

        if (this._previewReadyHandler) {
            this.avatarPreviewVideo.removeEventListener('loadeddata', this._previewReadyHandler);
            this.avatarPreviewVideo.removeEventListener('canplay', this._previewReadyHandler);
            this._previewReadyHandler = null;
        }
        if (this._previewTimeUpdateHandler) {
            this.avatarPreviewVideo.removeEventListener('timeupdate', this._previewTimeUpdateHandler);
            this._previewTimeUpdateHandler = null;
        }

        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }

        if (this.videoElement) {
            this.videoElement.pause();
            this.videoElement.removeAttribute('src');
            this.videoElement.load();
        }

        if (this.avatarPreviewVideo) {
            this.avatarPreviewVideo.pause();
            this.avatarPreviewVideo.removeAttribute('src');
            this.avatarPreviewVideo.load();
        }

        if (this.videoCropCanvas) {
            const ctx = this.videoCropCanvas.getContext('2d');
            if (ctx) {
                ctx.clearRect(0, 0, this.videoCropCanvas.width, this.videoCropCanvas.height);
            }
        }

        if (this.objectUrl) {
            URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = null;
        }

        this.videoFile = null;
        this.duration = 0;
        this.currentStartTime = 0;
        this.currentEndTime = 0;
        $('#universalFileInput').val('');
        this.setUploadingState(false);

        if (this.cropSizeInfo) this.cropSizeInfo.textContent = '—';
        if (this.clipDurationInfo) this.clipDurationInfo.textContent = '—';
        if (this.clipRangeInfo) this.clipRangeInfo.textContent = '—';
    }

    showError(message) {
        if (typeof showNotification === 'function') {
            showNotification(message, 'error');
        } else {
            alert(message);
        }
    }
}

$(document).ready(() => {
    new AvatarUploader();
    new VideoAvatarUploader();
});

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
            const cropImage = document.getElementById('cropImage');
            if (!cropImage) {
                return;
            }

            cropImage.src = e.target.result;

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
        if (typeof showNotification === 'function') {
            showNotification(message, 'error');
        } else {
            alert(message);
        }
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

        this.timelineStart = document.getElementById('timelineStart');
        this.timelineEnd = document.getElementById('timelineEnd');
        this.timelineSelection = document.getElementById('timelineSelection');
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
        this.isDraggingStart = false;
        this.isDraggingEnd = false;
        this.lastInteractedSlider = null;

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

        // Обработка выбора файла
        universalFileInput.on('change.videoUpload', (e) => {
            const file = e.target.files[0];
            if (file) {
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

        if (this.timelineStart) {
            this.timelineStart.addEventListener('mousedown', () => {
                this.isDraggingStart = true;
                this.isDraggingEnd = false;
                this.lastInteractedSlider = 'start';
            });
            
            this.timelineStart.addEventListener('touchstart', () => {
                this.isDraggingStart = true;
                this.isDraggingEnd = false;
                this.lastInteractedSlider = 'start';
            });
            
            this.timelineStart.addEventListener('input', () => {
                if (this.isDraggingStart) {
                    this.updateTimeline();
                }
            });
            
            this.timelineStart.addEventListener('change', () => {
                this.isDraggingStart = false;
                this.renderCurrentFrame();
                this.updatePreview();
            });
        }

        if (this.timelineEnd) {
            this.timelineEnd.addEventListener('mousedown', () => {
                this.isDraggingEnd = true;
                this.isDraggingStart = false;
                this.lastInteractedSlider = 'end';
            });
            
            this.timelineEnd.addEventListener('touchstart', () => {
                this.isDraggingEnd = true;
                this.isDraggingStart = false;
                this.lastInteractedSlider = 'end';
            });
            
            this.timelineEnd.addEventListener('input', () => {
                if (this.isDraggingEnd) {
                    this.updateTimeline();
                }
            });
            
            this.timelineEnd.addEventListener('change', () => {
                this.isDraggingEnd = false;
                this.updatePreview();
            });
        }
        
        // Общие обработчики для сброса флагов перетаскивания
        document.addEventListener('mouseup', () => {
            this.isDraggingStart = false;
            this.isDraggingEnd = false;
        });
        
        document.addEventListener('touchend', () => {
            this.isDraggingStart = false;
            this.isDraggingEnd = false;
        });

        this.uploadButton.on('click.videoUpload', () => {
            this.uploadVideo();
        });
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

            const defaultDuration = Math.min(4, Math.min(this.duration, this.MAX_DURATION));

            if (this.timelineStart) {
                this.timelineStart.min = 0;
                this.timelineStart.max = this.duration;
                this.timelineStart.value = 0;
            }

            if (this.timelineEnd) {
                this.timelineEnd.min = 0;
                this.timelineEnd.max = this.duration;
                this.timelineEnd.value = defaultDuration;
            }

            this.updateTimeline();

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

            const drawFrame = () => {
                try {
                    // Ждем пока видео загрузится и будут доступны размеры
                    if (this.videoElement.readyState < 2) {
                        reject(new Error('Video not ready'));
                        return;
                    }
                    
                    const width = this.videoElement.videoWidth;
                    const height = this.videoElement.videoHeight;
                    
                    if (!width || !height) {
                        reject(new Error('Video dimensions unavailable'));
                        return;
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
                    // Дополнительная проверка перед отрисовкой
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
                    this.renderVideoFrame(time).then(resolve).catch(reject);
                };
                this.videoElement.addEventListener('loadeddata', onReady, { once: true });
            }
        });
    }

    renderCurrentFrame() {
        const time = parseFloat(this.timelineStart?.value || 0);
        this.renderVideoFrame(time).then(() => {
            if (this.cropper) {
                try {
                    this.cropper.replace(this.videoCropCanvas.toDataURL('image/png'));
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

    updateTimeline() {
        if (!this.timelineStart || !this.timelineEnd || !this.timelineSelection) {
            return;
        }

        let start = parseFloat(this.timelineStart.value) || 0;
        let end = parseFloat(this.timelineEnd.value) || this.duration;
        
        // Определяем какой ползунок активен на основе флагов перетаскивания
        const isStartActive = this.isDraggingStart || this.lastInteractedSlider === 'start';
        const isEndActive = this.isDraggingEnd || this.lastInteractedSlider === 'end';

        // Проверка пересечения: если start догнал или перешёл end
        if (start >= end) {
            if (isStartActive) {
                // Пользователь двигает start вправо → сдвигаем end
                end = Math.min(start + this.MIN_DURATION, this.duration);
                this.timelineEnd.value = end;
            } else if (isEndActive) {
                // Пользователь двигает end влево → сдвигаем start
                start = Math.max(end - this.MIN_DURATION, 0);
                this.timelineStart.value = start;
            }
        }

        // Проверка максимальной длительности
        const clipLength = end - start;
        if (clipLength > this.MAX_DURATION) {
            if (isStartActive) {
                // Двигаем start → ограничиваем end
                end = Math.min(start + this.MAX_DURATION, this.duration);
                this.timelineEnd.value = end;
            } else if (isEndActive) {
                // Двигаем end → ограничиваем start
                start = Math.max(end - this.MAX_DURATION, 0);
                this.timelineStart.value = start;
            }
        }
        
        // Сохраняем текущие значения
        start = parseFloat(this.timelineStart.value);
        end = parseFloat(this.timelineEnd.value);

        const duration = this.duration || 1;
        const startPercent = (start / duration) * 100;
        const endPercent = (end / duration) * 100;

        this.timelineSelection.style.left = `${startPercent}%`;
        this.timelineSelection.style.width = `${Math.max(endPercent - startPercent, 0)}%`;

        if (this.startTimeLabel) {
            this.startTimeLabel.textContent = `${start.toFixed(2)}s`;
        }
        if (this.endTimeLabel) {
            this.endTimeLabel.textContent = `${end.toFixed(2)}s`;
        }
        if (this.durationLabel) {
            this.durationLabel.textContent = `Длительность: ${(end - start).toFixed(2)}s`;
        }
        if (this.clipDurationInfo) {
            this.clipDurationInfo.textContent = `${(end - start).toFixed(2)}s`;
        }
        if (this.clipRangeInfo) {
            this.clipRangeInfo.textContent = `${start.toFixed(2)}s - ${end.toFixed(2)}s`;
        }
    }

    updatePreview() {
        if (!this.cropper || !this.videoElement || !this.avatarPreviewVideo || !this.avatarPreviewCanvas) {
            return;
        }

        this.currentStartTime = parseFloat(this.timelineStart?.value || 0);
        this.currentEndTime = parseFloat(this.timelineEnd?.value || this.duration);

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

        const start = this.timelineStart ? parseFloat(this.timelineStart.value) || 0 : 0;
        const end = this.timelineEnd ? parseFloat(this.timelineEnd.value) || this.duration : this.duration;
        const clipDuration = end - start;

        if (clipDuration < this.MIN_DURATION || clipDuration > this.MAX_DURATION) {
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
        formData.append('start', start.toFixed(2));
        formData.append('end', end.toFixed(2));
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
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
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

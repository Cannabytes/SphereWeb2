/**
 * Avatar Upload Plugin - JavaScript
 * Обработка загрузки и обрезки аватаров
 */

class AvatarUploader {
    constructor() {
        this.cropper = null;
        this.selectedFile = null;
        this.modal = null;
        this.init();
    }

    init() {
        // Инициализация модального окна Bootstrap
        const modalElement = document.getElementById('cropAvatarModal');
        if (modalElement) {
            this.modal = new bootstrap.Modal(modalElement);
            
            // Очистка при закрытии модального окна
            modalElement.addEventListener('hidden.bs.modal', () => {
                this.cancelCrop();
            });
        }
        
        this.bindEvents();
    }

    bindEvents() {
        const self = this;
        const fileUploadArea = $('#fileUploadArea');
        const avatarInput = $('#avatarInput');

        // Если input отключён (пользователь не имеет средств), не привязываем обработчики
        if (avatarInput.prop('disabled')) {
            // Добавим подсказку при наведении, если нужно показать причину
            fileUploadArea.attr('title', window.avatarUploadPhrases?.pleaseSelect || 'You cannot upload');
            return;
        }

        // Unbind all previous handlers to prevent duplicates and recursion
        fileUploadArea.off('.avatarUpload');
        avatarInput.off('.avatarUpload');
        $('#uploadAvatar').off('.avatarUpload');

        // Клик по области загрузки — trigger native file input click
        fileUploadArea.on('click.avatarUpload', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Use native DOM click (does not trigger jQuery event handlers, prevents recursion)
            avatarInput[0].click();
        });

        // Выбор файла
        avatarInput.on('change.avatarUpload', function(e) {
            const file = e.target.files[0];
            if (file) {
                self.handleFile(file);
            }
        });

        // Drag & Drop события
        fileUploadArea.on('dragover.avatarUpload', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        fileUploadArea.on('dragleave.avatarUpload', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        fileUploadArea.on('drop.avatarUpload', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
            
            const file = e.originalEvent.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                self.handleFile(file);
            } else {
                self.showError(window.avatarUploadPhrases?.pleaseSelect || 'Please select an image');
            }
        });

        // Кнопка загрузки
        $('#uploadAvatar').on('click.avatarUpload', function() {
            self.uploadAvatar();
        });
    }

    handleFile(file) {
        // Проверка размера файла
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            this.showError(window.avatarUploadPhrases?.fileTooLarge || 'File size must not exceed 5MB');
            return;
        }

        this.selectedFile = file;

        const reader = new FileReader();
        const self = this;

        reader.onload = function(e) {
            const cropImage = document.getElementById('cropImage');
            cropImage.src = e.target.result;
            
            // Открываем модальное окно
            if (self.modal) {
                self.modal.show();
            }

            // Небольшая задержка для корректной инициализации после показа модального окна
            setTimeout(() => {
                // Уничтожаем предыдущий cropper если существует
                if (self.cropper) {
                    self.cropper.destroy();
                }

                // Инициализация Cropper.js
                self.cropper = new Cropper(cropImage, {
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
                    minCropBoxWidth: 256,
                    minCropBoxHeight: 256,
                    preview: '.preview',
                    ready: function() {
                        // Анимация появления
                        $('#cropContainer').addClass('fade-in');
                    }
                });
            }, 300);
        };

        reader.onerror = function() {
            self.showError(window.avatarUploadPhrases?.fileReadError || 'Failed to read file');
        };

        reader.readAsDataURL(file);
    }

    cancelCrop() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        
        this.selectedFile = null;
        $('#avatarInput').val('');
        $('#cropContainer').removeClass('fade-in');
    }

    uploadAvatar() {
        if (!this.cropper || !this.selectedFile) {
            this.showError(window.avatarUploadPhrases?.pleaseSelect || 'Please select an image');
            return;
        }

        const cropData = this.cropper.getData(true);
        
        // Проверка минимального размера
        if (cropData.width < 256 || cropData.height < 256) {
            this.showError(window.avatarUploadPhrases?.minCropSize || 'Minimum crop area size: 256x256 pixels');
            return;
        }

        // Блокируем кнопку
        const uploadBtn = $('#uploadAvatar');
        const originalText = uploadBtn.html();
        const uploadingText = window.avatarUploadPhrases?.uploading || 'Uploading...';
        uploadBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-2"></i>' + uploadingText);

        const formData = new FormData();
        formData.append('avatar', this.selectedFile);
        formData.append('cropData', JSON.stringify(cropData));

        const self = this;

        // Отправка на сервер
        $.ajax({
            url: '/avatar/upload/process',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.ok) {
                    location.reload();
                } else {
                    uploadBtn.prop('disabled', false).html(originalText);
                    self.showError(response.message || 'Error uploading');
                }
            },
            error: function(xhr) {
                uploadBtn.prop('disabled', false).html(originalText);
                
                let errorMsg = 'An error occurred while uploading';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                self.showError(errorMsg);
            }
        });
    }

    showError(message) {
        if (typeof showNotification === 'function') {
            showNotification(message, 'error');
        } else {
            alert(message);
        }
    }

    showSuccess(message) {
        if (typeof showNotification === 'function') {
            showNotification(message, 'success');
        } else {
            alert(message);
        }
    }
}

// Инициализация при загрузке страницы
$(document).ready(function() {
    new AvatarUploader();
});

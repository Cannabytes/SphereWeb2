window.ForumEditor = (function() {
    let quill = null;
    let uploadedAttachments = [];
    let uploadedImages = [];
    let lastInsertPosition = null;
    let pond = null;
    let lightbox = null;
    let isUploadInProgress = false;  
    
    const toolbarOptions = [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'font': [] }],
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'align': [] }],
        ['link'],
        ['clean']
    ];


    function initializeQuillCustomFormats() {
        const Block = Quill.import('blots/block');
        const Inline = Quill.import('blots/inline');

        // HTML Блот для вставки произвольного HTML
        class HtmlBlot extends Block {
            static create(value) {
                let node = super.create();
                // Проверяем, является ли значение строкой
                if (typeof value === 'string') {
                    node.innerHTML = value;
                }
                return node;
            }

            static value(node) {
                // Проверяем содержимое перед возвратом
                return node.innerHTML || '';
            }
        }
        HtmlBlot.blotName = 'html';
        HtmlBlot.tagName = 'div';
        Quill.register(HtmlBlot);

        // Кастомный форматтер для ссылок
        class CustomLink extends Inline {
            static create(value) {
                const node = super.create();
                node.setAttribute('href', value);
                node.classList.add('text-primary', 'fw-semibold');

                try {
                    const currentHostname = window.location.hostname;
                    const linkUrl = new URL(value);
                    const currentBaseDomain = this.getBaseDomain(currentHostname);
                    const linkBaseDomain = this.getBaseDomain(linkUrl.hostname);

                    if (currentBaseDomain !== linkBaseDomain) {
                        this.setExternalLinkAttributes(node);
                    }
                } catch (error) {
                    console.warn('Неверный формат URL:', error);
                }

                return node;
            }

            static getBaseDomain(hostname) {
                const parts = hostname.split('.');
                return parts.slice(-2).join('.').toLowerCase();
            }

            static setExternalLinkAttributes(node) {
                node.setAttribute('target', '_blank');
                node.setAttribute('rel', 'noopener noreferrer');
            }

            static formats(node) {
                return node.getAttribute('href');
            }
        }
        CustomLink.blotName = 'link';
        CustomLink.tagName = 'A';
        Quill.register(CustomLink, true);
    }

    // Инициализация FilePond
    function initializeFilePond(selector = '.multiple-filepond') {
        FilePond.registerPlugin(
            FilePondPluginImagePreview,
            FilePondPluginImageExifOrientation,
            FilePondPluginFileValidateSize,
            FilePondPluginFileValidateType
        );

        let modalShown = false;

        FilePond.setOptions({
            server: {
                process: {
                    url: '/forum/upload/image',
                    method: 'POST',
                    withCredentials: false,
                    headers: {},
                    timeout: 7000,
                    onload: async (response) => {
                        try {
                            const result = JSON.parse(response);
                            if (result.success && !modalShown) {
                                modalShown = true;
                                await insertImageToEditor(result);
                                modalShown = false;
                            }
                            return result.file.id;
                        } catch (error) {
                            console.error('Ошибка обработки ответа:', error);
                            noticeError('Ошибка при обработке загруженного изображения');
                            modalShown = false;
                        }
                    },
                    onerror: (response) => {
                        let error = 'Ошибка загрузки';
                        try {
                            const result = JSON.parse(response);
                            error = result.error || error;
                        } catch(e) {}
                        noticeError(error);
                        modalShown = false;
                    }
                }
            },
            labelIdle: 'Перетащите изображения сюда или <span class="filepond--label-action">выберите</span>',
            acceptedFileTypes: ['image/*'],
            maxFileSize: '5MB',
            maxFiles: 6,
            allowMultiple: true,
            instantUpload: true,
            allowReorder: false
        });

        pond = FilePond.create(document.querySelector(selector));

        return pond;
    }

    // Создание HTML разметки изображения
    function createImageHtml(originalUrl, thumbnailUrl, type = 'original') {
        const url = type === 'original' ? originalUrl : thumbnailUrl;

        uploadedImages.push({
            original: originalUrl,
            thumbnail: thumbnailUrl
        });

        return `<a href="${originalUrl}" class="glightbox" data-gallery="gallery"><img src="${url}" class="img-fluid rounded" alt="image"></a> `;
    }

    // Модальное окно выбора размера изображения
    async function showImageSizeModal(result) {
        return new Promise((resolve) => {
            const modal = $(`
                <div class="modal fade" id="imageInsertModal">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Выберите размер изображения</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">Превью</h6>
                                            </div>
                                            <div class="card-body d-flex flex-column">
                                                <div class="flex-grow-1 text-center">
                                                    <img src="${result.file.thumbnail}" class="img-fluid rounded" alt="Превью">
                                                </div>
                                                <button class="btn btn-primary btn-sm mt-2 select-image" data-type="thumbnail">
                                                    Вставить превью
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">Оригинал</h6>
                                            </div>
                                            <div class="card-body d-flex flex-column">
                                                <div class="flex-grow-1 text-center">
                                                    <img src="${result.file.url}" class="img-fluid rounded" alt="Оригинал">
                                                </div>
                                                <button class="btn btn-primary btn-sm mt-2 select-image" data-type="original">
                                                    Вставить оригинал
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            modal.on('click', '.select-image', function() {
                const type = $(this).data('type');
                modal.modal('hide');
                modal.remove();
                resolve({ type });
            });

            modal.on('hidden.bs.modal', function() {
                modal.remove();
                resolve({ type: 'original' });
            });

            modal.appendTo('body').modal('show');
        });
    }

    // Вставка изображения в редактор
    async function insertImageToEditor(result) {
        try {
            const uploadResult = await showImageSizeModal(result);
            const imageHtml = createImageHtml(result.file.url, result.file.thumbnail, uploadResult.type);
            const selection = quill.getSelection(true);
            const currentPosition = selection ? selection.index : quill.getLength();

            quill.insertEmbed(currentPosition, 'html', imageHtml);
            quill.setSelection(currentPosition + 1);

            if (result.file && result.file.id) {
                uploadedAttachments.push(result.file.id);
            }

            refreshLightbox();
        } catch (error) {
            console.error('Ошибка при вставке изображения:', error);
            noticeError('Ошибка при вставке изображения');
        }
    }

    // Обновление GLightbox
    function refreshLightbox() {
        if (lightbox) {
            lightbox.destroy();
        }
        lightbox = GLightbox({
            selector: '.glightbox'
        });
    }

    // Инициализация drag & drop
    function initializeDragAndDrop() {
        const editor = quill.root;

        editor.addEventListener('dragover', (e) => {
            e.preventDefault();
            editor.classList.add('dragover');
        });

        editor.addEventListener('dragleave', (e) => {
            e.preventDefault();
            editor.classList.remove('dragover');
        });

        editor.addEventListener('drop', async (e) => {
            e.preventDefault();
            editor.classList.remove('dragover');
            handleImageDrop(e.dataTransfer.files);
        });
    }

    // Обработка перетаскивания изображений
    async function handleImageDrop(files) {
        if (!files || !files.length) return;

        lastInsertPosition = null;

        for (const file of files) {
            if (!file.type.startsWith('image/')) continue;

            try {
                const selection = quill.getSelection(true);
                const currentPosition = selection ? selection.index : quill.getLength();

                const placeholderText = 'Загрузка изображения...';
                quill.insertText(currentPosition, placeholderText, {
                    'color': '#666',
                    'italic': true
                });

                const formData = new FormData();
                formData.append('filepond', file);

                const response = await fetch('/forum/upload/image', {
                    method: 'POST',
                    body: formData
                });

                quill.deleteText(currentPosition, placeholderText.length);

                if (!response.ok) throw new Error('Ошибка загрузки');

                const result = await response.json();
                if (result.success) {
                    await insertImageToEditor(result);
                }
            } catch (error) {
                console.error('Ошибка загрузки:', error);
                noticeError('Ошибка при загрузке изображения');
            }
        }
    }

    // Обработчик вставки изображений
    function handleImagePaste(node, delta) {
        return new Delta();
    }

    function validateContent(content = null) {
        if (content === null && quill) {
            content = quill.root.innerHTML;
        }

        if (!content) {
            if (uploadedAttachments.length > 0 || uploadedImages.length > 0) {
                return null; 
            }
            return "Сообщение не может быть пустым";
        }

        const plainText = content.replace(/<[^>]*>/g, '').trim();

        if (plainText.length < 1) {
            if (uploadedAttachments.length > 0 || uploadedImages.length > 0) {
                return null;
            }
            if (/<img\b[^>]*>/i.test(content)) {
                return null;
            }
            return "Сообщение слишком короткое. Минимальная длина - 1 символ.";
        }

        // Проверка на пустое сообщение (только пробелы)
        if (/^\s*$/.test(plainText)) {
            return "Сообщение не может быть пустым.";
        }

        // Проверка на повторяющиеся символы
        if (/^(.)\1+$/.test(plainText)) {
            return "Сообщение не может состоять из повторяющихся символов.";
        }

        return null; // Возвращаем null если все проверки пройдены
    }

    // Подготовка контента к отправке
    function prepareContent() {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = quill.root.innerHTML;

        tempDiv.querySelectorAll('a').forEach(link => {
            const img = link.querySelector('img');
            if (img) {
                const imgSrc = img.getAttribute('src');
                const foundImage = uploadedImages.find(image =>
                    image.thumbnail === imgSrc || image.original === imgSrc
                );

                if (foundImage) {
                    link.setAttribute('href', foundImage.original);
                    link.setAttribute('class', 'glightbox');
                    link.setAttribute('data-gallery', 'gallery_');
                    link.removeAttribute('target');
                    link.removeAttribute('rel');
                    img.setAttribute('src', foundImage.thumbnail);
                    img.setAttribute('class', 'img-fluid rounded');
                }
            }
        });

        return tempDiv.innerHTML;
    }

    // Публичное API
    return {
        initialize: function(editorSelector = '#message-post', pondSelector = '.multiple-filepond') {
            initializeQuillCustomFormats();

            quill = new Quill(editorSelector, {
                modules: {
                    toolbar: toolbarOptions,
                    keyboard: {
                        bindings: {
                            'paste': {
                                key: 'V',
                                shortKey: true,
                                handler: function() {
                                    return true;
                                }
                            }
                        }
                    }
                },
                theme: 'snow'
            });

            // Флаг для отслеживания процесса загрузки
            let isUploading = false;

            quill.root.addEventListener('paste', function(e) {
                if (isUploading) {
                    e.preventDefault();
                    return;
                }

                const clipboardData = e.clipboardData;

                if (!clipboardData || !clipboardData.items) {
                    return;
                }

                for (const item of clipboardData.items) {
                    if (item.type.startsWith('image/')) {
                        e.preventDefault();

                        // Устанавливаем флаг загрузки
                        isUploading = true;

                        const file = item.getAsFile();

                        if (pond && file) {
                            // Очищаем FilePond перед добавлением нового файла
                            pond.removeFiles();

                            // Добавляем один файл
                            pond.addFile(file).then(() => {
                                // Сбрасываем флаг после завершения загрузки
                                isUploading = false;
                            }).catch(() => {
                                isUploading = false;
                            });
                        }
                        break;
                    }
                }
            });

            // Инициализация FilePond с модифицированными настройками
            FilePond.registerPlugin(
                FilePondPluginImagePreview,
                FilePondPluginImageExifOrientation,
                FilePondPluginFileValidateSize,
                FilePondPluginFileValidateType
            );

            const pondOptions = {
                server: {
                    process: {
                        url: '/forum/upload/image',
                        method: 'POST',
                        withCredentials: false,
                        headers: {},
                        timeout: 7000,
                        onload: async (response) => {
                            try {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    await insertImageToEditor(result);
                                    // Очищаем FilePond после успешной загрузки
                                    if (pond) {
                                        pond.removeFiles();
                                    }
                                }
                                return result.file.id;
                            } catch (error) {
                                console.error('Ошибка обработки ответа:', error);
                                noticeError('Ошибка при обработке загруженного изображения');
                            }
                        },
                        onerror: (response) => {
                            let error = 'Ошибка загрузки';
                            try {
                                const result = JSON.parse(response);
                                error = result.error || error;
                            } catch(e) {}
                            noticeError(error);
                        }
                    }
                },
                allowMultiple: false,
                instantUpload: true,
                allowRevert: false,
                acceptedFileTypes: ['image/*'],
                maxFileSize: '5MB',
                labelIdle: 'Перетащите изображения сюда или <span class="filepond--label-action">выберите</span>'
            };

            pond = FilePond.create(document.querySelector(pondSelector));
            pond.setOptions(pondOptions);

            initializeDragAndDrop();
            refreshLightbox();

            return {
                quill,
                pond
            };
        },

        getContent: function() {
            return prepareContent();
        },

        validateContent: function(content) {
            return validateContent(content);
        },

        getUploadedAttachments: function() {
            return [...uploadedAttachments];
        },

        reset: function() {
            uploadedAttachments = [];
            uploadedImages = [];
            lastInsertPosition = null;
            if (pond) {
                pond.removeFiles();
            }
            if (quill) {
                quill.setContents([]);
            }
        },

        // Метод для отслеживания уже существующих изображений при редактировании
        trackExistingImage: function(imageData) {
            uploadedImages.push(imageData);
        }
    };
})();

// Добавляем стили для редактора
document.head.appendChild(Object.assign(document.createElement('style'), {
    textContent: `
        .ql-editor.dragover {
            border: 2px dashed #0088cc;
            background-color: rgba(0, 136, 204, 0.1);
        }
        #imageInsertModal img {
            max-height: 200px;
            width: auto;
            margin: 0 auto;
            display: block;
            object-fit: contain;
        }
        .modal img {
            max-width: 100%;
            height: auto;
        }
    `
}));



document.addEventListener('DOMContentLoaded', function () {
    const posts = document.querySelectorAll('.post');
    posts.forEach(post => {
        let htmlContent = post.innerHTML;
        const youtubeLinkPattern = /https:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)[^\s<]*/g;
        htmlContent = htmlContent.replace(youtubeLinkPattern, function (match, videoId) {
            return `<iframe width="560" height="315" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        });
        post.innerHTML = htmlContent;
    });
});

window.ForumEditor = (function() {
    let quill = null;
    let uploadedAttachments = [];
    let uploadedImages = [];
    let lastInsertPosition = null;
    let pond = null;
    let lightbox = null;
    let isUploadInProgress = false;  
    let postGallerySequence = 0;

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

    function registerUploadedImage(imageData = {}) {
        const attachmentId = imageData.id ? String(imageData.id) : null;
        const normalizedData = {
            id: attachmentId,
            original: imageData.original || '',
            thumbnail: imageData.thumbnail || ''
        };

        if (attachmentId) {
            const existingIndex = uploadedImages.findIndex(img => img.id && String(img.id) === attachmentId);
            if (existingIndex !== -1) {
                uploadedImages[existingIndex] = { ...uploadedImages[existingIndex], ...normalizedData };
                return;
            }
        }

        uploadedImages.push(normalizedData);
    }

    function escapeHtml(value = '') {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };
        return String(value ?? '').replace(/[&<>"]'/g, char => map[char]);
    }

    function mergeClasses(element, classes) {
        if (!element || !classes || !classes.length) {
            return;
        }

        const current = (element.getAttribute('class') || '')
            .split(/\s+/)
            .filter(Boolean);
        const targetSet = new Set(current);
        classes.forEach(cls => {
            if (cls && typeof cls === 'string') {
                targetSet.add(cls);
            }
        });
        element.setAttribute('class', Array.from(targetSet).join(' '));
    }

    function ensureAnchorForImage(img) {
        if (!img || img.closest('.forum-gallery-row')) {
            return null;
        }

        if (img.dataset && img.dataset.forumIgnoreGallery === 'true') {
            return null;
        }

        const imgSrc = img.getAttribute('data-original-url') || img.getAttribute('src');
        if (!imgSrc) {
            return null;
        }

        let anchor = img.closest('a');
        if (!anchor) {
            anchor = document.createElement('a');
            anchor.setAttribute('href', imgSrc);
            const parent = img.parentNode;
            if (!parent) {
                return null;
            }
            parent.insertBefore(anchor, img);
            anchor.appendChild(img);
        }

        return anchor;
    }

    function removeNodeIfEmpty(node) {
        if (!node) {
            return;
        }
        if (node.classList && (node.classList.contains('forum-gallery-row') || node.classList.contains('post'))) {
            return;
        }

        const hasMedia = node.querySelector && node.querySelector('img, video, iframe, audio, source, object, canvas, svg, a, picture');
        const textContent = node.textContent ? node.textContent.trim() : '';

        if (!hasMedia && textContent === '') {
            node.remove();
        }
    }

    function removeEmptyTextNodes(container) {
        if (!container) {
            return;
        }
        if (typeof NodeFilter === 'undefined') {
            return;
        }
        const iterator = document.createNodeIterator(container, NodeFilter.SHOW_TEXT);
        const toRemove = [];
        let currentNode;
        while ((currentNode = iterator.nextNode())) {
            if (currentNode.textContent.trim() === '') {
                toRemove.push(currentNode);
            }
        }
        toRemove.forEach(node => {
            if (node.parentNode) {
                node.parentNode.removeChild(node);
            }
        });
    }

    function preparePostGalleries(root = document) {
        const posts = root.querySelectorAll('.post');

        posts.forEach(post => {
            if (!post || post.dataset.galleryPrepared === 'true') {
                return;
            }

            const images = Array.from(post.querySelectorAll('img')).filter(img => !img.closest('.forum-gallery-row'));
            if (!images.length) {
                return;
            }

            const anchors = [];
            images.forEach(img => {
                const anchor = ensureAnchorForImage(img);
                if (!anchor) {
                    return;
                }
                if (!anchors.includes(anchor)) {
                    anchors.push(anchor);
                }
            });

            if (!anchors.length) {
                return;
            }

            postGallerySequence += 1;
            const galleryId = `gallery${postGallerySequence}`;
            const row = document.createElement('div');
            row.className = 'row g-3 forum-gallery-row';
            row.setAttribute('data-gallery-id', galleryId);

            anchors.forEach(anchor => {
                const img = anchor.querySelector('img');
                const previousParent = anchor.parentElement;

                mergeClasses(anchor, ['glightbox', 'forum-image']);
                anchor.setAttribute('data-gallery', galleryId);

                const originalUrl = anchor.getAttribute('data-original-url') ||
                    (img && img.getAttribute('data-original-url')) ||
                    anchor.getAttribute('href') ||
                    (img && img.getAttribute('src')) ||
                    '#';

                if (originalUrl && originalUrl !== '#') {
                    anchor.setAttribute('href', originalUrl);
                    anchor.setAttribute('data-original-url', originalUrl);
                }

                const preferredImageType = anchor.getAttribute('data-image-type') || (img && img.getAttribute('data-image-type')) || 'original';
                const isThumbnail = preferredImageType === 'thumbnail';

                if (img) {
                    mergeClasses(img, ['img-fluid', 'rounded']);
                    if (!img.getAttribute('alt') || img.getAttribute('alt').trim() === '') {
                        img.setAttribute('alt', 'image');
                    }
                    if (!img.getAttribute('data-original-url') && originalUrl && originalUrl !== '#') {
                        img.setAttribute('data-original-url', originalUrl);
                    }
                    img.setAttribute('data-image-type', preferredImageType);
                }

                anchor.setAttribute('data-image-type', preferredImageType);

                const col = document.createElement('div');
                col.className = isThumbnail
                    ? 'col-xl-3 col-lg-3 col-md-4 col-sm-6 col-12 mb-3 forum-gallery-col'
                    : 'col-12 mb-3 forum-gallery-col';
                col.appendChild(anchor);
                row.appendChild(col);

                if (previousParent && previousParent !== row && previousParent !== post) {
                    removeNodeIfEmpty(previousParent);
                }
            });

            post.appendChild(row);
            removeEmptyTextNodes(post);

            post.dataset.galleryPrepared = 'true';
        });
    }


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
    function createImageHtml({
        id = null,
        originalUrl = '',
        thumbnailUrl = '',
        type = 'original',
        name = 'image'
    }) {
        const hasThumbnail = Boolean(thumbnailUrl);
        const resolvedType = type === 'thumbnail' && hasThumbnail ? 'thumbnail' : 'original';
        const imageSrc = resolvedType === 'thumbnail' && hasThumbnail ? thumbnailUrl : originalUrl;
        const attachmentAttr = id ? ` data-attachment-id="${id}"` : '';
        const typeAttr = ` data-image-type="${resolvedType}"`;
        const originalAttr = originalUrl ? ` data-original-url="${originalUrl}"` : '';
        const thumbnailAttr = hasThumbnail ? ` data-thumbnail-url="${thumbnailUrl}"` : '';
        const safeAlt = escapeHtml(name || 'image');

        return `<a href="${originalUrl}" class="glightbox forum-image" data-gallery="gallery_"${attachmentAttr}${typeAttr}${originalAttr}${thumbnailAttr}>` +
            `<img src="${imageSrc}" class="img-fluid rounded" alt="${safeAlt}"${attachmentAttr}${typeAttr}${originalAttr}${thumbnailAttr}></a> `;
    }

    // Модальное окно выбора размера изображения
    async function showImageSizeModal(result) {
        return new Promise((resolve) => {
            const fileData = result && result.file ? result.file : {};
            const hasThumbnail = Boolean(fileData.thumbnail);
            const previewSrc = hasThumbnail ? fileData.thumbnail : fileData.url;
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
                                                    <img src="${previewSrc || ''}" class="img-fluid rounded" alt="Превью">
                                                </div>
                                                <button class="btn btn-primary btn-sm mt-2 select-image" data-type="thumbnail" ${hasThumbnail ? '' : 'disabled'}>
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
            const fileData = result && result.file ? result.file : {};
            const requestedType = uploadResult.type === 'thumbnail' && fileData.thumbnail ? 'thumbnail' : 'original';

            registerUploadedImage({
                id: fileData.id,
                original: fileData.url,
                thumbnail: fileData.thumbnail
            });

            const imageHtml = createImageHtml({
                id: fileData.id,
                originalUrl: fileData.url || '',
                thumbnailUrl: fileData.thumbnail || '',
                type: requestedType,
                name: fileData.name
            });
            const selection = quill.getSelection(true);
            const currentPosition = selection ? selection.index : quill.getLength();

            quill.insertEmbed(currentPosition, 'html', imageHtml);
            quill.setSelection(currentPosition + 1);

            if (fileData.id) {
                uploadedAttachments.push(fileData.id);
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
        preparePostGalleries(document);
        if (typeof GLightbox === 'undefined') {
            return;
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
            if (!img) {
                return;
            }

            const attachmentId = link.getAttribute('data-attachment-id') || img.getAttribute('data-attachment-id') || null;
            const existingOriginal = link.getAttribute('data-original-url') || img.getAttribute('data-original-url') || link.getAttribute('href');
            const existingThumbnail = link.getAttribute('data-thumbnail-url') || img.getAttribute('data-thumbnail-url') || '';

            let storedImage = null;
            if (attachmentId) {
                storedImage = uploadedImages.find(image => image.id && String(image.id) === String(attachmentId)) || null;
            }
            if (!storedImage) {
                storedImage = uploadedImages.find(image => {
                    if (!image) {
                        return false;
                    }
                    const matchesOriginal = image.original && existingOriginal && image.original === existingOriginal;
                    const matchesThumbnail = image.thumbnail && existingThumbnail && image.thumbnail === existingThumbnail;
                    return matchesOriginal || matchesThumbnail;
                }) || null;
            }

            const preferredType = link.getAttribute('data-image-type') || img.getAttribute('data-image-type') || 'original';
            const originalUrl = (storedImage && storedImage.original) || existingOriginal;
            const thumbnailUrl = (storedImage && storedImage.thumbnail) || existingThumbnail;
            const hasThumbnail = Boolean(thumbnailUrl);
            const finalType = preferredType === 'thumbnail' && hasThumbnail ? 'thumbnail' : 'original';
            const resolvedSrc = finalType === 'thumbnail' && hasThumbnail ? thumbnailUrl : originalUrl;

            if (originalUrl) {
                link.setAttribute('href', originalUrl);
                link.setAttribute('data-original-url', originalUrl);
            } else {
                link.removeAttribute('href');
                link.removeAttribute('data-original-url');
            }

            if (hasThumbnail) {
                link.setAttribute('data-thumbnail-url', thumbnailUrl);
            } else {
                link.removeAttribute('data-thumbnail-url');
            }

            mergeClasses(link, ['glightbox', 'forum-image']);
            link.setAttribute('data-gallery', 'gallery_');
            link.setAttribute('data-image-type', finalType);
            link.removeAttribute('target');
            link.removeAttribute('rel');

            if (attachmentId) {
                link.setAttribute('data-attachment-id', attachmentId);
            } else {
                link.removeAttribute('data-attachment-id');
            }

            if (resolvedSrc) {
                img.setAttribute('src', resolvedSrc);
            }

            mergeClasses(img, ['img-fluid', 'rounded']);
            img.setAttribute('data-image-type', finalType);

            if (!img.getAttribute('alt') || img.getAttribute('alt').trim() === '') {
                img.setAttribute('alt', 'image');
            }

            if (attachmentId) {
                img.setAttribute('data-attachment-id', attachmentId);
            } else {
                img.removeAttribute('data-attachment-id');
            }

            if (originalUrl) {
                img.setAttribute('data-original-url', originalUrl);
            } else {
                img.removeAttribute('data-original-url');
            }

            if (hasThumbnail) {
                img.setAttribute('data-thumbnail-url', thumbnailUrl);
            } else {
                img.removeAttribute('data-thumbnail-url');
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
            registerUploadedImage(imageData);
        },

        prepareGalleries: function(root) {
            preparePostGalleries(root || document);
            refreshLightbox();
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
        .forum-gallery-row {
            margin-top: 0.75rem;
        }
        .forum-gallery-row .forum-gallery-col {
            position: relative;
        }
        .forum-gallery-row .card {
            border: 0;
            background: transparent;
            box-shadow: none;
        }
        .forum-gallery-row .card img {
            width: 100%;
            height: auto;
            display: block;
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

    preparePostGalleries(document);
    refreshLightbox();
});

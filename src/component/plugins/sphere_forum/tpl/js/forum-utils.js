// forum-utils.js
// Утилитарные функции для форума

const ForumUtils = (function() {
    // Приватные переменные
    const YOUTUBE_VIDEO_REGEX = /https:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)[^\s<]*/g;
    const YOUTUBE_CHANNEL_REGEX = /https:\/\/(?:www\.)?youtube\.com\/@[a-zA-Z0-9_-]+(?:\/\w+)?[^\s<]*/g;
    const YOUTUBE_REGEX = /https:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|@[a-zA-Z0-9_-]+(?:\/\w+)?)|youtu\.be\/)([a-zA-Z0-9_-]+)?[^\s<]*/g;
    const IMAGE_URL_REGEX = /https?:\/\/[^\s<]+(?:jpg|jpeg|png|gif|webp)(?![^<]*>|[^<]*<\/)/gi;
    const URL_REGEX = /(https?:\/\/[^\s<]+[^<.,:;"')\]\s])|(\bwww\.[^\s<]+[^<.,:;"')\]\s])/g;
    const EXCLUDED_DOMAINS = [];

    // Функции для работы с датами
    function formatDate(date) {
        return date.toISOString().slice(0, 16);
    }

    function addDays(date, days) {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    }

    // Функции для обработки URL и контента
    function processPostContent() {
        const posts = document.querySelectorAll('.post');
        posts.forEach(post => {
            processYoutubeLinks(post);
            processImagesAndLinks(post);
        });
    }

    function processYoutubeLinks(post) {
        // Обрабатываем только видео-ссылки для встраивания
        let htmlContent = post.innerHTML;
        htmlContent = htmlContent.replace(YOUTUBE_VIDEO_REGEX, (match, videoId) => {
            return createYoutubeEmbed(videoId);
        });
        post.innerHTML = htmlContent;

        // Примечание: каналы и другие типы YouTube-ссылок
        // будут обрабатываться как обычные ссылки в processParagraphLinks
    }

    function processImagesAndLinks(post) {
        post.querySelectorAll('p').forEach(paragraph => {
            // Обрабатываем изображения первыми
            processParagraphImages(paragraph);

            // Затем обрабатываем ссылки
            processParagraphLinks(paragraph);
        });
    }

    function processParagraphImages(paragraph) {
        // Получаем текстовое содержимое параграфа
        const content = paragraph.innerHTML;

        // Находим все URL изображений
        const imageMatches = Array.from(content.matchAll(IMAGE_URL_REGEX));

        if (imageMatches.length === 0) return;

        // Создаем временный div для работы с HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;

        // Собираем группы изображений и содержимое без изображений
        const elements = [];
        let currentImages = [];
        let lastTextNode = null;

        // Получаем все текстовые узлы
        const textNodes = [];
        const walker = document.createTreeWalker(
            tempDiv,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        let node;
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }

        // Проходим по всем текстовым узлам и ищем URL изображений
        textNodes.forEach(textNode => {
            const nodeContent = textNode.nodeValue;
            const nodeImageMatches = Array.from(nodeContent.matchAll(IMAGE_URL_REGEX));

            if (nodeImageMatches.length > 0) {
                let lastIndex = 0;

                nodeImageMatches.forEach(match => {
                    const imageUrl = match[0];
                    const offset = match.index;

                    // Текст до изображения
                    if (offset > lastIndex) {
                        const textBefore = nodeContent.slice(lastIndex, offset);
                        if (textBefore.trim()) {
                            // Если есть значимый текст, завершаем текущую группу изображений
                            if (currentImages.length > 0) {
                                elements.push(createImageGroupHtml(currentImages));
                                currentImages = [];
                            }

                            // Добавляем текст как отдельный элемент
                            const textNode = document.createTextNode(textBefore);
                            elements.push(textNode);
                        }
                    }

                    // Добавляем изображение в текущую группу
                    currentImages.push(imageUrl);
                    lastIndex = offset + imageUrl.length;
                });

                // Текст после последнего изображения
                if (lastIndex < nodeContent.length) {
                    const textAfter = nodeContent.slice(lastIndex);
                    if (textAfter.trim()) {
                        // Если есть значимый текст, завершаем текущую группу изображений
                        if (currentImages.length > 0) {
                            elements.push(createImageGroupHtml(currentImages));
                            currentImages = [];
                        }

                        // Добавляем текст как отдельный элемент
                        const textNode = document.createTextNode(textAfter);
                        elements.push(textNode);
                    }
                }

                // Удаляем обработанный текстовый узел
                textNode.parentNode.removeChild(textNode);
            } else {
                // Если в текстовом узле нет изображений, сохраняем его
                elements.push(textNode.cloneNode(true));
            }
        });

        // Завершаем последнюю группу изображений
        if (currentImages.length > 0) {
            elements.push(createImageGroupHtml(currentImages));
        }

        // Очищаем параграф и добавляем новые элементы
        paragraph.innerHTML = '';
        elements.forEach(element => {
            if (element instanceof Node) {
                paragraph.appendChild(element);
            } else {
                const tempContainer = document.createElement('div');
                tempContainer.innerHTML = element;
                while (tempContainer.firstChild) {
                    paragraph.appendChild(tempContainer.firstChild);
                }
            }
        });
    }

    function processParagraphLinks(paragraph) {
        // Получаем все текстовые узлы параграфа
        const textNodes = [];
        const walker = document.createTreeWalker(
            paragraph,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        let node;
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }

        // Обрабатываем каждый текстовый узел
        textNodes.forEach(textNode => {
            const content = textNode.nodeValue;

            // Проверяем, содержит ли текст URL
            if (!content.match(URL_REGEX)) return;

            const fragments = [];
            let lastIndex = 0;

            // Используем регулярное выражение для поиска URL
            const urlMatches = Array.from(content.matchAll(URL_REGEX));

            urlMatches.forEach(match => {
                const url = match[0];
                const offset = match.index;
                const www = url.startsWith('www.');

                try {
                    const domain = new URL(www ? `http://${url}` : url).hostname;

                    // Добавляем текст до URL
                    if (offset > lastIndex) {
                        fragments.push(document.createTextNode(content.slice(lastIndex, offset)));
                    }

                    // Пропускаем исключенные домены и уже обработанные изображения
                    if (EXCLUDED_DOMAINS.some(excludedDomain => domain.includes(excludedDomain)) ||
                        IMAGE_URL_REGEX.test(url)) {
                        fragments.push(document.createTextNode(url));
                    } else {
                        // Создаем ссылку
                        const link = document.createElement('a');
                        link.href = www ? `http://${url}` : url;
                        link.textContent = url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.className = 'text-primary fw-semibold';
                        fragments.push(link);
                    }

                    lastIndex = offset + url.length;
                } catch (error) {
                    console.warn('Неверный формат URL:', error);
                    if (offset > lastIndex) {
                        fragments.push(document.createTextNode(content.slice(lastIndex, offset)));
                    }
                    fragments.push(document.createTextNode(url));
                    lastIndex = offset + url.length;
                }
            });

            // Добавляем оставшийся текст
            if (lastIndex < content.length) {
                fragments.push(document.createTextNode(content.slice(lastIndex)));
            }

            // Заменяем исходный текстовый узел на фрагменты
            const parent = textNode.parentNode;
            fragments.forEach(fragment => {
                parent.insertBefore(fragment, textNode);
            });

            // Удаляем исходный текстовый узел
            parent.removeChild(textNode);
        });
    }

    // Вспомогательные функции
    function createYoutubeEmbed(videoId) {
        return `<iframe width="560" height="315" 
                src="https://www.youtube.com/embed/${videoId}" 
                frameborder="0" 
                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen></iframe>`;
    }

    function createImageGroupHtml(images) {
        if (images.length === 0) return '';

        const row = document.createElement('div');
        row.className = 'row';

        images.forEach(url => {
            const col = createImageColumn(url);
            row.appendChild(col);
        });

        return row.outerHTML;
    }

    function createImageColumn(url) {
        const col = document.createElement('div');
        col.className = 'col-lg-12 col-md-12 col-sm-12 col-12';

        const link = document.createElement('a');
        link.href = url;
        link.className = 'glightbox';

        const img = document.createElement('img');
        img.src = url;
        img.alt = 'image';
        img.className = 'img-fluid';

        link.appendChild(img);
        col.appendChild(link);
        return col;
    }

    function hasSignificantText(part) {
        return part &&
            part.type === 'text' &&
            part.content.replace(/(?:<br\s*\/?>|\s+)/g, '').length > 0;
    }

    // Обработка загруженных файлов
    function handleFileUpload(file, maxSize = 5, allowedTypes = ['image/*']) {
        return new Promise((resolve, reject) => {
            // Проверка размера файла
            if (file.size > maxSize * 1024 * 1024) {
                reject(`Размер файла превышает ${maxSize}MB`);
                return;
            }

            // Проверка типа файла
            const isAllowedType = allowedTypes.some(type => {
                if (type.endsWith('/*')) {
                    const baseType = type.slice(0, -2);
                    return file.type.startsWith(baseType);
                }
                return file.type === type;
            });

            if (!isAllowedType) {
                reject('Неподдерживаемый тип файла');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = (e) => reject('Ошибка чтения файла');
            reader.readAsDataURL(file);
        });
    }

    // Валидация данных
    function validateInput(value, rules = {}) {
        const {
            minLength = 0,
            maxLength = Infinity,
            required = false,
            pattern = null,
            custom = null
        } = rules;

        // Проверка на обязательное поле
        if (required && !value) {
            return 'Поле обязательно для заполнения';
        }

        // Проверка длины
        if (value.length < minLength) {
            return `Минимальная длина: ${minLength} символов`;
        }

        if (value.length > maxLength) {
            return `Максимальная длина: ${maxLength} символов`;
        }

        // Проверка по регулярному выражению
        if (pattern && !pattern.test(value)) {
            return 'Неверный формат';
        }

        // Пользовательская проверка
        if (custom && typeof custom === 'function') {
            const customError = custom(value);
            if (customError) return customError;
        }

        return null;
    }

    // Работа с локальным хранилищем
    const storage = {
        set: (key, value) => {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                console.error('Ошибка сохранения в localStorage:', e);
            }
        },

        get: (key, defaultValue = null) => {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                console.error('Ошибка чтения из localStorage:', e);
                return defaultValue;
            }
        },

        remove: (key) => {
            try {
                localStorage.removeItem(key);
            } catch (e) {
                console.error('Ошибка удаления из localStorage:', e);
            }
        }
    };

    // Функции для работы с уведомлениями
    function showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification-toast`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Анимация появления
        setTimeout(() => notification.classList.add('show'), 100);

        // Автоматическое скрытие
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    // Публичное API
    return {
        formatDate,
        addDays,
        processPostContent,
        handleFileUpload,
        validateInput,
        storage,
        showNotification
    };
})();

// Добавляем стили для уведомлений
document.head.appendChild(Object.assign(document.createElement('style'), {
    textContent: `
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
            max-width: 300px;
        }
        
        .notification-toast.show {
            opacity: 1;
        }
    `
}));

// Инициализация при загрузке документа
document.addEventListener('DOMContentLoaded', () => {
    ForumUtils.processPostContent();
});
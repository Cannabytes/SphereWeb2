// forum-utils.js
// Утилитарные функции для форума

const ForumUtils = (function() {
    // Приватные переменные
    const YOUTUBE_REGEX = /https:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)[^\s<]*/g;
    const IMAGE_URL_REGEX = /https?:\/\/[^\s<]+(?:jpg|jpeg|png|gif|webp)(?![^<]*>|[^<]*<\/)/gi;
    const URL_REGEX = /(https?:\/\/[^\s<]+[^<.,:;"')\]\s])|(\bwww\.[^\s<]+[^<.,:;"')\]\s])/g;
    const EXCLUDED_DOMAINS = ['youtube.com', 'youtu.be'];

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
        let htmlContent = post.innerHTML;
        htmlContent = htmlContent.replace(YOUTUBE_REGEX, (match, videoId) => {
            return createYoutubeEmbed(videoId);
        });
        post.innerHTML = htmlContent;
    }

    function processImagesAndLinks(post) {
        post.querySelectorAll('p').forEach(paragraph => {
            const content = paragraph.innerHTML;
            const processedContent = processContentWithImagesAndLinks(content);
            paragraph.innerHTML = processedContent;
        });
    }

    function processContentWithImagesAndLinks(content) {
        const parts = splitContentByImages(content);
        return processContentParts(parts);
    }

    function splitContentByImages(content) {
        const parts = [];
        let lastIndex = 0;

        const matches = Array.from(content.matchAll(IMAGE_URL_REGEX));
        matches.forEach(match => {
            const textBefore = content.slice(lastIndex, match.index);
            if (textBefore) parts.push({ type: 'text', content: textBefore });
            parts.push({ type: 'image', content: match[0] });
            lastIndex = match.index + match[0].length;
        });

        if (lastIndex < content.length) {
            parts.push({ type: 'text', content: content.slice(lastIndex) });
        }

        return parts;
    }

    function processContentParts(parts) {
        let result = '';
        let imageGroup = [];

        parts.forEach((part, index) => {
            if (part.type === 'image') {
                const prevPart = parts[index - 1];
                const nextPart = parts[index + 1];

                const hasSignificantTextBefore = hasSignificantText(prevPart);
                const hasSignificantTextAfter = hasSignificantText(nextPart);

                if (hasSignificantTextBefore) {
                    result += createImageGroupHtml(imageGroup);
                    imageGroup = [];
                }

                imageGroup.push(part.content);

                if (hasSignificantTextAfter || index === parts.length - 1) {
                    result += createImageGroupHtml(imageGroup);
                    imageGroup = [];
                }
            } else if (part.type === 'text') {
                result += convertUrlsToLinks(part.content);
            }
        });

        return result;
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

    function convertUrlsToLinks(text) {
        if (!text.match(URL_REGEX)) return text;

        let result = '';
        let lastIndex = 0;

        text.replace(URL_REGEX, (url, protocol, www, offset) => {
            try {
                const domain = new URL(www ? `http://${url}` : url).hostname;

                // Добавляем текст до URL
                result += text.slice(lastIndex, offset);

                // Пропускаем исключенные домены
                if (EXCLUDED_DOMAINS.some(excludedDomain => domain.includes(excludedDomain))) {
                    result += url;
                } else {
                    // Создаем ссылку
                    const link = document.createElement('a');
                    link.href = www ? `http://${url}` : url;
                    link.textContent = url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.className = 'text-primary fw-semibold';
                    result += link.outerHTML;
                }

                lastIndex = offset + url.length;
            } catch (error) {
                console.warn('Неверный формат URL:', error);
                result += url;
                lastIndex = offset + url.length;
            }
        });

        // Добавляем оставшийся текст
        if (lastIndex < text.length) {
            result += text.slice(lastIndex);
        }

        return result;
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
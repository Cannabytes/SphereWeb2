/**
 * CSRF Protection - Automatic token injection
 * This script automatically adds CSRF token to all forms and AJAX requests
 */

(function() {
    'use strict';

    // Получаем CSRF токен из meta тега
    function getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    }

    // Получаем имя поля CSRF токена
    const CSRF_TOKEN_NAME = '_csrf_token';
    const CSRF_HEADER_NAME = 'X-CSRF-Token';

    // Добавляем CSRF токен ко всем существующим формам при загрузке страницы
    function addCsrfToExistingForms() {
        const token = getCsrfToken();
        if (!token) return;

        document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(form => {
            // Проверяем, нет ли уже CSRF поля
            if (!form.querySelector(`input[name="${CSRF_TOKEN_NAME}"]`)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = CSRF_TOKEN_NAME;
                input.value = token;
                form.appendChild(input);
            }
        });
    }

    // Добавляем CSRF токен к новым формам (используем MutationObserver)
    function observeNewForms() {
        const token = getCsrfToken();
        if (!token) return;

        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // ELEMENT_NODE
                        // Проверяем, является ли сам элемент формой
                        if (node.tagName === 'FORM' && (node.method.toLowerCase() === 'post')) {
                            addCsrfToForm(node, token);
                        }
                        // Проверяем формы внутри добавленного элемента
                        if (node.querySelectorAll) {
                            node.querySelectorAll('form[method="post"], form[method="POST"]').forEach(form => {
                                addCsrfToForm(form, token);
                            });
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Добавить CSRF токен к конкретной форме
    function addCsrfToForm(form, token) {
        if (!form.querySelector(`input[name="${CSRF_TOKEN_NAME}"]`)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = CSRF_TOKEN_NAME;
            input.value = token;
            form.appendChild(input);
        }
    }

    // Обновление CSRF токена в meta теге и во всех формах
    function updateCsrfToken(newToken) {
        if (!newToken) return;
        
        // Обновляем meta тег
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
        
        // Обновляем все скрытые поля с CSRF токеном в формах
        document.querySelectorAll(`input[name="${CSRF_TOKEN_NAME}"]`).forEach(input => {
            input.value = newToken;
        });
    }

    // Обработка ответа для обновления токена
    function handleResponse(response) {
        try {
            let data = response;
            if (typeof response === 'string') {
                data = JSON.parse(response);
            }
            
            if (data && data.csrf_token) {
                updateCsrfToken(data.csrf_token);
            }
        } catch (e) {
            // Ignore parse errors
        }
    }

    function ensureFormDataToken(data, token) {
        if (!token || data == null) {
            return data;
        }

        if (data instanceof FormData) {
            if (!data.has(CSRF_TOKEN_NAME)) {
                data.append(CSRF_TOKEN_NAME, token);
            }
            return data;
        }

        if (typeof URLSearchParams !== 'undefined' && data instanceof URLSearchParams) {
            if (!data.has(CSRF_TOKEN_NAME)) {
                data.append(CSRF_TOKEN_NAME, token);
            }
            return data;
        }

        if (typeof data === 'string') {
            const trimmed = data.trim();
            // Не вмешиваемся в JSON payload
            if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
                return data;
            }

            if (!data.includes(`${encodeURIComponent(CSRF_TOKEN_NAME)}=`)) {
                const separator = data.length > 0 ? '&' : '';
                data += `${separator}${encodeURIComponent(CSRF_TOKEN_NAME)}=${encodeURIComponent(token)}`;
            }
            return data;
        }

        if (typeof data === 'object') {
            if (!(CSRF_TOKEN_NAME in data)) {
                data[CSRF_TOKEN_NAME] = token;
            }
            return data;
        }

        return data;
    }

    // Перехватываем XMLHttpRequest
    const originalXhrOpen = XMLHttpRequest.prototype.open;
    const originalXhrSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url, ...args) {
        this._method = method;
        this._url = url;
        return originalXhrOpen.call(this, method, url, ...args);
    };

    XMLHttpRequest.prototype.send = function(data) {
        if (this._method && this._method.toUpperCase() === 'POST') {
            const token = getCsrfToken();
            if (token) {
                this.setRequestHeader(CSRF_HEADER_NAME, token);
                const updatedData = ensureFormDataToken(data, token);
                if (updatedData !== undefined) {
                    data = updatedData;
                }
            }

            // Добавляем обработчик для обновления токена из ответа
            this.addEventListener('load', function() {
                handleResponse(this.responseText);
            });
        }
        return originalXhrSend.call(this, data);
    };

    // Перехватываем fetch
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Проверяем метод запроса
        const method = (options.method || 'GET').toUpperCase();
        
        if (method === 'POST' || method === 'PUT' || method === 'DELETE' || method === 'PATCH') {
            const token = getCsrfToken();
            if (token) {
                options.headers = options.headers || {};
                const updatedBody = ensureFormDataToken(options.body, token);
                if (updatedBody !== undefined) {
                    options.body = updatedBody;
                }
                
                // Проверяем тип headers
                if (options.headers instanceof Headers) {
                    options.headers.append(CSRF_HEADER_NAME, token);
                } else {
                    options.headers[CSRF_HEADER_NAME] = token;
                }
            }
        }
        
        // Оборачиваем промис для обновления токена из ответа
        return originalFetch.call(this, url, options).then(response => {
            // Клонируем response для чтения, так как body можно прочитать только один раз
            const clonedResponse = response.clone();
            
            // Пытаемся прочитать JSON и обновить токен
            clonedResponse.json().then(data => {
                if (data && data.csrf_token) {
                    updateCsrfToken(data.csrf_token);
                }
            }).catch(() => {
                // Ignore parse errors
            });
            
            return response;
        });
    };

    // Перехватываем jQuery AJAX (если jQuery доступен)
    if (window.jQuery) {
        jQuery(document).ready(function() {
            jQuery.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (settings.type && settings.type.toUpperCase() === 'POST') {
                        const isJsonPayload = settings.contentType && settings.contentType.toLowerCase().includes('application/json');
                        const token = getCsrfToken();
                        if (token) {
                            xhr.setRequestHeader(CSRF_HEADER_NAME, token);
                            if (settings.data && !isJsonPayload) {
                                const updated = ensureFormDataToken(settings.data, token);
                                if (updated !== undefined) {
                                    settings.data = updated;
                                }
                            } else if (!isJsonPayload && (!settings.csrf || settings.csrf !== false)) {
                                // Всегда устанавливаем data для POST запросов, чтобы CSRF токен был отправлен
                                // Но только если не отключено явно
                                settings.data = ensureFormDataToken({}, token);
                            }
                        }
                    }
                },
                complete: function(xhr, status) {
                    // Обновляем токен из ответа
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.csrf_token) {
                            updateCsrfToken(response.csrf_token);
                        }
                    } catch (e) {
                        // Ignore parse errors
                    }
                }
            });
        });
    }

    // Инициализация при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            addCsrfToExistingForms();
            observeNewForms();
        });
    } else {
        // DOM уже загружен
        addCsrfToExistingForms();
        observeNewForms();
    }

    // Экспортируем функции для использования в других скриптах
    window.CSRF = {
        getToken: getCsrfToken,
        getTokenName: function() { return CSRF_TOKEN_NAME; },
        getHeaderName: function() { return CSRF_HEADER_NAME; },
        updateToken: updateCsrfToken,
        addToForm: function(form) {
            const token = getCsrfToken();
            if (token) {
                addCsrfToForm(form, token);
            }
        }
    };

})();


// template/interface.js

document.addEventListener('DOMContentLoaded', () => {
    // По умолчанию автологин отключён, флаг может быть переустановлен при remoteConfigLoaded
    if (typeof window.__autologinEnabled === 'undefined') window.__autologinEnabled = false;

    // Проверяем режим предпросмотра
    const urlParams = new URLSearchParams(window.location.search);
    const isPreviewMode = urlParams.get('preview') === 'true';
    
    if (isPreviewMode) {
        console.log('[Preview] Preview mode activated - blocking window controls');
        
        // Блокируем кнопки управления окном
        const blockWindowControls = () => {
            const minimizeBtn = document.querySelector('.minimize-btn');
            const closeBtn = document.querySelector('.close-btn');
            
            if (minimizeBtn) {
                minimizeBtn.style.opacity = '0.3';
                minimizeBtn.style.cursor = 'not-allowed';
                minimizeBtn.style.pointerEvents = 'none';
                minimizeBtn.title = 'Недоступно в режиме предпросмотра';
            }
            
            if (closeBtn) {
                closeBtn.style.opacity = '0.3';
                closeBtn.style.cursor = 'not-allowed';
                closeBtn.style.pointerEvents = 'none';
                closeBtn.title = 'Используйте кнопку закрытия в окне предпросмотра';
            }
        };

        // Применяем блокировку сразу и через небольшую задержку
        blockWindowControls();
        setTimeout(blockWindowControls, 100);
        setTimeout(blockWindowControls, 500);
    }

    // Исправление: подтягиваем текущее состояние автологина из Go после перезагрузки окна
    try {
        if (window.go && window.go.main && window.go.main.App && typeof window.go.main.App.GetAutologinEnabled === 'function') {
            window.go.main.App.GetAutologinEnabled().then((enabled) => {
                if (typeof enabled === 'boolean') {
                    window.__autologinEnabled = enabled;
                }
            }).catch(() => { /* ignore */ });
        }
    } catch (_) { /* ignore */ }

    // DOM элементы
    const elements = {
        status: document.getElementById('status'),
        updateBtn: document.getElementById('update-game-btn'),
        fullUpdateBtn: document.getElementById('full-update-btn'),
        cancelBtn: document.getElementById('cancel-download-btn'),
        playBtn: document.getElementById('play-btn'),
        fileList: document.getElementById('file-list'),
        progressBar: document.getElementById('total-overall-progress'),
        progressPercentage: document.getElementById('progress-percentage'),

        // Элементы статистики в футере
        currentSpeed: document.getElementById('current-speed'),
        scanTime: document.getElementById('scan-time'),
        downloadTime: document.getElementById('download-time'),

        // Настройки
        settingsModal: document.getElementById('settings-modal'),
        settingsBtn: document.getElementById('settings-btn-launcher'),
        saveSettingsBtn: document.getElementById('save-settings-btn'),
        maxDownloadsInput: document.getElementById('setting-max-concurrent-downloads'),
        archiveCheckbox: document.getElementById('setting-archive-in-appdata'),
        closeModalBtn: document.getElementById('close-modal-btn'),
        cancelSettingsBtn: document.getElementById('cancel-settings-btn'),

        openAppDataArchiveLink: document.getElementById('open-appdata-archive-link'),
        langSwitcher: document.getElementById('lang-switcher'),
        launchButtonsContainer: document.getElementById('launch-buttons-container'),
        downloadedFilesCount: document.getElementById('downloaded-files-count'),

        // Контейнер для новостей
        newsContainer: document.querySelector('.news-container'),

        // Элементы обновления лаунчера
        updateNotification: document.getElementById('update-notification'),
        updateNotificationText: document.getElementById('update-notification-text'),
        updateNowBtn: document.getElementById('update-now-btn'),

        // Accounts modal & controls
        accountsModal: document.getElementById('accounts-modal'),
        accountsTableBody: document.getElementById('accounts-table-body'),
        addAccountName: document.getElementById('add-account-name'),
        addAccountPassword: document.getElementById('add-account-password'),
        addAccountCharacter: document.getElementById('add-account-character'),
        addAccountBtn: document.getElementById('add-account-btn'),
        closeAccountsBtn: document.getElementById('close-accounts-modal-btn'),
        cancelAccountsBtn: document.getElementById('cancel-accounts-btn'),
    };

    window.go.main.App.GetAppTitle().then(function (title) {
        document.title = title;
        const titleElements = document.querySelectorAll('.title');
        titleElements.forEach(element => {
            element.textContent = title;
        });
    });

    // Проставляем ссылки из конфигурации в элементы с классами accountLink / registrationLink / forumLink / supportLink / descriptionLink / donateLink
    const initConfigLinks = async () => {
        try {
            console.log('[Links] Initializing buttons from config...');
            
            // Wait for Wails to be ready (up to 3 seconds)
            for (let i = 0; i < 30; i++) {
                if (window.go?.main?.App?.GetSupportLink) break;
                await new Promise(r => setTimeout(r, 100));
            }

            if (!window.go?.main?.App?.GetSupportLink) {
                console.error('[Links] Wails methods not found after waiting');
                return;
            }

            // Получаем ссылки. Используем try/catch для каждой, чтобы отсутствие одной не ломало всё.
            const getLink = async (name) => {
                try {
                    const func = window.go?.main?.App?.[name];
                    if (typeof func === 'function') {
                        return await func();
                    }
                    console.warn(`[Links] Method window.go.main.App.${name} not found`);
                    return null;
                } catch (err) {
                    console.error(`[Links] Error calling ${name}:`, err);
                    return null;
                }
            };

            const [acc, reg, forum, support, desc, donate] = await Promise.all([
                getLink('GetAccountLink'),
                getLink('GetRegistrationLink'),
                getLink('GetForumLink'),
                getLink('GetSupportLink'),
                getLink('GetDescriptionLink'),
                getLink('GetDonateLink')
            ]);

            console.log('[Links] Loaded URLs:', { acc, reg, forum, support, desc, donate });

            const applyLink = (selector, url) => {
                const elements = document.querySelectorAll(`.${selector}`);
                if (!elements.length) {
                    // console.log(`[Links] Elements with selector .${selector} not found in this template`);
                    return;
                }

                if (!url) {
                    console.log(`[Links] Hiding ${selector} because URL is empty`);
                    elements.forEach(el => el.style.display = 'none');
                    return;
                }
                
                console.log(`[Links] Showing ${selector} with URL: ${url}`);
                elements.forEach(el => {
                    // Явно выставляем display, используя !important для обхода стилей шаблонов
                    // Для jellyfish-btn обычно используется flex
                    el.style.setProperty('display', 'flex', 'important'); 
                    
                    // Удаляем старые слушатели (клонированием), чтобы не множились и сбросить поведение браузера по умолчанию
                    const newEl = el.cloneNode(true);
                    el.parentNode.replaceChild(newEl, el);
                    
                    newEl.addEventListener('click', (e) => {
                        e.preventDefault();
                        console.log(`[Links] Opening URL: ${url}`);
                        if (window.go?.main?.App?.OpenURLInDefaultBrowser) {
                            window.go.main.App.OpenURLInDefaultBrowser(url);
                        } else {
                            window.open(url, '_blank');
                        }
                    });

                    // Если это ссылка, на всякий случай проставим href
                    if (newEl.tagName === 'A') {
                        newEl.setAttribute('href', url);
                        newEl.setAttribute('target', '_blank');
                    }
                });
            };

            applyLink('accountLink', acc);
            applyLink('registrationLink', reg);
            applyLink('forumLink', forum);
            applyLink('supportLink', support);
            applyLink('descriptionLink', desc);
            applyLink('donateLink', donate);
        } catch (e) {
            console.error('[Links] Critical error applying links:', e);
        }
    };

    // Запускаем после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initConfigLinks);
    } else {
        initConfigLinks();
    }

    // Ensure fullscreen is restored after minimise when window is refocused/shown
    let __restoreScheduled = false;
    function scheduleRestoreFullscreenIfNeeded() {
        if (__restoreScheduled) return;
        __restoreScheduled = true;
        setTimeout(() => {
            __restoreScheduled = false;
            try {
                if (window.go && window.go.main && window.go.main.App && typeof window.go.main.App.RestoreFullscreenIfNeeded === 'function') {
                    if (window.runtime && typeof window.runtime.WindowIsMinimised === 'function') {
                        window.runtime.WindowIsMinimised().then(isMin => {
                            if (!isMin) {
                                window.go.main.App.RestoreFullscreenIfNeeded().catch(() => { });
                            }
                        }).catch(() => {
                            window.go.main.App.RestoreFullscreenIfNeeded().catch(() => { });
                        });
                    } else {
                        window.go.main.App.RestoreFullscreenIfNeeded().catch(() => { });
                    }
                }
            } catch (_) { /* noop */ }
        }, 150);
    }

    window.addEventListener('focus', scheduleRestoreFullscreenIfNeeded);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') scheduleRestoreFullscreenIfNeeded();
    });

    // Инициализируем слушатель обновлений ДО всех других операций
    function setupUpdateListener() {
        console.log('[UpdateListener] Начало инициализации слушателя обновлений...');

        // Проверяем наличие всех необходимых элементов
        if (!elements.updateNotification) {
            console.error('[UpdateListener] Элемент updateNotification не найден!');
            return;
        }
        if (!elements.updateNotificationText) {
            console.error('[UpdateListener] Элемент updateNotificationText не найден!');
            return;
        }
        if (!elements.updateNowBtn) {
            console.error('[UpdateListener] Элемент updateNowBtn не найден!');
            return;
        }

        // Проверяем наличие runtime
        if (!window.runtime) {
            console.error('[UpdateListener] window.runtime не найден! Повторная попытка через 500мс...');
            setTimeout(setupUpdateListener, 500);
            return;
        }

        console.log('[UpdateListener] Все элементы найдены, подписываемся на событие launcher:updateAvailable');

        // Подписываемся на событие обновления лаунчера
        window.runtime.EventsOn("launcher:updateAvailable", (updateInfo) => {
            console.log('[UpdateListener] Получена информация об обновлении лаунчера:', updateInfo);

            if (!updateInfo) {
                console.warn('[UpdateListener] Получена пустая информация об обновлении');
                return;
            }

            if (!updateInfo.newVersion || !updateInfo.downloadLink) {
                console.warn('[UpdateListener] Неполная информация об обновлении:', updateInfo);
                return;
            }

            console.log('[UpdateListener] Показываем уведомление об обновлении...');

            // Обновляем текст уведомления
            elements.updateNotificationText.textContent = i18n.t('updateNeeded', { newVersion: updateInfo.newVersion });

            // Показываем уведомление
            elements.updateNotification.classList.add('visible');
            console.log('[UpdateListener] Уведомление показано с классом visible');

            // Пересоздаем обработчик кнопки обновления (избегаем множественных слушателей)
            const newUpdateBtn = elements.updateNowBtn.cloneNode(true);
            elements.updateNowBtn.parentNode.replaceChild(newUpdateBtn, elements.updateNowBtn);
            elements.updateNowBtn = newUpdateBtn;

            elements.updateNowBtn.addEventListener('click', async () => {
                console.log('[UpdateListener] Пользователь нажал кнопку обновления');
                elements.updateNowBtn.disabled = true;
                elements.updateNotificationText.textContent = "Проверка возможности обновления...";

                // Добавляем индикатор загрузки
                const originalText = elements.updateNowBtn.innerHTML;
                elements.updateNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обновление...';

                try {
                    console.log('[UpdateListener] Вызываем DownloadAndApplyUpdate...');
                    elements.updateNotificationText.textContent = "Загрузка обновления... Это может занять несколько минут.";

                    // Добавляем таймаут для функции (5 минут)
                    const updatePromise = window.go.main.App.DownloadAndApplyUpdate(updateInfo.downloadLink);
                    const timeoutPromise = new Promise((_, reject) => {
                        setTimeout(() => reject(new Error('Превышено время ожидания (5 минут)')), 5 * 60 * 1000);
                    });

                    await Promise.race([updatePromise, timeoutPromise]);

                    // Если дошли до этой строки, значит что-то пошло не так
                    // Обычно приложение должно закрыться автоматически
                    console.warn('[UpdateListener] Обновление завершилось, но приложение не закрылось');
                    elements.updateNotificationText.textContent = "Обновление завершено. Пожалуйста, перезапустите лаунчер вручную.";

                } catch (error) {
                    console.error('[UpdateListener] Ошибка во время применения обновления:', error);

                    let errorMessage = 'Неизвестная ошибка';
                    if (error && typeof error === 'object' && error.message) {
                        errorMessage = error.message;
                    } else if (typeof error === 'string') {
                        errorMessage = error;
                    } else {
                        errorMessage = String(error);
                    }

                    console.error('[UpdateListener] Детали ошибки:', {
                        error: error,
                        message: errorMessage,
                        type: typeof error,
                        stack: error?.stack
                    });

                    // Показываем детальное сообщение об ошибке
                    const detailedMessage = `Не удалось обновить лаунчер:\n\n${errorMessage}\n\nПопробуйте:\n1. Перезапустить лаунчер от имени администратора\n2. Скачать обновление вручную с сайта\n3. Проверить интернет-соединение`;

                    showAlert({ title: 'Ошибка', message: detailedMessage, icon: 'error' });
                    elements.updateNotificationText.textContent = `Ошибка: ${errorMessage}`;

                    // Восстанавливаем кнопку
                    elements.updateNowBtn.innerHTML = originalText;
                    elements.updateNowBtn.disabled = false;
                }
            });

            console.log('[UpdateListener] Обработчик кнопки обновления назначен');
        });

        // ИСПРАВЛЕНИЕ: Регистрируем слушатель remoteConfigLoaded здесь же
        window.runtime.EventsOn('remoteConfigLoaded', (remoteConfig) => {
            console.log('[Interface] Получена удаленная конфигурация:', remoteConfig);
            // Перезагружаем новости и кнопки только если конфигурация действительно изменилась
            const currentLang = window.i18n.currentLang || 'ru';
            loadNews(currentLang);

            // Устанавливаем флаг автологина
            try {
                const al = remoteConfig && remoteConfig.autologin;
                const enabled = !!(al && ((Array.isArray(al.fields) && al.fields.length > 0) || (typeof al.pattern === 'string' && al.pattern.trim() !== '')));
                window.__autologinEnabled = enabled;
                console.log('[Interface] autologin enabled =', window.__autologinEnabled);
            } catch (e) {
                window.__autologinEnabled = false;
            }

            // ИСПРАВЛЕНИЕ: Используем безопасное создание кнопок
            safeCreateLaunchButtons(currentLang, 'remoteConfigLoaded');
        });

        console.log('[UpdateListener] Слушатель событий успешно инициализирован');
    }

    // ИСПРАВЛЕНИЕ: Вызываем setupUpdateListener сразу же
    setupUpdateListener();

    let dynamicLaunchButtons = [];
    let isManualLanguageChange = false;
    let isInitialStateLoaded = false;

    // НОВОЕ: Флаги для предотвращения множественных вызовов
    let isCreatingButtons = false;
    let buttonsCreationPromise = null;

    // ===================================================================
    // ФУНКЦИИ ДЛЯ РАБОТЫ С НОВОСТЯМИ
    // ===================================================================

    /**
     * Создает HTML-элемент для одной новости.
     * @param {object} newsItem - Объект новости, полученный от Go.
     * @returns {HTMLElement} Готовый div-элемент для вставки в DOM.
     */
    function createNewsElement(newsItem) {
        const newsDiv = document.createElement('div');
        newsDiv.className = 'news-item';

        // Если у новости есть ссылка, делаем весь блок кликабельным
        if (newsItem.link && newsItem.link !== '') {
            newsDiv.style.cursor = 'pointer';
            newsDiv.addEventListener('click', () => {
                // Используем API Wails для безопасного открытия ссылки в браузе по умолчанию
                if (window.go && window.go.main && window.go.main.App && typeof window.go.main.App.OpenURLInDefaultBrowser === 'function') {
                    window.go.main.App.OpenURLInDefaultBrowser(newsItem.link)
                        .catch(err => console.error(`[News] Ошибка при открытии ссылки ${newsItem.link}:`, err));
                }
            });
        }

        // Добавляем дату, если она есть. Вставляем ее перед заголовком.
        if (newsItem.date && newsItem.date !== '') {
            const dateDiv = document.createElement('div');
            dateDiv.className = 'news-date';
            dateDiv.textContent = newsItem.date;
            newsDiv.appendChild(dateDiv);
        }

        const headerDiv = document.createElement('div');
        headerDiv.className = 'news-header';
        headerDiv.textContent = newsItem.name || 'Без заголовка';
        newsDiv.appendChild(headerDiv);

        const textDiv = document.createElement('div');
        textDiv.className = 'news-text';
        textDiv.textContent = newsItem.description || 'Нет описания.';
        newsDiv.appendChild(textDiv);

        return newsDiv;
    }

    /**
     * Асинхронно загружает новости для указанного языка и отображает их.
     * @param {string} language - Код языка (например, 'ru', 'en').
     */
    async function loadNews(language) {
        if (!elements.newsContainer) {
            console.warn('[News] Контейнер для новостей (.news-container) не найден в DOM.');
            return;
        }

        // Показываем заглушку о загрузке (локализовано)
        const loadingText = (window.i18n && typeof window.i18n.t === 'function') ? window.i18n.t('newsLoading') : 'Loading news...';
        elements.newsContainer.innerHTML = `<div class="news-item"><div class="news-text">${loadingText}</div></div>`;

        try {
            console.log(`[News] Запрос новостей для языка: ${language}`);
            const newsList = await window.go.main.App.GetNews(language);

            elements.newsContainer.innerHTML = ''; // Очищаем контейнер

            if (newsList && newsList.length > 0) {
                console.log(`[News] Загружено ${newsList.length} новостей.`);
                newsList.forEach(newsItem => {
                    const newsElement = createNewsElement(newsItem);
                    elements.newsContainer.appendChild(newsElement);
                });
            } else {
                console.log('[News] Новости не найдены, показываем заглушку.');
                const noNewsDiv = document.createElement('div');
                noNewsDiv.className = 'news-item';
                const noTitle = (window.i18n && i18n.t) ? i18n.t('newsNotFoundTitle') : 'No news found';
                const noText = (window.i18n && i18n.t) ? i18n.t('newsNotFoundText') : 'News for the selected language are currently unavailable.';
                noNewsDiv.innerHTML = `
                    <div class="news-header">${noTitle}</div>
                    <div class="news-text">${noText}</div>
                `;
                elements.newsContainer.appendChild(noNewsDiv);
            }
        } catch (error) {
            console.error('[News] Ошибка при загрузке новостей:', error);
            const errTitle = (window.i18n && i18n.t) ? i18n.t('newsLoadErrorTitle') : 'Load error';
            const errText = (window.i18n && i18n.t) ? i18n.t('newsLoadErrorText') : 'Failed to load news. Please check your internet connection.';
            elements.newsContainer.innerHTML = `
                <div class="news-item">
                    <div class="news-header">${errTitle}</div>
                    <div class="news-text">${errText}</div>
                </div>
            `;
        }
    }

    // ИСПРАВЛЕННАЯ ФУНКЦИЯ: Безопасное создание кнопок с защитой от множественных вызовов
    async function safeCreateLaunchButtons(language = 'ru', source = 'unknown') {
        console.log(`[Interface] safeCreateLaunchButtons вызвана для языка ${language} из источника: ${source}`);

        // Если уже создаем кнопки, возвращаем существующий промис
        if (isCreatingButtons && buttonsCreationPromise) {
            console.log(`[Interface] Кнопки уже создаются, ожидаем завершения...`);
            return buttonsCreationPromise;
        }

        // Устанавливаем флаг и создаем промис
        isCreatingButtons = true;
        buttonsCreationPromise = createLaunchButtons(language);

        try {
            await buttonsCreationPromise;
        } catch (error) {
            console.error(`[Interface] Ошибка создания кнопок из источника ${source}:`, error);
        } finally {
            // Сбрасываем флаги
            isCreatingButtons = false;
            buttonsCreationPromise = null;
        }
    }

    // ОБНОВЛЕННАЯ ФУНКЦИЯ: Функция для создания кнопок запуска с поддержкой локализации
    async function createLaunchButtons(language = 'ru') {
        if (!elements.launchButtonsContainer) return;

        console.log(`[Interface] Начинаем создание кнопок для языка: ${language}`);

        // Очищаем контейнер и массив ссылок
        elements.launchButtonsContainer.innerHTML = '';
        dynamicLaunchButtons = [];

        if (window.go && window.go.main && window.go.main.App && typeof window.go.main.App.GetLocalizedLaunchApplications === 'function') {
            try {
                console.log(`[Interface] Запрос локализованных кнопок для языка: ${language}`);
                const applications = await window.go.main.App.GetLocalizedLaunchApplications(language);

                if (applications && applications.length > 0) {
                    console.log(`[Interface] Получено ${applications.length} приложений для создания кнопок`);

                    applications.forEach((appConfig, index) => {
                        // Split container
                        const split = document.createElement('div');
                        split.className = 'split-button-container';

                        // Main play button
                        const mainBtn = document.createElement('button');
                        mainBtn.className = 'jellyfish-btn action-primary split-button-main';
                        mainBtn.id = `launch-btn-${appConfig.exe.replace(/[^a-zA-Z0-9]/g, '-')}-${index}`;
                        mainBtn.innerHTML = `<i class="fas fa-play"></i> ${appConfig.name || 'Играть'}`;
                        mainBtn.disabled = true;
                        mainBtn.dataset.exe = appConfig.exe;
                        mainBtn.dataset.args = appConfig.args;
                        mainBtn.dataset.originalName = appConfig.name || 'Играть';
                        mainBtn.dataset.selectedAccount = '';
                        // ВАЖНО: сохраняем идентификатор записи запуска, чтобы использовать LaunchApplicationByID
                        if (typeof appConfig.id !== 'undefined') {
                            mainBtn.dataset.appId = String(appConfig.id);
                        } else {
                            // fallback на порядок, если по какой-то причине id не пришел
                            mainBtn.dataset.appId = String(index);
                        }

                        // Only show account dropdown when autologin is enabled by remote config
                        let toggleBtn = null;
                        let menu = null;
                        if (window.__autologinEnabled) {
                            toggleBtn = document.createElement('button');
                            toggleBtn.className = 'jellyfish-btn action-primary split-button-toggle';
                            toggleBtn.setAttribute('aria-label', 'Options');
                            toggleBtn.title = 'Опции';
                            toggleBtn.innerHTML = `<i class="fas fa-chevron-up"></i>`;

                            // Dropdown menu
                            menu = document.createElement('div');
                            menu.className = 'split-button-menu';

                            // First item: manage accounts
                            const manageBtn = document.createElement('button');
                            manageBtn.className = 'dropdown-item';
                            manageBtn.innerHTML = `<i class="fas fa-users-cog"></i> <span data-i18n="manageAccounts">Управление аккаунтами</span>`;
                            manageBtn.addEventListener('click', (e) => { e.stopPropagation(); openAccountsModal(); hideAllSplitMenus(); });
                            menu.appendChild(manageBtn);

                            // Separator like spacing via thin element
                            const sep = document.createElement('div');
                            sep.style.cssText = 'height:1px;background:rgba(255,255,255,0.15);margin:4px 8px;';
                            menu.appendChild(sep);

                            // Accounts list will be (re)built on open
                            toggleBtn.addEventListener('click', async (e) => {
                                e.stopPropagation();
                                // Refresh accounts each open
                                await buildAccountsMenuItems(menu, mainBtn);
                                // Toggle visibility
                                const isShown = menu.classList.contains('show');
                                hideAllSplitMenus();
                                if (!isShown) menu.classList.add('show');
                            });
                        } else {
                            // No toggle visually -> make main button fully rounded via class
                            split.classList.add('no-toggle');
                        }

                        // Main button click: launch with selected account (if any)
                        mainBtn.addEventListener('click', async () => {
                            if (mainBtn.disabled) return;
                            const acc = mainBtn.dataset.selectedAccount || '';
                            const appIdRaw = mainBtn.dataset.appId;
                            const appId = appIdRaw ? parseInt(appIdRaw, 10) : NaN;
                            try {
                                if (!isNaN(appId) && window.go?.main?.App?.CheckForUpdatesAndLaunchGameByID) {
                                    const result = await window.go.main.App.CheckForUpdatesAndLaunchGameByID(appId, acc);
                                    if (result === 'UPDATING') {
                                        showAlert && showAlert({ type: 'info', message: (window.i18n?.t ? window.i18n.t('checkingUpdates') : 'Проверка обновлений...') });
                                    }
                                } else if (!isNaN(appId) && window.go?.main?.App?.LaunchApplicationByID) {
                                    await window.go.main.App.LaunchApplicationByID(appId, acc);
                                } else {
                                    // Дальний fallback: старый путь через exe
                                    const exePath = mainBtn.dataset.exe;
                                    const appArgs = mainBtn.dataset.args;
                                    if (acc && window.go?.main?.App?.LaunchApplicationWithAccount) {
                                        await window.go.main.App.LaunchApplicationWithAccount(exePath, acc);
                                    } else if (window.go?.main?.App?.CheckForUpdatesAndLaunchGame) {
                                        await window.go.main.App.CheckForUpdatesAndLaunchGame([exePath, appArgs]);
                                    } else if (window.go?.main?.App?.LaunchApplication) {
                                        await window.go.main.App.LaunchApplication(exePath, appArgs);
                                    } else {
                                        throw new Error('API запуска недоступно');
                                    }
                                }
                            } catch (err) {
                                console.error('[Interface] Ошибка запуска:', err);
                                showAlert && showAlert({ title: 'Ошибка запуска', message: String(err?.message || err), icon: 'error' });
                            }
                        });

                        // Compose
                        split.appendChild(mainBtn);
                        if (toggleBtn) split.appendChild(toggleBtn);
                        if (menu) split.appendChild(menu);
                        elements.launchButtonsContainer.appendChild(split);
                        dynamicLaunchButtons.push(mainBtn);
                    });

                    console.log(`[Interface] ✅ Успешно создано ${dynamicLaunchButtons.length} кнопок для языка ${language}`);
                } else {
                    console.log("[Interface] Конфигурация для кнопок запуска отсутствует или пуста.");
                }

                const currentState = window.Launcher ? window.Launcher.getState() : null;
                if (currentState) {
                    updateLaunchButtonStates(currentState);
                }
            } catch (err) {
                console.error('[Interface] Ошибка при получении локализованных конфигураций запуска:', err);
                if (elements.launchButtonsContainer) {
                    elements.launchButtonsContainer.innerHTML = '<p style="color: #c07060; font-size: 13px; align-self: center;">Ошибка загрузки кнопок.</p>';
                }
            }
        } else {
            console.error('[Launcher] Функция GetLocalizedLaunchApplications не найдена в Go бэкенде.');
            if (elements.launchButtonsContainer) {
                elements.launchButtonsContainer.innerHTML = '<p style="color: #c07060; font-size: 13px; align-self: center;">Не удалось получить конфигурацию.</p>';
            }
        }
    }

    // Функция для обновления состояния кнопок запуска
    function updateLaunchButtonStates(state) {
        if (!state) return;
        let shouldBeDisabled;
        switch (state.status) {
            case 'idle':
            case 'completed':
            case 'cancelled':
                shouldBeDisabled = false;
                break;
            case 'checking':
            case 'downloading':
            case 'extracting':
            case 'error':
            default:
                shouldBeDisabled = true;
                break;
        }
        dynamicLaunchButtons.forEach(button => {
            if (button.disabled !== shouldBeDisabled) {
                button.disabled = shouldBeDisabled;
            }
        });
    }

    // Helpers to manage split menus
    function hideAllSplitMenus() {
        document.querySelectorAll('.split-button-menu.show').forEach(m => m.classList.remove('show'));
    }

    window.addEventListener('click', () => hideAllSplitMenus());

    async function buildAccountsMenuItems(menuEl, mainBtn) {
        // Remove old dynamic items (keep first two: manage + separator)
        Array.from(menuEl.children).forEach((child, idx) => { if (idx > 1) child.remove(); });
        try {
            const accounts = await window.go.main.App.GetAccounts();
            const selected = mainBtn.dataset.selectedAccount || '';
            if (!accounts || accounts.length === 0) {
                const emptyBtn = document.createElement('button');
                emptyBtn.className = 'dropdown-item';
                emptyBtn.disabled = true;
                emptyBtn.innerHTML = `<i class="fas fa-info-circle"></i> <span data-i18n="noAccounts">Список пуст</span>`;
                menuEl.appendChild(emptyBtn);
                window.i18n && window.i18n.applyTranslations && window.i18n.applyTranslations();
                return;
            }
            accounts.forEach(acc => {
                const item = document.createElement('button');
                item.className = 'dropdown-item';
                const isSel = selected && selected === acc.name;
                const charLabel = acc.character || acc.name;
                item.innerHTML = `${isSel ? '<i class=\"fas fa-check\"></i>' : '<i class=\"fas fa-user\"></i>'} ${charLabel}`;
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Toggle selection if clicked selected again -> deselect
                    if (isSel) {
                        mainBtn.dataset.selectedAccount = '';
                        mainBtn.innerHTML = `<i class=\"fas fa-play\"></i> ${mainBtn.dataset.originalName}`;
                    } else {
                        mainBtn.dataset.selectedAccount = acc.name;
                        mainBtn.innerHTML = `<i class=\"fas fa-play\"></i> ${mainBtn.dataset.originalName} (${charLabel})`;
                    }
                    hideAllSplitMenus();
                });
                menuEl.appendChild(item);
                // If this is the selected account, add a remove option under it
                if (isSel) {
                    const remove = document.createElement('button');
                    remove.className = 'dropdown-item';
                    remove.innerHTML = `<i class=\"fas fa-times-circle\"></i> <span data-i18n=\"removeSelected\">Убрать</span>`;
                    remove.addEventListener('click', (e) => {
                        e.stopPropagation();
                        mainBtn.dataset.selectedAccount = '';
                        mainBtn.innerHTML = `<i class=\"fas fa-play\"></i> ${mainBtn.dataset.originalName}`;
                        hideAllSplitMenus();
                    });
                    menuEl.appendChild(remove);
                }
            });
            window.i18n && window.i18n.applyTranslations && window.i18n.applyTranslations();
        } catch (e) {
            console.error('GetAccounts failed', e);
        }
    }

    if (elements.settingsModal) {
        elements.settingsModal.addEventListener('click', (event) => {
            // Проверяем, был ли клик именно по нашей ссылке
            if (event.target && event.target.id === 'open-appdata-archive-link') {
                event.preventDefault(); // Предотвращаем стандартное поведение
                event.stopPropagation(); // Останавливаем всплытие события

                console.log('[Interface] Клик по ссылке AppData обнаружен.');

                if (window.go && window.go.main.App.OpenAppDataArchiveFolder) {
                    window.go.main.App.OpenAppDataArchiveFolder().catch(err => console.error(err));
                }
            }
        });
    }

    let isSettingsModalOpen = false;
    let isUserEditingSettings = false;
    let originalSettings = null;

    function isSettingsModalVisible() {
        return elements.settingsModal && (elements.settingsModal.style.display === 'flex' || elements.settingsModal.style.display === 'block');
    }

    function loadSettingsToModal(settings) {
        if (!settings) return;
        if (elements.maxDownloadsInput) elements.maxDownloadsInput.value = String(settings.maxConcurrentDownloads);
        if (elements.archiveCheckbox) elements.archiveCheckbox.checked = settings.archiveDownloadsInAppData;
    }

    function saveOriginalSettings() {
        if (!elements.maxDownloadsInput || !elements.archiveCheckbox) return;
        originalSettings = {
            maxConcurrentDownloads: parseInt(elements.maxDownloadsInput.value) || 3,
            archiveDownloadsInAppData: elements.archiveCheckbox.checked,
        };
    }

    function restoreOriginalSettings() {
        if (!originalSettings) return;
        if (elements.maxDownloadsInput) elements.maxDownloadsInput.value = String(originalSettings.maxConcurrentDownloads);
        if (elements.archiveCheckbox) elements.archiveCheckbox.checked = originalSettings.archiveDownloadsInAppData;
    }

    function setupSettingsInputHandlers() {
        [elements.maxDownloadsInput, elements.archiveCheckbox].forEach(element => {
            if (element) {
                const eventType = element.type === 'checkbox' ? 'change' : 'input';
                element.addEventListener(eventType, () => { isUserEditingSettings = true; });
            }
        });
    }

    if (window.Launcher && typeof window.Launcher.onStateChange === 'function') {
        Launcher.onStateChange(updateUI);
    } else {
        console.warn("[Launcher] window.Launcher.onStateChange не найден. Попытка подписки через window.runtime.EventsOn.");
        if (window.runtime && typeof window.runtime.EventsOn === 'function') {
            try {
                window.runtime.EventsOn('app:ready', (payload) => {
                    console.log('[Interface] Получено событие app:ready:', payload);

                    if (payload && payload.initialPage) {
                        if (!window.location.pathname.endsWith(payload.initialPage)) {
                            console.log(`[Interface] Перенаправление на начальную страницу: ${payload.initialPage}`);
                            window.location.href = payload.initialPage;
                            return;
                        }
                    }

                    if (payload && typeof payload === 'object') {
                        if (payload.defaultLang && !isManualLanguageChange) {
                            console.log(`[Interface] Установка языка из app:ready: ${payload.defaultLang}`);
                            window.i18n.setLanguage(payload.defaultLang);
                            if (elements.langSwitcher) elements.langSwitcher.value = payload.defaultLang;

                            // ИСПРАВЛЕНИЕ: Используем безопасное создание кнопок
                            loadNews(payload.defaultLang);
                            safeCreateLaunchButtons(payload.defaultLang, 'app:ready');

                            isInitialStateLoaded = true;
                        }
                        if (payload.initialState) {
                            updateUI(payload.initialState);
                        }
                    }
                });

                window.runtime.EventsOn('stateUpdate', (state) => {
                    updateUI(Array.isArray(state) && state.length === 1 ? state[0] : state);
                });

                if (window.go && window.go.main.App.GetState) {
                    window.go.main.App.GetState().then(updateUI).catch(err => console.error(err));
                }
            } catch (e) {
                console.error("[Launcher] Не удалось подписаться на события:", e);
            }
        } else {
            console.error("[Launcher] Не удалось настроить получение обновлений состояния.");
        }
    }

    function formatBytes(bytes, decimals = 1) {
        if (bytes === null || typeof bytes === 'undefined' || isNaN(bytes) || bytes < 0) return '0 Байт';
        if (bytes === 0) return '0 Байт';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Байт', 'КБ', 'МБ', 'ГБ', 'ТБ'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        if (i >= sizes.length || i < 0) return `${bytes} Байт`;
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    /**
     * Форматирует скорость из байт/сек в человекочитаемый формат.
     * Версия 2: Улучшенное отображение низких скоростей.
     * @param {number} bytes - Скорость в байтах в секунду.
     * @returns {string} - Отформатированная строка (напр. "0.49 КБ/с" или "1.25 МБ/с").
     */
    function formatSpeed(bytes) {
        // Если скорость отсутствует или равна нулю, показываем 0.
        if (!bytes || bytes <= 0) {
            return '0 КБ/с';
        }

        const k = 1024;
        const sizes = ['Байт/с', 'КБ/с', 'МБ/с', 'ГБ/с', 'ТБ/с'];

        // Если скорость меньше 1 КБ/с, но больше нуля,
        // показываем её как дробную часть от КБ/с для наглядности.
        if (bytes < k) {
            // (bytes / k) даст значение от 0.00 до 0.99
            // toFixed(2) округлит до двух знаков, например, до "0.49"
            return `${(bytes / k).toFixed(2)} КБ/с`;
        }

        // Для скоростей 1 КБ/с и выше используем прежнюю логику
        // для выбора между КБ/с, МБ/с, ГБ/с и т.д.
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        const formattedValue = parseFloat((bytes / Math.pow(k, i)).toFixed(2));

        // sizes[1] это 'КБ/с', sizes[2] это 'МБ/с' и так далее.
        return `${formattedValue} ${sizes[i]}`;
    }

    function formatTime(seconds) {
        if (!seconds || seconds <= 0) return '--';
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        if (hours > 0) return `${hours}ч ${minutes}м ${secs}с`;
        if (minutes > 0) return `${minutes}м ${secs}с`;
        return `${secs}с`;
    }

    function updateStatistics(state) {
        if (!state || !state.timing) {
            if (elements.currentSpeed) elements.currentSpeed.textContent = '0 КБ/с';
            if (elements.scanTime) elements.scanTime.textContent = '--';
            if (elements.downloadTime) elements.downloadTime.textContent = '--';
            return;
        }
        if (elements.downloadedFilesCount) {
            const progress = state.progress;
            const status = state.status;
            let filesCountText = '-- / --';
            let isActive = false;
            if (progress) {
                if (['downloading', 'extracting', 'completed', 'cancelled', 'error'].includes(status)) {
                    if ((progress.filesTotal || 0) > 0) {
                        filesCountText = i18n.t('filesProgress', { completed: progress.filesCompleted || 0, total: progress.filesTotal || 0 });
                        isActive = ['downloading', 'extracting'].includes(status);
                    }
                } else if (status === 'idle' && progress.totalToCheck > 0 && progress.filesTotal === 0 && !state.lastError) {
                    filesCountText = i18n.t('filesProgress', { completed: progress.totalToCheck || 0, total: progress.totalToCheck || 0 });
                }
            }
            if (elements.downloadedFilesCount.textContent !== filesCountText) {
                elements.downloadedFilesCount.textContent = filesCountText;
            }
            elements.downloadedFilesCount.classList.toggle('active', isActive);
        }
        const timing = state.timing;
        const status = state.status;
        if (elements.currentSpeed) {
            const speed = timing.currentTotalSpeed || 0;
            elements.currentSpeed.textContent = formatSpeed(speed);
            elements.currentSpeed.classList.toggle('active', speed > 0 && ['downloading', 'extracting'].includes(status));
        }
        if (elements.scanTime) {
            if (status === 'completed' && timing.checkingDuration > 0) {
                elements.scanTime.textContent = formatTime(timing.checkingDuration);
                elements.scanTime.classList.add('completed');
                elements.scanTime.classList.remove('active');
            } else if (status === 'checking') {
                elements.scanTime.textContent = timing.currentCheckingTime > 0 ? formatTime(timing.currentCheckingTime) : 'Запуск...';
                elements.scanTime.classList.add('active');
                elements.scanTime.classList.remove('completed');
            } else if (timing.checkingDuration > 0) {
                elements.scanTime.textContent = formatTime(timing.checkingDuration);
                elements.scanTime.classList.remove('active');
                elements.scanTime.classList.add('completed');
            } else {
                elements.scanTime.textContent = '--';
                elements.scanTime.classList.remove('active', 'completed');
            }
        }
        if (elements.downloadTime) {
            if (status === 'completed' && timing.downloadDuration > 0) {
                elements.downloadTime.textContent = formatTime(timing.downloadDuration);
                elements.downloadTime.classList.add('completed');
                elements.downloadTime.classList.remove('active');
            } else if (['downloading', 'extracting'].includes(status)) {
                elements.downloadTime.textContent = timing.currentDownloadTime > 0 ? formatTime(timing.currentDownloadTime) : 'Запуск...';
                elements.downloadTime.classList.add('active');
                elements.downloadTime.classList.remove('completed');
            } else if (timing.downloadDuration > 0) {
                elements.downloadTime.textContent = formatTime(timing.downloadDuration);
                elements.downloadTime.classList.remove('active');
                elements.downloadTime.classList.add('completed');
            } else {
                elements.downloadTime.textContent = '--';
                elements.downloadTime.classList.remove('active', 'completed');
            }
        }
    }

    function formatStatusMessage(state) {
        if (!state) return i18n.t('statusUnknown');
        const status = state.status;
        const progress = state.progress;

        switch (status) {
            case 'idle':
                if (state.lastError) return i18n.t('statusError', { error: state.lastError });
                if (progress && progress.totalToCheck > 0 && progress.filesTotal === 0) {
                    return i18n.t('statusAllFilesUpToDate');
                }
                return i18n.t('statusReady');

            case 'checking':
                return i18n.t('statusChecking', {
                    checked: progress?.checkingFiles || 0,
                    total: progress?.totalToCheck || 0
                });

            case 'downloading':
                return i18n.t('statusDownloadingTotal', {
                    downloadedSize: formatBytes(progress?.bytesCompleted || 0),
                    totalSize: formatBytes(progress?.bytesTotal || 0)
                });

            case 'extracting':
                return i18n.t('statusExtracting', {
                    remaining: (progress?.filesTotal || 0) - (progress?.filesCompleted || 0)
                });

            case 'completed':
                // УЛУЧШЕНИЕ: Различаем типы завершения
                if (progress && progress.totalToCheck === 0 && progress.filesTotal === 0) {
                    return i18n.t('noFilesForUpdate'); // "Нет файлов для обновления"
                }
                return i18n.t('statusCompleted');

            case 'cancelled':
                return i18n.t('statusCancelled');

            case 'error':
                return i18n.t('statusError', { error: state.lastError || i18n.t('unknownError') });

            default:
                return state.statusMessage || i18n.t('statusUnknown');
        }
    }

    function updateUI(state) {
        if (!state || typeof state !== 'object') {
            console.error("[Launcher] updateUI вызван с некорректным состоянием:", state);
            if (elements.status) elements.status.textContent = "Ошибка: Некорректный формат состояния.";
            return;
        }

        // ИСПРАВЛЕНИЕ: Улучшена логика обновления языка и кнопок
        if (state.settings && state.settings.language) {
            if (!isInitialStateLoaded && !isManualLanguageChange) {
                i18n.setLanguage(state.settings.language).then(() => {
                    loadNews(state.settings.language);
                    safeCreateLaunchButtons(state.settings.language, 'updateUI-initial');
                });
                if (elements.langSwitcher) elements.langSwitcher.value = state.settings.language;
                isInitialStateLoaded = true;
            } else if (isInitialStateLoaded && !isManualLanguageChange && i18n.currentLang !== state.settings.language) {
                // Это условие больше не должно вызывать обновление кнопок,
                // так как язык меняется только вручную или при первом запуске.
                i18n.setLanguage(state.settings.language).then(() => {
                    loadNews(state.settings.language);
                    // safeCreateLaunchButtons(state.settings.language, 'updateUI-background-sync'); // Убрали, чтобы избежать гонок
                });
                if (elements.langSwitcher) elements.langSwitcher.value = state.settings.language;
            }
        }

        if (elements.status) {
            elements.status.textContent = formatStatusMessage(state);
            elements.status.className = state.status || 'idle';
        }

        const isWorking = ['checking', 'downloading', 'extracting'].includes(state.status);
        const toggleBtn = document.getElementById('update-options-toggle');

        if (elements.updateBtn) {
            elements.updateBtn.style.display = isWorking ? 'none' : 'inline-flex';
            elements.updateBtn.disabled = isWorking;
        }
        if (elements.cancelBtn) {
            elements.cancelBtn.style.display = isWorking ? 'inline-flex' : 'none';
            elements.cancelBtn.disabled = !isWorking;
        }
        if (toggleBtn) {
            toggleBtn.style.display = isWorking ? 'none' : 'inline-flex';
        }
        if (elements.playBtn) {
            const canPlay = state.status === 'completed' || (state.status === 'idle' && state.progress && state.progress.totalToCheck > 0 && state.progress.filesTotal === 0 && !state.lastError);
            elements.playBtn.disabled = !canPlay || isWorking;
        }

        if (elements.progressBar && state.progress) {
            const percentage = state.progress.percentage || 0;
            elements.progressBar.style.width = `${percentage}%`;
            elements.progressBar.className = `progress-bar ${state.status || 'idle'}`;
            elements.progressBar.classList.toggle('updating', isWorking);
            elements.progressBar.classList.toggle('near-completion', percentage > 95);

            if (elements.progressPercentage) {
                elements.progressPercentage.textContent = `${Math.round(percentage)}%`;
            }
        }

        updateStatistics(state);
        updateFileList(state.files || [], state.status, state.progress);
        updateLaunchButtonStates(state);

        if (state.settings && !isSettingsModalVisible() && !isUserEditingSettings) {
            if (elements.downloadPathInput) elements.downloadPathInput.value = state.settings.downloadsDir || 'Не указан';
            if (elements.maxDownloadsInput) elements.maxDownloadsInput.value = String(state.settings.maxConcurrentDownloads);
            if (elements.archiveCheckbox) elements.archiveCheckbox.checked = state.settings.archiveDownloadsInAppData;
        }
    }

    // === ДОБАВЛЯЕМ ОБРАБОТЧИК ДЛЯ КНОПКИ ПОЛНОГО ОБНОВЛЕНИЯ ===
    const fullUpdateBtn = document.getElementById('full-update-btn');
    // Обработчик для кнопки полной проверки перенесен в конец файла

    function updateFileList(activeFilesFromGo, overallStatus, progressInfo) {
        if (!elements.fileList) return;
        const receivedFileIds = new Set((activeFilesFromGo || []).map(f => `file-${f.id}`));

        Array.from(elements.fileList.children).forEach(child => {
            if (child.classList.contains('file-item') && !receivedFileIds.has(child.id)) {
                child.remove();
            }
        });

        if (activeFilesFromGo && activeFilesFromGo.length > 0) {
            activeFilesFromGo.forEach(fileData => {
                let fileEl = document.getElementById(`file-${fileData.id}`);
                if (fileEl) {
                    updateFileElement(fileEl, fileData);
                } else {
                    fileEl = createFileElement(fileData);
                    elements.fileList.appendChild(fileEl);
                }
            });
        }

        const currentFileItems = Array.from(elements.fileList.children).filter(c => c.classList.contains('file-item'));
        const noFilesMessageEl = elements.fileList.querySelector('.no-files');

        if (currentFileItems.length === 0) {
            let message = '';
            const currentState = window.Launcher ? window.Launcher.getState() : null;

            switch (overallStatus) {
                case 'completed':
                    // УЛУЧШЕНИЕ: Различаем случаи завершения
                    if (progressInfo && progressInfo.totalToCheck === 0 && progressInfo.filesTotal === 0) {
                        message = i18n.t('noFilesForUpdate'); // "Нет файлов для обновления"
                    } else {
                        message = i18n.t('allFilesUpdated');
                    }
                    break;

                case 'idle':
                    if (progressInfo && progressInfo.totalToCheck > 0 && progressInfo.filesTotal === 0 && !currentState?.lastError) {
                        message = i18n.t('filesUpToDate');
                    } else {
                        message = i18n.t('pressStartUpdate');
                    }
                    break;

                case 'checking':
                    message = i18n.t('statusChecking', {
                        checked: progressInfo?.checkingFiles || 0,
                        total: progressInfo?.totalToCheck || 0
                    });
                    break;

                case 'extracting':
                    message = i18n.t('statusExtracting', {
                        remaining: (progressInfo?.filesTotal || 0) - (progress?.filesCompleted || 0)
                    });
                    break;

                case 'cancelled':
                    message = i18n.t('statusCancelled');
                    break;

                case 'error':
                    message = i18n.t('statusErrorWithLog', {
                        error: currentState?.lastError || i18n.t('unknownError')
                    });
                    break;

                default:
                    message = i18n.t('noActiveDownloads');
                    break;
            }

            if (!noFilesMessageEl || noFilesMessageEl.textContent !== message) {
                elements.fileList.innerHTML = `<p class="no-files">${message}</p>`;
            }
        } else if (noFilesMessageEl) {
            noFilesMessageEl.remove();
        }
    }

    function getStatusText(file) {
        const keyMap = { 'pending': 'fileStatusPending', 'checking': 'fileStatusChecking', 'downloading': 'fileStatusDownloading', 'extracting': 'fileStatusExtracting', 'completed': 'fileStatusCompleted', 'up-to-date': 'fileStatusUpToDate', 'error': 'fileStatusError' };
        const key = keyMap[file.status] || 'fileStatusUnknown';
        if (file.status === 'downloading') return i18n.t(key, { progress: file.progress || 0 });
        if (file.status === 'error') return i18n.t(key, { error: file.error || i18n.t('unknownError') });
        return i18n.t(key);
    }

    function createFileElement(file) {
        const div = document.createElement('div');
        div.className = `file-item status-${file.status || 'pending'}`;
        div.id = `file-${file.id}`;
        const fileName = file.path.split(/[\\/]/).pop() || file.path;
        div.innerHTML = `
            <div class="file-info">
                <span class="file-name">${fileName}</span>
                <span class="file-size-info">${formatBytes(file.size)}</span>
            </div>
            <div class="progress-container">
                <div class="progress-bar ${file.status || 'pending'}" style="width: ${file.progress || 0}%"></div>
            </div>
            <div class="file-status-details">
                <span class="file-status ${file.status || 'pending'}">${getStatusText(file)}</span>
                <span class="file-downloaded-bytes">${formatBytes(file.downloaded || 0)} / ${formatBytes(file.size || 0)}</span>
            </div>`;
        return div;
    }

    function updateFileElement(element, file) {
        element.className = `file-item status-${file.status || 'pending'}`;
        const progressBar = element.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = `${file.progress || 0}%`;
            progressBar.className = `progress-bar ${file.status || 'pending'}`;
        }
        const statusEl = element.querySelector('.file-status');
        if (statusEl) {
            statusEl.textContent = getStatusText(file);
            statusEl.className = `file-status ${file.status || 'pending'}`;
        }
        const bytesEl = element.querySelector('.file-downloaded-bytes');
        if (bytesEl) {
            let totalSizeText = formatBytes((file.status === 'downloading' ? file.zipSize : file.size) || 0);
            let speedText = (file.status === 'downloading' && file.currentSpeed > 0) ? ` (${formatSpeed(file.currentSpeed)})` : '';
            bytesEl.textContent = `${formatBytes(file.downloaded || 0)} / ${totalSizeText}${speedText}`;
        }
    }

    // ====== Top alert (showAlert) implementation ======
    function ensureAlertContainer() {
        let cont = document.querySelector('.top-alert-container');
        if (!cont) {
            cont = document.createElement('div');
            cont.className = 'top-alert-container';
            document.body.appendChild(cont);
        }
        return cont;
    }

    /**
     * showAlert({ title, message, type, duration })
     * type: 'info' | 'success' | 'warning' | 'error'
     */
    function showAlert(opts) {
        const o = opts || {};
        // Backward-compat: support `icon` option
        const ty = o.type || o.icon || 'info';
        const { title = '', message = '', duration = 3500 } = o;
        const type = ['info', 'success', 'warning', 'error'].includes(String(ty)) ? String(ty) : 'info';
        const cont = ensureAlertContainer();
        const el = document.createElement('div');
        el.className = `top-alert ${type}`;
        const iconMap = { info: 'fa-info-circle', success: 'fa-check-circle', warning: 'fa-exclamation-triangle', error: 'fa-times-circle' };
        const icon = iconMap[type] || iconMap.info;
        el.innerHTML = `
            <i class="fas ${icon}"></i>
            ${title ? `<span class="title">${title}</span>` : ''}
            <span class="msg">${message}</span>
            <button class="close" aria-label="Close"><i class="fas fa-times"></i></button>
        `;
        const closeBtn = el.querySelector('.close');
        const remove = () => { el.classList.remove('show'); setTimeout(() => el.remove(), 180); };
        closeBtn.addEventListener('click', remove);
        cont.appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        if (duration > 0) setTimeout(remove, duration);
        return remove;
    }

    // Кнопка обычного обновления
    // Кнопка обычного обновления
    if (elements.updateBtn) elements.updateBtn.addEventListener('click', () => { if (window.Launcher) window.Launcher.startFileCheck(); });

    // Кнопка полной проверки
    if (elements.fullUpdateBtn) elements.fullUpdateBtn.addEventListener('click', async () => {
        try {
            console.log('[Interface] Запуск полной проверки файлов...');
            await window.go.main.App.ForceUpdateAllFiles();
            // alert('Запущена полная проверка файлов!');
        } catch (err) {
            console.error('[Interface] Ошибка запуска полной проверки:', err);
            showAlert({ title: 'Ошибка', message: 'Ошибка запуска полной проверки: ' + (err.message || err), icon: 'error' });
        }
    });

    if (elements.cancelBtn) elements.cancelBtn.addEventListener('click', () => { if (window.Launcher) window.Launcher.cancel(); });
    if (elements.playBtn) elements.playBtn.addEventListener('click', () => { if (window.Launcher) window.Launcher.launchGame(); });

    if (elements.settingsBtn) {
        elements.settingsBtn.addEventListener('click', () => {
            isUserEditingSettings = false;
            const currentState = window.Launcher.getState();
            if (currentState && currentState.settings) {
                loadSettingsToModal(currentState.settings);
                if (elements.langSwitcher) elements.langSwitcher.value = currentState.settings.language;
            }
            saveOriginalSettings();
            elements.settingsModal.style.display = 'flex';
            isSettingsModalOpen = true;
        });
    }

    const closeModal = () => {
        if (elements.settingsModal) {
            restoreOriginalSettings();
            elements.settingsModal.style.display = 'none';
            isSettingsModalOpen = false;
            isUserEditingSettings = false;
        }
    };
    if (elements.closeModalBtn) elements.closeModalBtn.addEventListener('click', closeModal);
    if (elements.cancelSettingsBtn) elements.cancelSettingsBtn.addEventListener('click', closeModal);

    // Accounts modal logic
    function openAccountsModal() { if (elements.accountsModal) { elements.accountsModal.style.display = 'flex'; refreshAccountsTable(); } }
    function closeAccountsModal() { if (elements.accountsModal) elements.accountsModal.style.display = 'none'; }
    if (elements.closeAccountsBtn) elements.closeAccountsBtn.addEventListener('click', closeAccountsModal);
    if (elements.cancelAccountsBtn) elements.cancelAccountsBtn.addEventListener('click', closeAccountsModal);
    // Close accounts modal on overlay click
    const accountsOverlay = document.querySelector('#accounts-modal .modal-overlay');
    if (accountsOverlay) accountsOverlay.addEventListener('click', closeAccountsModal);
    if (elements.addAccountBtn) elements.addAccountBtn.addEventListener('click', async () => {
        const name = (elements.addAccountName?.value || '').trim();
        const password = (elements.addAccountPassword?.value || '').trim();
        const character = (elements.addAccountCharacter?.value || '').trim();
        if (!name || !password || !character) { showAlert({ title: 'Внимание', message: 'Заполните все поля', type: 'warning', duration: 3500 }); return; }
        try {
            const res = await window.go.main.App.AddAccount(name, password, character);
            const ok = Array.isArray(res) ? Boolean(res[0]) : (typeof res === 'boolean' ? res : true);
            if (ok) {
                elements.addAccountName.value = '';
                elements.addAccountPassword.value = '';
                elements.addAccountCharacter.value = '';
                await refreshAccountsTable();
            } else {
                const msg = Array.isArray(res) ? String(res[1] || '') : (typeof res === 'string' ? res : '');
                showAlert({ title: 'Ошибка', message: msg || 'Не удалось добавить аккаунт', type: 'error', duration: 4000 });
            }
        } catch (e) {
            console.error('AddAccount failed', e);
            showAlert({ title: 'Ошибка', message: 'Ошибка добавления аккаунта', type: 'error', duration: 4000 });
        }
    });

    async function refreshAccountsTable() {
        if (!elements.accountsTableBody) return;
        elements.accountsTableBody.innerHTML = '';
        try {
            const accounts = await window.go.main.App.GetAccounts();
            if (!accounts || accounts.length === 0) {
                elements.accountsTableBody.innerHTML = '<tr><td colspan="4" class="accounts-empty" data-i18n="noAccounts">Список пуст</td></tr>';
                window.i18n && window.i18n.applyTranslations && window.i18n.applyTranslations();
                return;
            }
            accounts.forEach(acc => {
                const tr = document.createElement('tr');
                const tdLogin = document.createElement('td'); tdLogin.textContent = acc.name;
                const tdPass = document.createElement('td'); tdPass.textContent = acc.password ? '••••••' : '';
                const tdChar = document.createElement('td'); tdChar.textContent = acc.character || '';
                const tdAct = document.createElement('td');
                const delBtn = document.createElement('button');
                delBtn.className = 'jellyfish-btn action-danger';
                delBtn.style.padding = '6px 10px';
                delBtn.innerHTML = '<i class="fas fa-trash"></i>';
                delBtn.title = 'Удалить';
                delBtn.addEventListener('click', async () => {
                    try {
                        const res = await window.go.main.App.DeleteAccount(acc.name);
                        const ok = Array.isArray(res) ? Boolean(res[0]) : (typeof res === 'boolean' ? res : true);
                        if (ok) {
                            await refreshAccountsTable();
                        }
                    } catch (e) { console.error('DeleteAccount failed', e); }
                });
                tdAct.appendChild(delBtn);
                tr.appendChild(tdLogin); tr.appendChild(tdPass); tr.appendChild(tdChar); tr.appendChild(tdAct);
                elements.accountsTableBody.appendChild(tr);
            });
        } catch (e) {
            console.error('GetAccounts failed', e);
            elements.accountsTableBody.innerHTML = '<tr><td colspan="4" class="accounts-empty">Ошибка загрузки</td></tr>';
        }
    }

    if (elements.saveSettingsBtn) {
        elements.saveSettingsBtn.addEventListener('click', async () => {
            const maxDownloads = parseInt(elements.maxDownloadsInput.value);
            if (isNaN(maxDownloads) || maxDownloads < 1 || maxDownloads > 20) { showAlert({ title: 'Внимание', message: 'Количество одновременных загрузок должно быть от 1 до 20', type: 'warning', duration: 5000 }); return; }
            const settings = {
                maxConcurrentDownloads: maxDownloads,
                archiveDownloadsInAppData: elements.archiveCheckbox.checked,
                language: elements.langSwitcher.value,
            };
            const result = await window.Launcher.saveSettings(settings);
            if (result.success) {
                isManualLanguageChange = false;
                closeModal();
                saveOriginalSettings();
                setTimeout(() => window.go.main.App.GetState().then(updateUI), 100);
            } else {
                showAlert({ title: 'Ошибка', message: (result.message ? `❌ ${result.message}` : 'Неизвестная ошибка'), type: 'error', duration: 5000 });
            }
        });
    }

    setupSettingsInputHandlers();

    window.addEventListener('click', (e) => {
        if (e.target === elements.settingsModal ||
            e.target.classList.contains('modal-backdrop')) {
            closeModal();
        }
    });

    if (!window.Launcher) {
        if (window.go && window.go.main.App.GetState) {
            window.go.main.App.GetState().then(updateUI).catch(err => console.error(err));
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (elements.settingsModal && elements.settingsModal.style.display === 'flex') closeModal();
            if (elements.accountsModal && elements.accountsModal.style.display === 'flex') closeAccountsModal();
        }
    });

    // --- НАЧАЛО ИСПРАВЛЕНИЯ: ПЕРЕМЕЩАЕМ ЛОГИКУ ВНУТРЬ DOMCONTENTLOADED ---
    const i18n = {
        allTranslations: null, // Хранилище для всех загруженных языков
        translations: {},      // Текущий активный язык
        currentLang: 'ru',

        t(key, replacements = {}) {
            let translation = this.translations[key] || `[${key}]`;
            Object.keys(replacements).forEach(placeholder => {
                translation = translation.replace(`{${placeholder}}`, replacements[placeholder]);
            });
            return translation;
        },

        applyTranslations() {
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                const attr = el.getAttribute('data-i18n-attr');
                if (attr) {
                    el.setAttribute(attr, this.t(key));
                } else {
                    // ИСПРАВЛЕНО: Используем innerHTML для корректной вставки HTML-тегов
                    el.innerHTML = this.t(key);
                }
            });
            console.log(`[i18n] Переводы для языка "${this.currentLang}" применены.`);
        },

        async setLanguage(lang) {
            if (!lang) lang = this.currentLang;
            this.currentLang = lang;
            console.log(`[i18n] Установка языка: ${lang}`);

            try {
                // Загружаем общий файл, если он еще не загружен
                if (this.allTranslations === null) {
                    console.log(`[i18n] Загрузка общего файла переводов /lang.json...`);
                    const response = await fetch(`/lang.json`);
                    if (!response.ok) throw new Error(`Общий файл переводов не найден: ${response.statusText}`);
                    this.allTranslations = await response.json();
                    console.log(`[i18n] Все переводы успешно загружены.`);
                }

                // Выбираем нужный язык из загруженных данных
                if (this.allTranslations[lang]) {
                    this.translations = this.allTranslations[lang];
                } else {
                    console.warn(`[i18n] Перевод для языка "${lang}" не найден. Используется 'ru'.`);
                    this.translations = this.allTranslations['ru'] || {}; // Фоллбэк на русский
                }

                this.applyTranslations();

                const langSwitcher = document.getElementById('lang-switcher');
                if (langSwitcher) langSwitcher.value = this.currentLang;
            } catch (error) {
                console.error(`[i18n] Ошибка загрузки или применения переводов для языка ${lang}:`, error);
                if (lang !== 'ru') {
                    console.warn(`[i18n] Попытка загрузить язык по умолчанию 'ru' из-за ошибки.`);
                    await this.setLanguage('ru');
                }
            }
        }
    };
    window.i18n = i18n;

    // ИСПРАВЛЕННЫЙ ОБРАБОТЧИК: смена языка с гарантированным пересозданием кнопок
    document.getElementById('lang-switcher').addEventListener('change', async (event) => {
        const newLang = event.target.value;
        console.log(`[Interface] Пользователь выбрал язык: ${newLang}`);

        // Устанавливаем флаг ручной смены
        isManualLanguageChange = true;

        // Немедленно меняем язык в UI
        await window.i18n.setLanguage(newLang);

        // Перезагружаем новости для нового языка
        loadNews(newLang);

        // ГЛАВНОЕ ИСПРАВЛЕНИЕ: Вместо обновления текста кнопок, мы их полностью пересоздаем.
        // Это самый надежный способ гарантировать, что они будут с правильным переводом.
        try {
            await safeCreateLaunchButtons(newLang, 'lang-switcher-change');
            console.log(`[Interface] Кнопки запуска пересозданы для языка ${newLang}`);
        } catch (e) {
            console.error('[Interface] Ошибка пересоздания кнопок запуска:', e);
        }

        try {
            let currentSettings = {};
            const currentState = window.Launcher ? window.Launcher.getState() : (await window.go.main.App.GetState());
            if (currentState && currentState.settings) currentSettings = currentState.settings;

            const settings = {
                language: newLang,
                maxConcurrentDownloads: currentSettings.maxConcurrentDownloads || 4,
                archiveDownloadsInAppData: currentSettings.archiveDownloadsInAppData !== undefined ? currentSettings.archiveDownloadsInAppData : false,
            };

            const result = await (window.Launcher ? window.Launcher.saveSettings(settings) : window.go.main.App.SaveSettings(settings));

            let success = false;
            if (typeof result === 'object' && result !== null && 'success' in result) {
                success = result.success;
            } else if (Array.isArray(result)) {
                success = Boolean(result[0]);
            } else {
                success = Boolean(result);
            }

            if (success) {
                console.log('[Interface] Язык успешно сохранен в backend');
                // Сбрасываем флаг после успешного сохранения
                isManualLanguageChange = false;
            } else {
                console.error('[Interface] Ошибка сохранения языка:', result);
            }
        } catch (error) {
            console.error('[Interface] Ошибка при сохранении языка:', error);
        }
    });

});

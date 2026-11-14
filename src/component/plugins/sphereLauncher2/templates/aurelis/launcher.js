// template/launcher.js - Упрощенная версия для Wails

const Launcher = (() => {
    let currentState = null;
    let stateListeners = [];

    // Инициализация
    function init() {
        if (!window.runtime) {
            console.error('[Launcher] Wails runtime не найден');
            return;
        }

        // Подписываемся на обновления состояния
        window.runtime.EventsOn('stateUpdate', (state) => {
            currentState = state;
            notifyListeners(state);
        });

        // Запрашиваем начальное состояние
        window.go.main.App.GetState().then(state => {
            currentState = state;
            notifyListeners(state);
        });

        console.log('[Launcher] Инициализирован');
    }

    // Подписка на изменения состояния
    function onStateChange(callback) {
        stateListeners.push(callback);
        // Вызываем callback с текущим состоянием
        if (currentState) {
            callback(currentState);
        }
    }

    // Уведомление всех слушателей
    function notifyListeners(state) {
        stateListeners.forEach(callback => {
            try {
                callback(state);
            } catch (error) {
                console.error('[Launcher] Ошибка в обработчике состояния:', error);
            }
        });
    }

    // API методы
    const api = {
        // Запуск проверки файлов
        startFileCheck: async () => {
            try {
                const error = await window.go.main.App.StartFileCheck();
                if (error) {
                    console.error('[Launcher] Ошибка запуска проверки:', error);
                }
            } catch (err) {
                console.error('[Launcher] Ошибка вызова StartFileCheck:', err);
            }
        },

        // Отмена операции
        cancel: async () => {
            try {
                await window.go.main.App.CancelOperation();
            } catch (err) {
                console.error('[Launcher] Ошибка отмены:', err);
            }
        },

        // Запуск игры
        launchGame: async () => {
            try {
                const error = await window.go.main.App.LaunchGame();
                if (error) {
                    console.error('[Launcher] Ошибка запуска игры:', error);
                }
            } catch (err) {
                console.error('[Launcher] Ошибка вызова LaunchGame:', err);
            }
        },

        // ИСПРАВЛЕННОЕ СОХРАНЕНИЕ НАСТРОЕК
        saveSettings: async (settings) => {
            try {
                console.log('[Launcher] Сохранение настроек:', settings);

                // Безопасный вызов без деструктуризации
                const goResult = await window.go.main.App.SaveSettings(settings);
                console.log('[Launcher] Результат от Go:', goResult);

                // Обрабатываем различные форматы ответа от Wails
                if (Array.isArray(goResult) && goResult.length >= 2) {
                    // Формат [success, message]
                    return {
                        success: Boolean(goResult[0]),
                        message: String(goResult[1] || 'Настройки сохранены')
                    };
                } else if (typeof goResult === 'object' && goResult !== null) {
                    // Формат {success: bool, message: string}
                    return {
                        success: Boolean(goResult.success),
                        message: String(goResult.message || 'Настройки сохранены')
                    };
                } else if (typeof goResult === 'boolean') {
                    // Простой bool ответ
                    return {
                        success: goResult,
                        message: goResult ? 'Настройки успешно сохранены' : 'Ошибка сохранения настроек'
                    };
                } else if (typeof goResult === 'string') {
                    // Строковый ответ (сообщение об ошибке)
                    return {
                        success: false,
                        message: goResult
                    };
                } else {
                    // Неожиданный формат
                    console.warn('[Launcher] Неожиданный формат ответа от SaveSettings:', goResult);
                    return {
                        success: false,
                        message: 'Неожиданный формат ответа от сервера'
                    };
                }
            } catch (err) {
                console.error('[Launcher] Ошибка сохранения настроек:', err);
                return {
                    success: false,
                    message: `Ошибка: ${err.message || err.toString()}`
                };
            }
        },

        // Получить текущее состояние
        getState: () => currentState,

        // Подписка на обновления
        onStateChange
    };

    // Автоматическая инициализация при загрузке
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return api;
})();

// Экспортируем для использования
window.Launcher = Launcher;
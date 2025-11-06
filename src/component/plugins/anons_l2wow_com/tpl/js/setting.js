// Глобальные данные о настройках серверов
let serverSettings = {};
let currentServerId = null;

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('Плагин L2WOW: Инициализация...');
    
    // Получаем данные из встроенных переменных
    if (typeof window.serverSettingsData !== 'undefined') {
        serverSettings = window.serverSettingsData;
        console.log('Настройки серверов загружены:', serverSettings);
    } else {
        console.warn('serverSettingsData не найден');
    }
    
    // Привязываем обработчики событий
    initEventListeners();
    
    // Автозагрузка данных если сервер уже выбран
    const serverSelect = document.getElementById('l2wowServerSelect');
    if (serverSelect && serverSelect.value) {
        console.log('Обнаружен предвыбранный сервер:', serverSelect.value);
        // Триггерим событие change для загрузки данных
        serverSelect.dispatchEvent(new Event('change'));
    }
});

// Инициализация обработчиков событий
function initEventListeners() {
    const serverSelect = document.getElementById('l2wowServerSelect');
    const saveBtn = document.getElementById('l2wowSaveBtn');
    
    if (!serverSelect) {
        console.error('Элемент l2wowServerSelect не найден');
        return;
    }
    
    console.log('Элементы найдены');
    
    // Выбор сервера
    serverSelect.addEventListener('change', function() {
        console.log('Выбран сервер:', this.value);
        const serverId = this.value;
        currentServerId = serverId;
        
        const serverConfig = document.getElementById('l2wowServerConfig');
        const serverConfigNotice = document.getElementById('l2wowServerConfigNotice');
        const webhookUrlInput = document.getElementById('l2wowWebhookUrl');
        
        if (!serverId) {
            // Показываем уведомление, скрываем форму
            serverConfigNotice.style.display = 'block';
            serverConfig.style.display = 'none';
            clearAllItems();
            return;
        }
        
        // Скрываем уведомление, показываем форму
        serverConfigNotice.style.display = 'none';
        serverConfig.style.display = 'block';
        
        // Устанавливаем webhook URL
        const webhookUrl = `${window.location.protocol}//${window.location.host}/anons_l2wow_com/${serverId}`;
        webhookUrlInput.value = webhookUrl;
        console.log('Webhook URL:', webhookUrl);
        
        // Загружаем конфигурацию сервера
        loadServerConfig(serverId);
    });
    
    // Кнопки "Добавить предмет" для каждой вкладки
    document.querySelectorAll('.add-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const voteCount = parseInt(this.getAttribute('data-vote-count'));
            console.log('Добавление предмета для', voteCount, 'голосов');
            addItemToVoteLevel(voteCount);
        });
    });
    
    // Сохранение настроек
    if (saveBtn) {
        saveBtn.addEventListener('click', saveSettings);
    }
}

// Очистка всех предметов
function clearAllItems() {
    for (let i = 1; i <= 5; i++) {
        const container = document.querySelector(`.items-container-${i}`);
        if (container) {
            container.innerHTML = '';
        }
    }
}

// Загрузка конфигурации сервера
function loadServerConfig(serverId) {
    console.log('Загрузка конфигурации для сервера:', serverId);
    
    // Очищаем все контейнеры
    clearAllItems();
    
    // Проверяем есть ли настройки для этого сервера
    const config = serverSettings[serverId];
    
    // Загружаем webhook_key
    const webhookKeyInput = document.getElementById('l2wowWebhookKey');
    if (webhookKeyInput && config) {
        webhookKeyInput.value = config.webhookKey || '';
    }
    
    if (config && config.voteLevels && config.voteLevels.length > 0) {
        console.log('Найдены настройки:', config.voteLevels);
        
        // Загружаем предметы для каждого уровня голосов
        config.voteLevels.forEach(level => {
            const voteCount = level.voteCount;
            if (voteCount >= 1 && voteCount <= 5 && level.items) {
                level.items.forEach(item => {
                    addItemToVoteLevel(voteCount, item.itemId, item.count, item.enchant);
                });
            }
        });
    } else {
        console.log('Настройки не найдены для сервера');
    }
}

// Добавление предмета к уровню голосов
function addItemToVoteLevel(voteCount, itemId = '', count = 1, enchant = 0) {
    const container = document.querySelector(`.items-container-${voteCount}`);
    if (!container) {
        console.error(`Контейнер для ${voteCount} голосов не найден`);
        return;
    }
    
    const template = document.getElementById('itemTemplate');
    if (!template) {
        console.error('Template itemTemplate не найден');
        return;
    }
    
    const clone = template.content.cloneNode(true);
    
    const itemIdInput = clone.querySelector('.item-id-input');
    const itemCountInput = clone.querySelector('.item-count-input');
    const itemEnchantInput = clone.querySelector('.item-enchant-input');
    const itemInfoDisplay = clone.querySelector('.item-info-display');
    
    itemIdInput.value = itemId;
    itemCountInput.value = count;
    itemEnchantInput.value = enchant;
    
    // Функция для фильтрации только цифр
    const makeNumericOnly = function(input) {
        input.addEventListener('keypress', function(e) {
            if (e.key && !/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbersOnly = pastedText.replace(/\D/g, '');
            this.value = numbersOnly;
        });
        
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });
    };
    
    // Применяем фильтрацию ко всем числовым полям
    makeNumericOnly(itemIdInput);
    makeNumericOnly(itemCountInput);
    makeNumericOnly(itemEnchantInput);
    
    // Дополнительный обработчик для загрузки информации о предмете
    let loadTimeout;
    itemIdInput.addEventListener('input', function() {
        const id = this.value;
        
        // Очищаем предыдущий таймер
        clearTimeout(loadTimeout);
        
        if (id && id > 0) {
            // Задержка 500мс перед запросом (debounce)
            loadTimeout = setTimeout(() => {
                updateItemInfo(itemInfoDisplay, id);
            }, 500);
        } else {
            itemInfoDisplay.innerHTML = '<span class="text-muted">Введите ID предмета</span>';
        }
    });
    
    // Если ID уже указан - загружаем информацию
    if (itemId && itemId > 0) {
        updateItemInfo(itemInfoDisplay, itemId);
    }
    
    // Обработчик удаления предмета
    const deleteBtn = clone.querySelector('.delete-item-btn');
    deleteBtn.addEventListener('click', function(e) {
        const itemRow = e.target.closest('.item-row');
        itemRow.remove();
    });
    
    container.appendChild(clone);
}

// Обновление информации о предмете
function updateItemInfo(infoDisplay, itemId) {
    infoDisplay.innerHTML = '<span class="text-muted">Загрузка...</span>';
    
    fetch('/admin/client/item/info', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'itemID=' + itemId
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.ok && data.item) {
            const item = data.item;
            const iconUrl = item.icon || '';
            const iconHtml = iconUrl ? `<img src="${iconUrl}" alt="${item.itemName}" style="height: 24px; width: 24px; margin-right: 5px; vertical-align: middle;">` : '';
            infoDisplay.innerHTML = `
                <div style="display: flex; align-items: center;">
                    ${iconHtml}
                    <div>
                        <strong>${item.itemName || 'Item #' + itemId}</strong><br>
                        <span class="text-muted">ID: ${itemId}</span>
                    </div>
                </div>
            `;
        } else {
            infoDisplay.innerHTML = `<span class="text-warning">Item #${itemId} - Not found</span>`;
        }
    })
    .catch(error => {
        console.error('Ошибка загрузки информации о предмете:', error);
        infoDisplay.innerHTML = `<span class="text-warning">Item #${itemId}</span>`;
    });
}

// Сохранение настроек
function saveSettings() {
    const serverId = currentServerId || document.getElementById('l2wowServerSelect').value;
    
    if (!serverId) {
        alert('Выберите сервер');
        return;
    }
    
    // Получаем webhook_key
    const webhookKey = document.getElementById('l2wowWebhookKey').value.trim();
    
    const voteLevels = [];
    
    // Собираем данные из всех 5 вкладок
    for (let voteCount = 1; voteCount <= 5; voteCount++) {
        const container = document.querySelector(`.items-container-${voteCount}`);
        if (!container) continue;
        
        const items = [];
        const itemRows = container.querySelectorAll('.item-row');
        
        itemRows.forEach((row) => {
            const itemId = parseInt(row.querySelector('.item-id-input').value);
            const count = parseInt(row.querySelector('.item-count-input').value);
            const enchant = parseInt(row.querySelector('.item-enchant-input').value);
            
            if (itemId > 0) {
                items.push({
                    itemId: itemId,
                    count: count || 1,
                    enchant: enchant || 0
                });
            }
        });
        
        // Добавляем уровень только если есть предметы
        if (items.length > 0) {
            voteLevels.push({
                voteCount: voteCount,
                items: items
            });
        }
    }
    
    console.log('Собранные данные:', voteLevels);
    
    // Отправка данных на сервер
    const data = {
        serverId: serverId,
        webhookKey: webhookKey,
        voteLevels: voteLevels
    };
    
    console.log('Отправка данных:', data);
    
    fetch('/admin/plugin/anons_l2wow_com/setting/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        responseAnalysis(data);
    })
    .catch(error => {
        console.error('Ошибка сохранения настроек:', error);
    });
}

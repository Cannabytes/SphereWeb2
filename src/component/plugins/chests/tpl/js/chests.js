/**
 * Новый механизм розыгрыша для кейсов - Магические кристаллы
 */

document.addEventListener('DOMContentLoaded', function() {
    // Элементы интерфейса
    const modal = document.getElementById('chestModal');
    const openButton = document.getElementById('open-chest-button');
    const openButtonGroup = document.querySelector('.open-button-container .btn-group');
    const openChestDropdownItems = document.querySelectorAll('.dropdown-menu .dropdown-item[data-open-chest-count]');
    const openChestMainBtn = document.getElementById('open-chest-button'); // Главная кнопка "Открыть кейс"

    // Настройки анимации
    const settings = {
        crystalsCount: 24,            // Количество кристаллов (3x3)
        revealDelay: 210,            // Задержка между открытием кристаллов (мс)
        highlightDuration: 1800,     // Длительность подсветки выигрыша (мс)
        soundEnabled: false           // Включены ли звуки
    };

    // Обработчики событий

    // Клик по кейсу
    document.querySelectorAll('.chest-item').forEach(item => {
        item.addEventListener('click', function() {
            const chestId = this.dataset.chestId;
            const chestName = this.dataset.chestName;
            const chestPrice = this.dataset.chestPrice;
            const chestIcon = this.dataset.chestIcon;

            openChestModal(chestId, chestName, chestPrice, chestIcon);
        });
    });

    // Клик по кнопке открытия кейса
    if (openButton) {
        openButton.addEventListener('click', function() {
            if (this.disabled) return;

            // Блокируем кнопку
            this.disabled = true;

            const chestId = this.dataset.chestId;
            if (!chestId) {
                console.error('ID кейса не найден');
                this.disabled = false;
                return;
            }

            // Получаем количество открываемых сундуков. По умолчанию 1.
            const numberOfChestsToOpen = parseInt(this.dataset.openChestCount || '1', 10);

            // Начинаем процесс открытия
            startOpeningProcess(chestId, numberOfChestsToOpen);
        });
    }

    // Обработчики событий для кнопок открытия кейсов в выпадающем списке
    if (openChestDropdownItems.length > 0) {
        openChestDropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const count = this.dataset.openChestCount;
                if (count) {
                    openChestMainBtn.textContent = `Открыть ${count} сундуков`;
                    openChestMainBtn.dataset.openChestCount = count;
                    openButtonGroup.classList.add('multi-open-selected');
                }
            });
        });
    }

    // Обработка кнопки "открыть еще"
    document.addEventListener('click', function(e) {
        if (e.target.id === 'open-again-btn' || e.target.classList.contains('btn-open-again')) {
            resetModalState();
        }
    });

    // Сброс при закрытии модального окна
    if (modal) {
        modal.addEventListener('hidden.bs.modal', resetModalState);
    }

    // Основные функции

    // Открытие модального окна с кейсом
    function openChestModal(chestId, chestName, chestPrice, chestIcon) {
        // Сбрасываем состояние
        resetModalState();

        // Установка данных кейса
        const imagePath = '/src/component/plugins/chests/tpl/images/chest/chest-' + chestIcon + '.webp';
        const modalChestImage = document.getElementById('modal-chest-image');
        const modalChestPrice = document.getElementById('modal-chest-price');
        const modalChestName = document.getElementById('modal-chest-name');

        if (modalChestImage) modalChestImage.src = imagePath;
        if (modalChestPrice) modalChestPrice.textContent = chestPrice + ' монет';
        if (modalChestName) modalChestName.textContent = chestName;
        if (openButton) openButton.dataset.chestId = chestId;

        // Загрузка списка предметов
        loadChestItems(chestId);

        try {
            // Открываем модальное окно через Bootstrap
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } catch (error) {
            console.warn('Ошибка Bootstrap Modal, используем запасной метод', error);
            $(modal).modal('show');
        }
    }

    // Загрузка предметов из кейса
    function loadChestItems(chestId) {
        const itemsList = document.getElementById('chest-items-list');
        if (!itemsList) return;

        // Очищаем список
        itemsList.innerHTML = '';

        // Получаем данные кейса
        const chest = window.chestData && window.chestData[chestId];
        if (!chest || !chest.items || !chest.items.length) {
            itemsList.innerHTML = '<div class="alert alert-warning">Предметы не найдены</div>';
            return;
        }

        // Добавляем предметы в список
        chest.items.forEach(item => {
            const itemCard = createItemCard(item);
            itemsList.innerHTML += itemCard;
        });
    }

    // Создание карточки предмета
    function createItemCard(item) {
        if (!item || !item.id) return '';

        const enchantBadge = item.enchant > 0
            ? `<div class="item-enchant">+${item.enchant}</div>`
            : '';

        return `
            <div class="item-card" data-item-id="${item.id}">
                <div class="item-image-container">
                    <img src="${item.icon}" alt="${item.name}" class="item-image">
                    ${enchantBadge}
                </div>
                <div class="item-details-n">
                    <p class="item-name">${item.name}</p>
                    <span class="item-count">x${item.count}</span>
                </div>
            </div>
        `;
    }

    // Процесс открытия кейса
    function startOpeningProcess(chestId, numberOfChestsToOpen = 1) {
        // Скрываем предпросмотр и список предметов
        makeModalTransparent();

        const previewContainer = document.querySelector('.chest-preview-container');
        const itemsContainer = document.querySelector('.chest-items-container');

        fadeOut(previewContainer);
        fadeOut(itemsContainer);

        // Показываем анимацию открытия сундука
        setTimeout(() => {
            showOpeningAnimation(chestId);

            AjaxSend('/fun/chests/callback', 'POST', {
                chest_id: chestId,
                count_open: numberOfChestsToOpen
            }, true)
                .then(response => {
                    responseAnalysis(response);

                    if (!response || (!response.ok && !response.success)) {
                        throw new Error('Некорректные данные ответа');
                    }

                    let winningItems = [];
                    const allWarehouseItems = response.warehouse || [];

                    // Создаем карту для быстрого поиска полной информации о предмете по его itemId
                    const warehouseItemMap = new Map();
                    allWarehouseItems.forEach(warehouseItem => {
                        if (warehouseItem.itemId) {
                            warehouseItemMap.set(warehouseItem.itemId, warehouseItem);
                        }
                    });

                    // Обрабатываем предметы, которые были фактически выиграны (из response.items)
                    if (response.items && Array.isArray(response.items)) {
                        winningItems = response.items.map(wonItem => {
                            const itemId = wonItem.id; // Это ID определения предмета
                            const matchedWarehouseItem = warehouseItemMap.get(itemId);

                            // Значения по умолчанию, если соответствующий предмет не найден (резерв)
                            const defaultDetails = {
                                name: 'Неизвестный предмет',
                                add_name: '',
                                icon: '',
                                crystal_type: null,
                                enchant: 0
                            };

                            let finalDetails = { ...defaultDetails }; // Начинаем со значений по умолчанию

                            if (matchedWarehouseItem) {
                                const itemInfo = matchedWarehouseItem.itemInfo || {};
                                finalDetails.name = itemInfo.itemName || defaultDetails.name;
                                finalDetails.add_name = itemInfo.addName || defaultDetails.add_name;
                                finalDetails.icon = itemInfo.icon || defaultDetails.icon;
                                finalDetails.crystal_type = itemInfo.crystal_type || defaultDetails.crystal_type;
                                finalDetails.enchant = matchedWarehouseItem.enchant || defaultDetails.enchant; // Зачарование находится в warehouseItem
                            }

                            return {
                                id: itemId, // ID определения предмета
                                count: wonItem.count, // Количество из выигранного предмета
                                enchant: finalDetails.enchant,
                                name: finalDetails.name,
                                add_name: finalDetails.add_name,
                                icon: finalDetails.icon,
                                crystal_type: finalDetails.crystal_type
                            };
                        });
                    } else if (response.item) {
                        // Этот путь менее вероятен, учитывая предоставленный JSON, но сохраняем для надежности
                        const wonItem = response.item;
                        const itemId = wonItem.id;
                        const matchedWarehouseItem = warehouseItemMap.get(itemId);

                        const defaultDetails = {
                            name: 'Неизвестный предмет',
                            add_name: '',
                            icon: '',
                            crystal_type: null,
                            enchant: 0
                        };

                        let finalDetails = { ...defaultDetails };

                        if (matchedWarehouseItem) {
                            const itemInfo = matchedWarehouseItem.itemInfo || {};
                            finalDetails.name = itemInfo.itemName || defaultDetails.name;
                            finalDetails.add_name = itemInfo.addName || defaultDetails.add_name;
                            finalDetails.icon = itemInfo.icon || defaultDetails.icon;
                            finalDetails.crystal_type = itemInfo.crystal_type || defaultDetails.crystal_type;
                            finalDetails.enchant = matchedWarehouseItem.enchant || defaultDetails.enchant;
                        }

                        winningItems.push({
                            id: itemId,
                            count: wonItem.count,
                            enchant: finalDetails.enchant,
                            name: finalDetails.name,
                            add_name: finalDetails.add_name,
                            icon: finalDetails.icon,
                            crystal_type: finalDetails.crystal_type
                        });
                    } else {
                        throw new Error('Ответ не содержит информацию о предметах');
                    }

                    // Скрываем анимацию открытия
                    setTimeout(() => {
                        const openingContainer = document.querySelector('.opening-animation-container');
                        fadeOut(openingContainer);

                        // Запускаем анимацию магических кристаллов, передаем массив выигранных предметов и карту склада
                        setTimeout(() => showCrystalsAnimation(chestId, winningItems, warehouseItemMap), 300);
                    }, 1500);
                })
                .catch(error => {
                    console.error('Ошибка при открытии кейса:', error);
                    restoreModalOpacity();
                    resetModalState();
                });
        }, 300);
    }

    // Анимация открытия сундука
    function showOpeningAnimation(chestId) {
        const chest = window.chestData && window.chestData[chestId];
        if (!chest) {
            console.error('Кейс не найден:', chestId);
            return;
        }

        // Подготавливаем контейнер анимации
        const openingContainer = document.querySelector('.opening-animation-container');
        if (!openingContainer) return;

        // Устанавливаем изображение кейса
        const chestAnimationImg = document.getElementById('chest-animation-img');
        if (chestAnimationImg) {
            const chestImagePath = '/src/component/plugins/chests/tpl/images/chest/chest-' + chest.icon + '.webp';
            chestAnimationImg.src = chestImagePath;
            chestAnimationImg.classList.add('chest-opening');
        }

        // Показываем контейнер
        openingContainer.classList.remove('d-none');
        openingContainer.style.display = 'flex';
        setTimeout(() => {
            openingContainer.style.opacity = '1';
        }, 10);

        // Играем звук открытия
        playSound('chest_open');

        // Эффект частиц
        createParticlesEffect(openingContainer);
    }

    // Эффект частиц при открытии сундука
    function createParticlesEffect(container) {
        let particlesContainer = container.querySelector('.particles-container');
        if (!particlesContainer) {
            particlesContainer = document.createElement('div');
            particlesContainer.className = 'particles-container';
            container.appendChild(particlesContainer);
        }

        // Создаем частицы
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';

            // Случайные параметры для частиц
            const size = Math.random() * 10 + 5;
            const posX = 50 + (Math.random() - 0.5) * 60;
            const posY = 50 + (Math.random() - 0.5) * 60;
            const angle = Math.random() * Math.PI * 2;
            const distance = 100 + Math.random() * 100;
            const speed = Math.random() * 2 + 1;
            const delay = Math.random() * 0.5;

            // Применяем стили
            particle.style.cssText = `
                width: ${size}px;
                height: ${size}px;
                left: ${posX}%;
                top: ${posY}%;
                --tx: ${Math.cos(angle) * distance}px;
                --ty: ${Math.sin(angle) * distance}px;
                animation: particle-fly ${speed}s ease-out ${delay}s forwards;
            `;

            particlesContainer.appendChild(particle);
        }

        // Удаляем частицы через 3 секунды
        setTimeout(() => {
            if (particlesContainer && particlesContainer.parentNode) {
                particlesContainer.parentNode.removeChild(particlesContainer);
            }
        }, 3000);
    }

    // ======== НОВАЯ АНИМАЦИЯ - МАГИЧЕСКИЕ КРИСТАЛЛЫ ========

    // Анимация магических кристаллов
    function showCrystalsAnimation(chestId, winningItems, warehouseItemMap) { // Теперь принимает массив winningItems и warehouseItemMap
        const chest = window.chestData && window.chestData[chestId];
        if (!chest || !chest.items || !chest.items.length) {
            console.error('Данные кейса не найдены:', chestId);
            showWinningItem(winningItems); // Передаем все выигранные предметы
            return;
        }

        // Создаем контейнер для кристаллов, если он еще не существует
        createCrystalsContainer();

        // Получаем коллекцию предметов из кейса
        const allPossibleItems = [...chest.items];

        // Заполняем кристаллы предметами
        populateCrystals(allPossibleItems, winningItems, warehouseItemMap); // Передаем все выигранные предметы и warehouseItemMap

        // Воспроизводим звук
        playSound('crystals_appear');

        // Запускаем анимацию открытия кристаллов
        revealCrystals(winningItems); // Передаем все выигранные предметы
    }

    // Создание контейнера для кристаллов
    function createCrystalsContainer() {
        // Удаляем старый контейнер, если он существует
        const oldContainer = document.querySelector('.crystals-container');
        if (oldContainer && oldContainer.parentNode) {
            oldContainer.parentNode.removeChild(oldContainer);
        }

        // Создаем HTML для нового контейнера
        const crystalsHTML = `
            <div class="crystals-container" style="opacity:0">
                <div class="crystals-grid"></div>
            </div>
        `;

        // Добавляем контейнер в DOM после контейнера анимации
        const openingContainer = document.querySelector('.opening-animation-container');
        if (openingContainer && openingContainer.parentNode) {
            openingContainer.insertAdjacentHTML('afterend', crystalsHTML);

            // Показываем контейнер
            const crystalsContainer = document.querySelector('.crystals-container');
            crystalsContainer.style.display = 'block';
            setTimeout(() => {
                crystalsContainer.style.opacity = '1';
            }, 10);
        }
    }

    // Заполнение кристаллов предметами
    function populateCrystals(allPossibleItems, winningItems, warehouseItemMap) { // Теперь принимает массив winningItems и warehouseItemMap
        const crystalsGrid = document.querySelector('.crystals-grid');
        if (!crystalsGrid) return;

        // Очищаем сетку
        crystalsGrid.innerHTML = '';

        // Создаем массив предметов для кристаллов
        const crystalItems = [];

        // Кеш для отслеживания использованных предметов, чтобы избежать повторов
        const usedItemIds = new Set();

        // Helper to get item details for populating crystals
        const getCrystalItemDetails = (item, map) => {
            const itemId = item.id;
            const matchedWarehouseItem = map.get(itemId);

            const defaultDetails = {
                name: '???',
                add_name: '',
                icon: '',
                crystal_type: null,
                enchant: 0
            };

            let finalDetails = { ...defaultDetails };

            if (matchedWarehouseItem) {
                const itemInfo = matchedWarehouseItem.itemInfo || {};
                finalDetails.name = itemInfo.itemName || defaultDetails.name;
                finalDetails.add_name = itemInfo.addName || defaultDetails.add_name;
                finalDetails.icon = itemInfo.icon || defaultDetails.icon;
                finalDetails.crystal_type = itemInfo.crystal_type || defaultDetails.crystal_type;
                finalDetails.enchant = matchedWarehouseItem.enchant || defaultDetails.enchant;
            }

            return {
                id: itemId,
                name: finalDetails.name,
                icon: finalDetails.icon,
                count: item.count || 1, // Количество берется из переданного объекта 'item'
                enchant: finalDetails.enchant,
                crystal_type: finalDetails.crystal_type
            };
        };


        // Добавляем все выигрышные предметы в crystalItems и помечаем их как winning
        winningItems.forEach(winItem => {
            // winItem уже имеет полные детали из предыдущего сопоставления,
            // но getCrystalItemDetails все равно будет использовать карту для единообразия.
            const details = getCrystalItemDetails(winItem, warehouseItemMap);
            crystalItems.push({ ...details, isWinning: true });
            usedItemIds.add(details.id);
        });

        // Определяем, сколько слотов осталось заполнить
        const remainingSlots = settings.crystalsCount - crystalItems.length;

        // Создаем пул предметов для заполнения оставшихся слотов
        // Сначала уникальные, невыигранные предметы
        const fillableItems = allPossibleItems.filter(item => !usedItemIds.has(item.id));

        // Если уникальных не хватает, повторяем
        let currentFillableItems = [...fillableItems];
        while (currentFillableItems.length < remainingSlots) {
            currentFillableItems = currentFillableItems.concat(fillableItems);
        }
        currentFillableItems = currentFillableItems.slice(0, remainingSlots); // Обрезаем до нужного количества

        // Добавляем их в массив кристаллов, используя getCrystalItemDetails и warehouseItemMap
        currentFillableItems.forEach(item => {
            crystalItems.push(getCrystalItemDetails(item, warehouseItemMap));
        });

        // Перемешиваем весь массив, чтобы выигрышные предметы были в случайных местах
        shuffleArray(crystalItems);

        // Создаем HTML для кристаллов
        for (let i = 0; i < settings.crystalsCount; i++) {
            const item = crystalItems[i];
            // Безопасная проверка на существование item перед доступом к его свойствам
            const itemIcon = item ? item.icon : '';
            const itemName = item ? item.name : '???';
            const itemCount = item ? item.count : '0';
            const enchantBadge = item && item.enchant > 0
                ? `<div class="crystal-enchant">+${item.enchant}</div>`
                : '';

            const crystalHTML = `
                <div class="magic-crystal ${item && item.isWinning ? 'winning-crystal' : ''}" data-index="${i}" data-item-id="${item ? item.id : ''}">
                    <div class="crystal-cover">
                        <div class="crystal-glow"></div>
                        <div class="crystal-runes"></div>
                    </div>
                    <div class="crystal-content">
                        <div class="crystal-item-container">
                            <img src="${itemIcon}" alt="${itemName}" class="crystal-item-image">
                            ${enchantBadge}
                        </div>
                        <div class="crystal-item-name">${itemName}</div>
                        <div class="crystal-item-count">x${itemCount}</div>
                    </div>
                </div>
            `;

            crystalsGrid.innerHTML += crystalHTML;
        }
    }

    // Анимация открытия кристаллов
    function revealCrystals(winningItems) { // Теперь принимает массив winningItems
        const crystals = document.querySelectorAll('.magic-crystal');
        const winningCrystals = document.querySelectorAll('.winning-crystal'); // Теперь получаем все выигрышные кристаллы

        if (!crystals.length) { // Исправлена проверка на наличие кристаллов
            console.error('Кристаллы не найдены для анимации.');
            // Если нет кристаллов, сразу переходим к показу выигрыша, если есть
            if (winningItems && winningItems.length > 0) {
                showWinningItem(winningItems);
            } else {
                // Если нет ни кристаллов, ни выигранных предметов, просто сбрасываем состояние
                resetModalState();
            }
            return;
        }

        // Массив индексов невыигрышных кристаллов
        // Исправлено: Более безопасный способ получения индексов невыигрышных кристаллов
        const nonWinningCrystalIndices = [];
        crystals.forEach((crystal, index) => {
            if (!crystal.classList.contains('winning-crystal')) {
                nonWinningCrystalIndices.push(index);
            }
        });

        // Перемешиваем массив индексов невыигрышных кристаллов
        shuffleArray(nonWinningCrystalIndices);

        // Определяем, сколько кристаллов открывать за раз для ускорения процесса
        const batchSize = Math.floor(nonWinningCrystalIndices.length / 5) || 1; // Открываем примерно 20% кристаллов за раз, минимум 1

        // Разбиваем индексы на группы
        const batches = [];
        for (let i = 0; i < nonWinningCrystalIndices.length; i += batchSize) {
            batches.push(nonWinningCrystalIndices.slice(i, i + batchSize));
        }

        // Открываем группы кристаллов
        function revealBatch(batchIndex) {
            if (batchIndex >= batches.length) {
                // Все невыигрышные кристаллы открыты, открываем выигрышные
                setTimeout(() => {
                    // Звук для выигрышных кристаллов (может быть один для всех или по очереди)
                    playSound('special_crystal');

                    // Открываем все выигрышные кристаллы
                    winningCrystals.forEach(crystal => {
                        if (crystal) { // Дополнительная проверка
                            crystal.classList.add('revealed');
                            // Добавляем специальные эффекты
                            setTimeout(() => {
                                crystal.classList.add('special-effect');
                            }, 300);
                        }
                    });

                    // Звук выигрыша
                    playSound('win');

                    // Переходим к экрану выигрыша после общей длительности подсветки
                    setTimeout(() => {
                        fadeOut(document.querySelector('.crystals-container'));
                        setTimeout(() => showWinningItem(winningItems), 500); // Передаем все выигранные предметы
                    }, settings.highlightDuration);
                }, settings.revealDelay);
                return;
            }

            // Открываем текущую группу кристаллов
            batches[batchIndex].forEach(index => {
                const crystal = crystals[index];
                if (crystal) { // Добавлена проверка на существование элемента
                    crystal.classList.add('revealed');
                }
            });

            // Звук открытия группы кристаллов
            playSound('crystal_reveal');

            // Переходим к следующей группе
            setTimeout(() => revealBatch(batchIndex + 1), settings.revealDelay);
        }

        // Начинаем открывать группы кристаллов
        setTimeout(() => revealBatch(0), settings.revealDelay);
    }

    // Функция для установки прозрачности модального окна
    function makeModalTransparent() {
        if (modal) {
            // Находим и меняем background модального окна
            const modalDialog = modal.querySelector('.modal-dialog');
            const modalContent = modal.querySelector('.modal-content');

            // Сохраняем исходные стили для восстановления
            if (modalContent && !modalContent.dataset.originalBg) {
                modalContent.dataset.originalBg = modalContent.style.backgroundColor || '';
            }

            // Применяем прозрачность
            if (modalContent) {
                modalContent.style.backgroundColor = 'rgba(0, 0, 0, 0)';
                modalContent.style.transition = 'background-color 0.5s ease';
            }
        }
    }


    // Показ выигрышного предмета(ов)
    function showWinningItem(winningItems) { // Теперь принимает массив winningItems
        restoreModalOpacity();

        const winningContainer = document.querySelector('.winning-container');
        if (!winningContainer) {
            console.error('Контейнер для отображения выигрыша не найден');
            return;
        }

        // Очищаем предыдущие выигрыши, если они были
        let winningItemsListContainer = winningContainer.querySelector('.winning-items-list');
        if (!winningItemsListContainer) {
            winningItemsListContainer = document.createElement('div');
            winningItemsListContainer.classList.add('winning-items-list');
            winningContainer.appendChild(winningItemsListContainer);
        }
        winningItemsListContainer.innerHTML = ''; // Очищаем контейнер

        // Добавляем каждый выигранный предмет
        winningItems.forEach(item => {
            const enchantBadge = item.enchant > 0
                ? `<div class="item-enchant">+${item.enchant}</div>`
                : '';

            const itemHTML = `
                <div class="winning-item-card">
                    <div class="winning-item-image-container">
                        <img src="${item.icon}" alt="${item.name}" class="winning-item-image">
                        ${enchantBadge}
                    </div>
                    <div class="winning-item-details">
                        <p class="winning-item-name">${item.name} ${item.add_name ? item.add_name : ''}</p>
                        <span class="winning-item-count">x${item.count}</span>
                    </div>
                </div>
            `;
            winningItemsListContainer.innerHTML += itemHTML; // Добавляем карточку предмета
        });


        // Эффекты для редких предметов (если есть хотя бы один S-тип)
        const hasSpecialItem = winningItems.some(item => item.crystal_type === 's');
        if (hasSpecialItem) {
            setTimeout(() => createConfettiEffect(winningContainer), 300);
        }


        // Показываем контейнер
        winningContainer.classList.remove('d-none');
        winningContainer.style.display = 'flex';
        setTimeout(() => {
            winningContainer.style.opacity = '1';
        }, 10);
    }

    function restoreModalOpacity() {
        const modal = document.getElementById('chestModal');
        if (modal) {
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                // Восстанавливаем исходные стили
                if (modalContent.dataset.originalBg) {
                    modalContent.style.backgroundColor = modalContent.dataset.originalBg;
                } else {
                    modalContent.style.backgroundColor = '';
                }
            }
        }
    }


    // Создание эффекта конфетти
    function createConfettiEffect(container) {
        let confettiContainer = container.querySelector('.confetti-container');
        if (!confettiContainer) {
            confettiContainer = document.createElement('div');
            confettiContainer.className = 'confetti-container';
            container.appendChild(confettiContainer);
        }

        // Создаем конфетти
        for (let i = 0; i < 150; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';

            // Случайные параметры
            const size = Math.random() * 10 + 5;
            const posX = Math.random() * 100;
            const delay = Math.random() * 5;
            const duration = Math.random() * 3 + 3;

            // Случайный цвет
            const colors = ['#f1c40f', '#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#1abc9c', '#f39c12'];
            const color = colors[Math.floor(Math.random() * colors.length)];

            confetti.style.cssText = `
                width: ${size}px;
                height: ${size}px;
                left: ${posX}%;
                top: -20px;
                background-color: ${color};
                animation: confetti-fall ${duration}s linear ${delay}s forwards;
            `;

            confettiContainer.appendChild(confetti);
        }
    }

    // ======== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ========

    // Сброс состояния модального окна
    function resetModalState() {

        // Останавливаем все звуки
        stopAllSounds();

        // Скрываем все контейнеры
        document.querySelectorAll('.opening-animation-container, .crystals-container, .winning-container').forEach(el => {
            el.classList.add('d-none');
            el.style.opacity = '0';
            el.style.display = 'none';
        });

        // Удаляем временные элементы
        document.querySelectorAll('.particles-container, .confetti-container, .winning-items-list').forEach(el => {
            if (el.parentNode) el.parentNode.removeChild(el);
        });

        // Возвращаем кнопку в исходное состояние
        if (openButton) openButton.disabled = false;

        // Возвращаем текст кнопки "Открыть кейс" в исходное состояние
        if (openChestMainBtn) {
            openChestMainBtn.textContent = 'Открыть кейс';
            delete openChestMainBtn.dataset.openChestCount; // Удаляем сохраненное количество
            if (openButtonGroup) {
                openButtonGroup.classList.remove('multi-open-selected');
            }
        }


        // Показываем основные контейнеры
        const previewContainer = document.querySelector('.chest-preview-container');
        const itemsContainer = document.querySelector('.chest-items-container');

        if (previewContainer) {
            previewContainer.style.display = 'block';
            previewContainer.style.opacity = '1';
        }

        if (itemsContainer) {
            itemsContainer.style.display = 'block';
            itemsContainer.style.opacity = '1';
        }
    }

    // Затухание элемента
    function fadeOut(element, duration = 300) {
        if (!element) return;

        element.style.opacity = '1';
        element.style.transition = `opacity ${duration}ms ease`;
        element.style.opacity = '0';

        setTimeout(() => {
            element.style.display = 'none';
        }, duration);
    }

    // Воспроизведение звука
    function playSound(soundName) {
        if (!settings.soundEnabled) return null;

        // Определяем источник звука в зависимости от типа
        let soundSrc;
        switch(soundName) {
            case 'crystal_reveal':
                soundSrc = 'tick';
                break;
            case 'crystals_appear':
                soundSrc = 'roulette_spin';
                break;
            case 'special_crystal':
                soundSrc = 'chest_open';
                break;
            default:
                soundSrc = soundName;
        }

        // Воспроизводим звук
        const soundElement = document.getElementById('sound-' + soundSrc);
        if (!soundElement) return null;

        try {
            soundElement.currentTime = 0;
            soundElement.volume = 0.5;
            soundElement.play().catch(e => console.warn('Ошибка воспроизведения звука:', e));
            return soundElement;
        } catch (e) {
            console.warn('Ошибка со звуком:', e);
            return null;
        }
    }

    // Остановка всех звуков
    function stopAllSounds() {
        document.querySelectorAll('audio').forEach(audio => {
            audio.pause();
            audio.currentTime = 0;
        });
    }

    // Перемешивание массива
    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }
});

// Добавляем стили для магических кристаллов
(function() {
    if (!document.getElementById('crystals-styles')) {
        const style = document.createElement('style');
        style.id = 'crystals-styles';
        style.innerHTML = `
        /* Контейнер для кристаллов */
        .crystals-container {
            width: 100%;
            padding: 20px 0;
            position: relative;
            margin: 20px 0;
            text-align: center;
            transition: opacity 0.3s ease;
        }
 
        /* Сетка кристаллов */
        /* Сетка кристаллов */
.crystals-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr); /* Изменено с 3 на 6 */
    gap: 15px; /* Уменьшен отступ между кристаллами */
    max-width: 900px; /* Увеличена максимальная ширина для размещения большего количества кристаллов */
    margin: 0 auto;
}

/* Магический кристалл */
.magic-crystal {
    position: relative;
    width: 100%;
    height: 130px; 
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    perspective: 1000px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
    transition: transform 0.5s ease;
}
        
        .magic-crystal:hover {
            transform: translateY(-5px) scale(1.03);
        }
        
        /* Обложка кристалла (закрытое состояние) */
        .crystal-cover {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #3a1c71, #d76d77, #ffaf7b);
            z-index: 2;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            backface-visibility: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Свечение кристалла */
        .crystal-glow {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
            opacity: 0.7;
            animation: crystal-pulse 2s infinite alternate;
        }
        
        /* Руны на кристалле */
        .crystal-runes {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: url('/src/component/plugins/chests/tpl/images/runes.png');
            background-size: cover;
            opacity: 0.3;
            mix-blend-mode: overlay;
        }
        
        /* Содержимое кристалла (открытое состояние) */
        .crystal-content {
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(30, 32, 41, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transform: rotateY(180deg);
            backface-visibility: hidden;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 15px;
        }
        
        /* Контейнер для предмета в кристалле */
        .crystal-item-container {
            position: relative;
            width: 70px;
            height: 70px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Изображение предмета */
        .crystal-item-image {
            max-width: 100%;
            max-height: 100%;
            filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.3));
        }
        
        /* Зачарование */
        .crystal-enchant {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            z-index: 3;
        }
        
        /* Название предмета */
        .crystal-item-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #fff;
            text-align: center;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            height: 36px;
        }
        
        /* Количество предметов */
        .crystal-item-count {
            font-size: 12px;
            color: #ddd;
        }
        
        /* Состояние открытого кристалла */
        .magic-crystal.revealed .crystal-cover {
            transform: rotateY(180deg);
        }
        
        .magic-crystal.revealed .crystal-content {
            transform: rotateY(0);
        }
        
        /* Специальные эффекты для выигрышного кристалла */
        .magic-crystal.special-effect {
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.8);
            z-index: 10;
        }
        
        .magic-crystal.special-effect .crystal-content {
            background: rgba(41, 35, 14, 0.9);
        }
        
        .magic-crystal.special-effect .crystal-item-image {
            animation: winning-item-pulse 1s infinite alternate;
            filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.8));
        }
        
        /* Анимация пульсации кристалла */
        @keyframes crystal-pulse {
            0% { opacity: 0.5; }
            100% { opacity: 0.9; }
        }
        
        /* Анимация пульсации выигрышного предмета */
        @keyframes winning-item-pulse {
            0% { transform: scale(1); filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.5)); }
            100% { transform: scale(1.1); filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.8)); }
        }
        
        /* Адаптивность для мобильных устройств */
        @media (max-width: 768px) {
            .crystals-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
      
            .magic-crystal {
                height: 160px;
            }
        }

        /* Стили для списка выигранных предметов */
        .winning-items-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            max-height: 400px; /* Ограничиваем высоту для скролла, если много предметов */
            overflow-y: auto; /* Добавляем скролл */
            padding: 10px;
        }

        .winning-item-card {
            background: rgba(30, 32, 41, 0.9);
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            width: 120px; /* Фиксированная ширина карточки */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between; /* Для равномерного распределения контента */
            min-height: 150px; /* Минимальная высота карточки */
        }

        .winning-item-image-container {
            position: relative;
            width: 80px;
            height: 80px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .winning-item-image {
            max-width: 100%;
            max-height: 100%;
        }

        .winning-item-details {
            width: 100%; /* Занимает всю ширину карточки */
        }

        .winning-item-name {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 2px;
            white-space: normal; /* Разрешаем перенос текста */
            word-wrap: break-word; /* Перенос длинных слов */
            height: 36px; /* Фиксированная высота для 2-х строк */
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .winning-item-count {
            font-size: 12px;
            color: #ddd;
        }

        /* Зачарование на выигранном предмете */
        .winning-item-card .item-enchant {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            z-index: 3;
        }
        `;
        document.head.appendChild(style);
    }
})();
$(document).ready(function() {
    let cases = [];
    let editingCaseId = null;
    let isSavingOrder = false; // Флаг для отслеживания процесса сохранения

    function saveCasesOrder() {
        // Предотвращаем множественные запросы
        if (isSavingOrder) {
            return;
        }

        isSavingOrder = true;

        // Собираем данные о кейсах
        let casesOrder = {};

        // Собираем порядок кейсов
        $('.case-item').each(function(index) {
            const caseId = $(this).data('case-id');
            if (!caseId) {
                console.error(`Элемент #${index} не имеет data-case-id:`, $(this));
                return;
            }

            casesOrder[index] = caseId;
        });

        // Детальное логирование
        console.log('Собранный порядок кейсов:', casesOrder);

        // Если нет данных для отправки - выходим
        if (Object.keys(casesOrder).length === 0) {
            noticeError('Не удалось собрать данные о кейсах для сортировки');
            isSavingOrder = false;
            return;
        }

        // Показываем индикатор загрузки
        const loadingIndicator = $('<div class="save-order-indicator position-fixed p-3 bg-white shadow rounded" style="top: 20px; right: 20px; z-index: 9999;"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Сохранение порядка кейсов...</div>');
        $('body').append(loadingIndicator);

        // Отправляем запрос на сервер
        $.ajax({
            type: 'POST',
            url: '/admin/plugin/chests/update/order',
            dataType: 'json',
            data: {
                cases_order: casesOrder
            },
            success: function(response) {
                console.log('Ответ сервера:', response);

                loadingIndicator.remove();
                isSavingOrder = false;

                if (response.ok) {
                    // Показываем уведомление об успехе
                    const successIndicator = $('<div class="save-order-indicator position-fixed p-3 bg-success text-white shadow rounded" style="top: 20px; right: 20px; z-index: 9999;"><i class="bi bi-check-circle me-2"></i>' + (response.message || 'Порядок кейсов сохранен') + '</div>');
                    $('body').append(successIndicator);

                    setTimeout(function() {
                        successIndicator.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 3000);

                    // Обновляем данные кейсов на странице
                    loadCases();
                } else {
                    noticeError('Ошибка сохранения порядка кейсов: ' + (response.message || 'Неизвестная ошибка'), 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка запроса:', { xhr, status, error });

                loadingIndicator.remove();
                isSavingOrder = false;

                let errorMessage = 'Ошибка сохранения порядка кейсов';

                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage += ': ' + response.message;
                        }
                    } catch (e) {
                        console.warn('Не удалось разобрать ответ сервера:', e);
                    }
                }

                noticeError(errorMessage, 'danger');
            }
        });
    }

// Загрузка всех кейсов с сервера
    function loadCases() {
        $.ajax({
            type: 'POST',
            url: '/admin/plugin/chests/get/all',
            dataType: 'json',
            success: function(response) {
                if (response.ok) {
                    // Проверяем тип данных cases в ответе
                    if (Array.isArray(response.cases)) {
                        cases = response.cases;
                    } else if (typeof response.cases === 'object' && response.cases !== null) {
                        // Если объект, преобразуем в массив
                        cases = Object.keys(response.cases).map(key => {
                            const caseData = response.cases[key];
                            caseData.id = key; // Добавляем ID (название) кейса в данные
                            return caseData;
                        });
                    } else {
                        cases = [];
                    }
                    renderCasesList();
                    initSortable();
                } else {
                    noticeError('Ошибка загрузки кейсов: ' + (response.message || ''), 'danger');
                }
            },
            error: function(xhr, status, error) {
                noticeError('Ошибка загрузки кейсов', 'danger');
            }
        });
    }

    function renderCasesList() {
        const casesList = $('#casesList');
        casesList.empty();

        if (!Array.isArray(cases) || cases.length === 0) {
            casesList.append('<div class="alert alert-info">Кейсы еще не созданы</div>');
            return;
        }

        // Отображаем кейсы в том порядке, в котором они получены с сервера
        cases.forEach(function(caseItem, index) {
            // Проверка наличия всех необходимых данных
            if (!caseItem || !caseItem.id) {
                console.error('Некорректные данные кейса:', caseItem);
                return;
            }

            // Проверка наличия icon и его корректности
            const chestIconId = caseItem.icon || 1;
            const chestIconPath = `${template_plugin}/tpl/images/chest/chest-${chestIconId}.webp`;
            const chestBgPath = caseItem.background || `${template_plugin}/tpl/images/background/bg-1.png`;

            // Текущий порядок для отображения
            const displayIndex = index;

            const caseElement = `
    <div class="case-item mb-3 p-3 border rounded position-relative" 
         data-case-id="${caseItem.id}" 
         data-case-sort="${displayIndex}">
        
        <!-- Индикатор порядка сортировки -->
        <span class="position-absolute badge bg-primary rounded-pill" style="top: -10px; right: -10px;">
            ${position}: ${displayIndex + 1}
        </span>
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center">
                <div class="drag-handle me-2 cursor-move" title="${move}">
                    <i class="bi bi-grip-vertical text-muted fs-4"></i>
                </div>
                <div class="position-relative" style="width: 48px; height: 48px;">
                    <img src="${chestBgPath}" alt="Background" class="position-absolute" style="width: 48px; height: 48px; object-fit: cover;">
                    <img src="${chestIconPath}" alt="Case Icon" class="position-absolute" style="width: 48px; height: 48px; object-fit: contain;">
                </div>
                <h6 class="mb-0 ms-2">${caseItem.name}</h6>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-primary edit-case-btn me-2" data-case-id="${caseItem.id}">
                    <i class="bi bi-pencil"></i> ${edit}
                </button>
                <button type="button" class="btn btn-sm btn-danger delete-case-btn" data-case-id="${caseItem.id}">
                    <i class="bi bi-trash"></i> ${deleteData}
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <p class="mb-1"><strong>${cost}:</strong> ${caseItem.cost} ${phrase_spherecoin}</p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>${chest_type}:</strong> ${caseItem.type || 'Middle'}</p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>${itemsPhrase}:</strong> ${Array.isArray(caseItem.items) ? caseItem.items.length : 0}</p>
            </div>
            <div class="col-md-12 mt-2">
                <button type="button" class="btn btn-sm btn-info view-items-btn" data-case-id="${caseItem.id}">
                    <i class="bi bi-eye me-1"></i>${view_items}
                </button>
            </div>
        </div>
    </div>
    `;

            casesList.append(caseElement);
        });
    }

    // Функция для обновления типа кейса в предпросмотре
    function updateCaseTypePreview(type) {
        var caseTypeContainer = $("#selectedCaseTypeContainer");
        var caseTypeElement = $("#selectedCaseType");

        // Удаляем все существующие классы цветов
        caseTypeElement.removeClass("bg-primary bg-success bg-warning bg-danger bg-info bg-secondary");

        // Если выбран тип "No Use", скрываем контейнер
        if (type === "No Use") {
            caseTypeContainer.hide();
            return;
        }

        // Выбираем цвет в зависимости от типа
        switch(type) {
            case "Middle":
                caseTypeElement.addClass("bg-primary");
                break;
            case "Low":
                caseTypeElement.addClass("bg-secondary");
                break;
            case "Top":
                caseTypeElement.addClass("bg-warning");
                break;
            case "Event":
                caseTypeElement.addClass("bg-info");
                break;
            default:
                caseTypeElement.addClass("bg-primary");
        }

        // Обновляем текст и показываем элемент
        caseTypeElement.text(type);
        caseTypeContainer.show();
    }

    // Обновление предпросмотра при изменении типа кейса
    $("#caseType").on("change", function() {
        var caseType = $(this).val();
        updateCaseTypePreview(caseType);
    });

    // Инициализация сортировки с улучшенной функциональностью
    function initSortable() {
        $("#casesList").sortable({
            items: ".case-item",
            handle: ".drag-handle",
            placeholder: "case-item-placeholder",
            forcePlaceholderSize: true,
            tolerance: "pointer",
            cursor: "grabbing",
            opacity: 0.9,
            revert: 150,
            scroll: true,
            scrollSpeed: 10,
            scrollSensitivity: 50,

            // Фиксируем положение хелпера относительно курсора
            cursorAt: { left: 20, top: 40 },

            // Улучшенный хелпер для перетаскивания
            helper: function(event, element) {
                // Получаем данные о размерах оригинального элемента
                const originalWidth = element.outerWidth();
                const originalHeight = element.outerHeight();

                // Создаем упрощенный клон для перетаскивания
                const clone = $('<div class="drag-helper card shadow p-2"></div>');

                // Получаем основную информацию о кейсе
                const caseId = element.data('case-id');
                const caseData = cases.find(c => c.id == caseId);
                const iconSrc = element.find('img').attr('src');
                const caseName = element.find('h6').text();

                // Создаем минималистичное представление кейса для перетаскивания
                const content = `
                    <div class="d-flex align-items-center">
                        <img src="${iconSrc}" alt="Case Icon" width="32" height="32" class="me-2">
                        <h6 class="mb-0">${caseName}</h6>
                    </div>
                `;

                clone.html(content);
                clone.css({
                    'width': '300px',
                    'background-color': '#fff',
                    'z-index': 9999,
                    'border': '2px solid #0d6efd',
                    'border-radius': '8px',
                    'box-shadow': '0 8px 16px rgba(0,0,0,0.15)',
                    'opacity': 0.9
                });

                return clone;
            },

            start: function(event, ui) {
                // Добавляем класс при начале перетаскивания к исходному элементу
                $(ui.item).addClass('case-item-dragging');

                // Стилизуем хелпер
                $(ui.helper).addClass('dragging-active');

                // Анимация эффекта "подъема"
                $(ui.item).css('transform', 'scale(0.98)');
            },

            stop: function(event, ui) {
                // Удаляем классы и сбрасываем стили
                $(ui.item).removeClass('case-item-dragging');
                $(ui.item).css('transform', '');
            },

            update: function(event, ui) {
                // Сохраняем новый порядок
                saveCasesOrder();
            }
        }).disableSelection();

        // Улучшаем отзывчивость элементов при наведении
        $(".case-item").hover(
            function() {
                $(this).find('.drag-handle').addClass('drag-handle-active');
            },
            function() {
                $(this).find('.drag-handle').removeClass('drag-handle-active');
            }
        );
    }

    // Обновление общего шанса выпадения
    function updateTotalChance() {
        let totalChance = 0;
        $('.item-chance-input').each(function() {
            const chance = parseFloat($(this).val()) || 0;
            totalChance += chance;
        });

        $('.total-chance').text(totalChance.toFixed(2));

        // Визуальная индикация правильного суммарного шанса
        if (Math.abs(totalChance - 100) < 0.01) {
            $('.total-chance').removeClass('bg-danger').addClass('bg-success');
        } else {
            $('.total-chance').removeClass('bg-success').addClass('bg-danger');
        }
    }

    // Инициализация выбора иконки сундука
    function initChestIconSelection() {
        $('.chest-icon-input').prop('checked', false);
        $('.chest-icon-label').removeClass('border-primary');

        // Выбор первой иконки по умолчанию для нового кейса
        if (editingCaseId === null) {
            $('#chest-1').prop('checked', true);
            $('#chest-1').siblings('.chest-icon-label').addClass('border-primary');
        }
    }

    // Открытие модального окна для создания нового кейса
// Открытие модального окна для создания нового кейса
    $('#addCaseBtn').on('click', function() {
        // Сбрасываем глобальное состояние редактирования
        editingCaseId = null;

        updateCaseTypePreview('Middle');

        // Очистка полей модального окна
        $('#caseId').val(''); // Очищаем скрытое поле с оригинальным названием
        $('#caseName').val('');
        $('#caseCost').val('100');
        $('#caseType').val('Middle');
        $('#caseItemsTable tbody').empty();

        // Инициализация выбора иконки кейса
        initChestIconSelection();
        updateTotalChance();

        // Изменение заголовка модального окна
        $('#caseModalLabel').text(phrase_chest_name);

        // Открытие модального окна
        $('#caseModal').modal('show');
    });

// Добавляем обработчик события скрытия модального окна
    $('#caseModal').on('hidden.bs.modal', function () {
        // Сбрасываем переменные состояния
        editingCaseId = null;
        $('#caseId').val('');

        // Очищаем содержимое модальной формы
        $('#caseName').val('');
        $('#caseCost').val('100');
        $('#caseType').val('Middle');
        $('#caseItemsTable tbody').empty();

        // Сбрасываем выбор иконки
        $('.chest-icon-input').prop('checked', false);
        $('.chest-icon-label').removeClass('border-primary');

        // Обновляем общий шанс
        updateTotalChance();
        updateCaseTypePreview('Middle');
    });

    // Выбор иконки кейса
    $(document).on('change', '.chest-icon-input', function() {
        // Удаляем выделение со всех иконок
        $('.chest-icon-label').removeClass('border-primary');

        // Добавляем выделение выбранной иконке
        $(this).siblings('.chest-icon-label').addClass('border-primary');
    });

    // Обработка кликов по кнопкам редактирования кейса
    $(document).on('click', '.edit-case-btn', function() {
        const caseId = $(this).data('case-id');
        editingCaseId = caseId;

        // Найти кейс в массиве по ID
        const caseItem = cases.find(item => item.id == caseId);

        if (caseItem) {
            // Заполнение полей модального окна
            $('#caseId').val(caseId); // Сохраняем оригинальное имя кейса
            $('#caseName').val(caseItem.name).trigger('input'); // Вызываем событие input для обновления превью
            $('#caseCost').val(caseItem.cost).trigger('input'); // Вызываем событие input для обновления превью
            $('#caseType').val(caseItem.type || 'Middle');

            updateCaseTypePreview(caseItem.type || 'Middle');

            // Выбор иконки
            initChestIconSelection();
            const iconId = caseItem.icon || 1;
            $(`#chest-${iconId}`).prop('checked', true).trigger('change'); // Вызываем событие change

            // Выбор фона, если он существует
            if (caseItem.background) {
                const bgId = caseItem.background.replace(/.*bg-(\d+)\.png$/, '$1');
                $(`#bg-${bgId}`).prop('checked', true).trigger('change'); // Вызываем событие change
            } else {
                // По умолчанию
                $('#bg-1').prop('checked', true).trigger('change');
            }

            // Очистка и заполнение таблицы предметов
            $('#caseItemsTable tbody').empty();

            if (caseItem.items && caseItem.items.length > 0) {
                caseItem.items.forEach(function(item) {
                    addItemRow(item);
                });
            }

            updateTotalChance();

            // Изменение заголовка модального окна
            $('#caseModalLabel').text(`${edit_case}`);

            // Открытие модального окна
            $('#caseModal').modal('show');
        } else {
            noticeError('Ошибка: Кейс не найден', 'danger');
        }
    });


    // Добавить новую строку предмета в таблицу
    function addItemRow(itemData = null) {
        const rowCount = $('#caseItemsTable tbody tr').length;

        if (rowCount >= 50) {
            noticeError(`${max_items_chest}`, 'warning');
            return;
        }

        const rowId = 'item_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        const itemId = itemData ? itemData.itemId : '0';
        const minCount = itemData ? itemData.minCount : '1';
        const maxCount = itemData ? itemData.maxCount || minCount : '1';
        const enchant = itemData ? itemData.enchant : '0';
        const chance = itemData ? itemData.chance : '0';

        // Получение иконки предмета (если доступна)
        let itemIconSrc = '/uploads/images/icon/NOIMAGE.webp';
        if (itemData && itemData.icon) {
            itemIconSrc = itemData.icon;
        }

        const newRow = `
            <tr id="${rowId}" class="item-row" ${itemData && itemData.name ? `data-item-name="${itemData.name}"` : ''}>
                <td class="text-center">
                    <img class="item-icon" src="${itemIconSrc}" alt="Item Icon" width="32" height="32">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" min="0" step="1" class="form-control item-id-input"
                            placeholder="ID предмета" data-row-id="${rowId}" value="${itemId}"> 
                    </div>
                </td>
                <td>
                    <input type="number" min="1" step="1" class="form-control form-control-sm item-min-count" placeholder="Кол-во" value="${minCount}">
                </td>
                <td>
                    <input type="number" min="0" step="1" class="form-control form-control-sm item-enchant" placeholder="Заточка" value="${enchant}">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" min="0" max="100" step="0.01" class="form-control item-chance-input"
                            placeholder="Шанс" value="${chance}"
                            oninput="this.value = Math.min(100, Math.max(0, this.value))">
                        <span class="input-group-text">%</span>
                    </div>
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-danger removeItemBtn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;

        $('#caseItemsTable tbody').append(newRow);
        updateTotalChance();

        // Если добавляем новый предмет, фокусируемся на поле ID
        if (!itemData) {
            $(`#${rowId} .item-id-input`).focus();
        }
    }

    // Добавление нового предмета в таблицу
    $('#addItemToCaseBtn').on('click', function() {
        addItemRow();
    });

    // Удаление предмета из таблицы
    $(document).on('click', '.removeItemBtn', function() {
        $(this).closest('tr').remove();
        updateTotalChance();
    });

    // Обновление общего шанса при изменении значений
    $(document).on('input', '.item-chance-input', function() {
        updateTotalChance();
    });

    // Обновление иконки предмета при изменении ID
    $(document).on('change', '.item-id-input', function() {
        let itemId = $(this).val().trim();
        const row = $(this).closest('tr');
        const icon = row.find('.item-icon');

        if (itemId && itemId > 0) {
            $.ajax({
                type: 'POST',
                url: '/admin/client/item/info',
                dataType: 'json',
                data: { itemID: itemId },
                success: function(response) {
                    if (response.ok && response.item && response.item.icon) {
                        icon.attr('src', response.item.icon);
                        row.attr('data-item-name', response.item.name);

                        // Показать всплывающую подсказку с именем предмета
                        showItemTooltip(row, response.item.name, response.item.add_name);
                    } else {
                        icon.attr('src', '/uploads/images/icon/NOIMAGE.webp');
                        row.removeAttr('data-item-name');
                        noticeError(`Предмет ID ${itemId} не найден`, 'warning');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Произошла ошибка:', error);
                    icon.attr('src', '/uploads/images/icon/NOIMAGE.webp');
                    row.removeAttr('data-item-name');
                }
            });
        } else {
            icon.attr('src', '/uploads/images/icon/NOIMAGE.webp');
            row.removeAttr('data-item-name');
        }
    });

    // Показать всплывающую подсказку с именем предмета
    function showItemTooltip(rowElement, itemName, addName) {
        const tooltip = $('<div class="item-tooltip"></div>');
        tooltip.text(addName ? `${addName} ${itemName}` : itemName);

        // Добавляем стили для всплывающей подсказки
        tooltip.css({
            'position': 'absolute',
            'background-color': '#333',
            'color': '#fff',
            'padding': '5px 10px',
            'border-radius': '4px',
            'z-index': '1000',
            'font-size': '12px',
            'max-width': '200px',
            'opacity': '0',
            'transition': 'opacity 0.3s'
        });

        $('body').append(tooltip);

        const iconPos = rowElement.find('.item-icon').offset();
        tooltip.css({
            'top': iconPos.top - tooltip.outerHeight() - 5,
            'left': iconPos.left - (tooltip.outerWidth() / 2) + 16
        });

        tooltip.animate({opacity: 1}, 200);

        setTimeout(function() {
            tooltip.animate({opacity: 0}, 200, function() {
                tooltip.remove();
            });
        }, 3000);
    }

    // Сбор данных о предметах из таблицы
    function collectItemsData() {
        const items = [];

        $('#caseItemsTable tbody tr').each(function() {
            const $row = $(this);
            const itemId = $row.find('.item-id-input').val();
            const minCount = $row.find('.item-min-count').val();
            const enchant = $row.find('.item-enchant').val();
            const chance = $row.find('.item-chance-input').val();

            // Проверка обязательных полей
            if (!itemId || itemId <= 0) {
                return; // Пропускаем предметы с некорректным ID
            }

            // Собираем данные о предмете, включая имя и иконку если они есть
            const itemData = {
                itemId: parseInt(itemId),
                minCount: parseInt(minCount) || 1,
                enchant: parseInt(enchant) || 0,
                chance: parseFloat(chance) || 0
            };

            // Добавляем дополнительные данные, если они есть
            if ($row.attr('data-item-name')) {
                itemData.name = $row.attr('data-item-name');
            }
            if ($row.attr('data-item-add-name')) {
                itemData.add_name = $row.attr('data-item-add-name');
            }

            // Получаем иконку предмета из элемента img
            const iconSrc = $row.find('.item-icon').attr('src');
            if (iconSrc && iconSrc !== '/uploads/images/icon/NOIMAGE.webp') {
                itemData.icon = iconSrc;
            }

            items.push(itemData);
        });

        return items;
    }

// Функция для проверки уникальности имени кейса
    function checkCaseNameUnique(name, originalName) {
        // Если имя не менялось, всё в порядке
        if (name === originalName) {
            return true;
        }

        // Ищем кейс с таким же именем
        const existingCase = cases.find(caseItem => caseItem.name.toLowerCase() === name.toLowerCase() && caseItem.id !== originalName);

        return !existingCase;
    }

// Модифицированный обработчик клика на кнопку сохранения
    $('#saveCaseBtn').on('click', function() {
        const caseName = $('#caseName').val();
        const originalCaseName = $('#caseId').val();
        const caseIcon = $('input[name="caseIcon"]:checked').data('chest-icon-id');
        const caseCost = $('#caseCost').val();
        const caseType = $('#caseType').val() || 'Middle';
        const caseBg = $('#caseBgImage').val();

        // Валидация полей
        if (!caseName) {
            noticeError(`${enter_name}`, 'warning');
            return;
        }

        // Проверка уникальности имени кейса
        if (!checkCaseNameUnique(caseName, originalCaseName)) {
            noticeError(`${chest_name_exists}`, 'warning');
            return;
        }

        if (!caseIcon) {
            noticeError(`${select_chest_icon}`, 'warning');
            return;
        }

        if (!caseCost || isNaN(caseCost) || caseCost < 0) {
            noticeError(`${chest_price_error}`, 'warning');
            return;
        }

        // Сбор данных о предметах
        const items = collectItemsData();

        if (items.length === 0) {
            noticeError(`${chest_items_error}`, 'warning');
            return;
        }

        // Проверка общего шанса выпадения
        const totalChance = parseFloat($('.total-chance').text());
        if (Math.abs(totalChance - 100) > 0.1) {
            noticeError(`${general_chance_error}`, 'warning');
            return;
        }

        const caseData = {
            id: caseName,
            name: caseName,
            original_name: originalCaseName, // Добавляем оригинальное имя
            icon: caseIcon,
            cost: caseCost,
            type: caseType,
            background: caseBg, // Добавляем фон в данные
            items: items
        };

        // Показываем индикатор загрузки
        showLoadingOverlay();

        // Отправка данных на сервер
        $.ajax({
            type: 'POST',
            url: '/admin/plugin/chests/setting/save',
            dataType: 'json',
            data: caseData,
            success: function(response) {
                hideLoadingOverlay();

                if (response.ok) {
                    noticeSuccess(originalCaseName ? `${chest_updated}` : `${chest_created}`, 'success');

                    // Закрытие модального окна
                    $('#caseModal').modal('hide');

                    // Обновление списка кейсов
                    loadCases();
                } else {
                    noticeError('Error: ' + (response.message || ''), 'danger');
                }
            },
            error: function(xhr, status, error) {
                hideLoadingOverlay();
                noticeError('Ошибка сохранения кейса', 'danger');
            }
        });
    });


    // Удаление кейса
    $(document).on('click', '.delete-case-btn', function() {
        const caseId = $(this).data('case-id');
        const caseName = $(this).closest('.case-item').find('h6').text();

        if (confirm(`${delete_case}`)) {
            showLoadingOverlay();

            $.ajax({
                type: 'POST',
                url: '/admin/plugin/chests/delete',
                dataType: 'json',
                data: { id: caseId },
                success: function(response) {
                    hideLoadingOverlay();

                    if (response.ok) {
                        noticeSuccess(`${chest_deleted}`, 'success');
                        loadCases();
                    } else {
                        noticeError('Error : ' + (response.message || ''), 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoadingOverlay();
                    noticeError('Ошибка удаления кейса', 'danger');
                }
            });
        }
    });

    // Просмотр предметов в кейсе
    $(document).on('click', '.view-items-btn', function() {
        const caseId = $(this).data('case-id');
        const caseItem = cases.find(item => item.id == caseId);

        if (caseItem) {
            // Изменение заголовка модального окна
            $('#viewItemsModalLabel').text(`${chest_items}: ` + caseItem.name);
            $('#viewItemsModalLabel').data('case-id', caseId);

            // Очистка и заполнение таблицы предметов
            $('#viewItemsTable tbody').empty();

            if (caseItem.items && caseItem.items.length > 0) {
                caseItem.items.forEach(function(item) {
                    const itemIconSrc = item.icon || '/uploads/images/icon/NOIMAGE.webp';
                    const itemName = item.name || `${item_phrase_name} #` + item.itemId;
                    const itemAddName = item.add_name || '';

                    const newRow = `
                    <tr>
                        <td class="text-center">
                            <img src="${itemIconSrc}" alt="Item Icon" width="32" height="32"
                                data-bs-toggle="tooltip" data-bs-placement="top"
                                title="${itemAddName} ${itemName}">
                        </td>
                        <td>${item.itemId}</td>
                        <td>${itemAddName} ${itemName}</td>
                        <td>${item.minCount}</td>
                        <td>${item.enchant || 0}</td>
                        <td class="text-end">${item.chance}%</td>
                    </tr>
                    `;

                    $('#viewItemsTable tbody').append(newRow);
                });

                // Инициализация всплывающих подсказок
                $('[data-bs-toggle="tooltip"]').tooltip();
            } else {
                $('#viewItemsTable tbody').append(`<tr><td colspan="6" class="text-center">${no_items_chest}</td></tr>`);
            }

            // Открытие модального окна
            $('#viewItemsModal').modal('show');
        } else {
            noticeError('Ошибка: Кейс не найден', 'danger');
        }
    });

    // Показать/скрыть индикатор загрузки
    function showLoadingOverlay() {
        let overlay = $('#loadingOverlay');

        if (overlay.length === 0) {
            overlay = $(`<div id="loadingOverlay" class="position-fixed w-100 h-100 d-flex justify-content-center align-items-center" style="top: 0; left: 0; background-color: rgba(0,0,0,0.3); z-index: 9999;">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">${data_loading}...</span>
                </div>
            </div>`);

            $('body').append(overlay);
        } else {
            overlay.show();
        }
    }

    function hideLoadingOverlay() {
        $('#loadingOverlay').remove();
    }

    // Распределение шансов равномерно между всеми предметами
    $(document).on('click', '#distributeChanceBtn', function() {
        const itemRows = $('#caseItemsTable tbody tr');
        const itemCount = itemRows.length;

        if (itemCount > 0) {
            const evenChance = (100 / itemCount).toFixed(2);

            itemRows.each(function() {
                $(this).find('.item-chance-input').val(evenChance);
            });

            updateTotalChance();
            noticeSuccess(`${even_chance}`, 'success');
        }
    });

    // Обработка клика на кнопку редактирования в окне просмотра предметов
    $(document).on('click', '.edit-items-btn', function() {
        // Закрываем текущее модальное окно
        $('#viewItemsModal').modal('hide');

        // Получаем ID кейса из данных модального окна
        const caseId = $('#viewItemsModalLabel').data('case-id');

        // Находим кнопку редактирования кейса по ID и программно кликаем на нее
        setTimeout(function() {
            $(`.edit-case-btn[data-case-id="${caseId}"]`).click();
        }, 500);
    });

    // Инициализация при загрузке страницы
    function init() {
        // Проверяем, активен ли плагин
        if (pluginActive !== "1") {
            $('#addCaseBtn').prop('disabled', true);
        }

        // Добавляем кнопку для равномерного распределения шансов
        const chanceDistributeBtn = $(`
            <button id="distributeChanceBtn" type="button" class="btn btn-sm btn-outline-primary ms-2">
                <i class="bi bi-pie-chart-fill me-1"></i>${distribute_chance}
            </button>
        `);

        $('#addItemToCaseBtn').after(chanceDistributeBtn);

        // Добавляем стили для сортировки
        $('head').append(`
            <style>
                .cursor-move { cursor: grab !important; }
                .cursor-move:active { cursor: grabbing !important; }
                
                .case-item-placeholder {
                    border: 2px dashed #0d6efd;
                    background-color: rgba(13, 110, 253, 0.08);
                    min-height: 100px;
                    margin-bottom: 1rem;
                    border-radius: 6px;
                    animation: pulse-border 1.5s infinite;
                }
                
                @keyframes pulse-border {
                    0% { border-color: rgba(13, 110, 253, 0.5); }
                    50% { border-color: rgba(13, 110, 253, 1); }
                    100% { border-color: rgba(13, 110, 253, 0.5); }
                }
                
                .dragging-active {
                    transform: rotate(1deg) scale(1.02);
                    box-shadow: 0 8px 20px rgba(0,0,0,0.2) !important;
                    transition: all 0.2s;
                }
                
                .case-item-dragging {
                    opacity: 0.6;
                    background-color: #f8f9fa;
                }
                
                .drag-handle {
                    cursor: grab;
                    transition: all 0.2s;
                    border-radius: 4px;
                    padding: 6px;
                }
                
                .drag-handle:hover,
                .drag-handle-active {
                    background-color: rgba(13, 110, 253, 0.1);
                    color: #0d6efd !important;
                }
                
                .drag-handle:active {
                    cursor: grabbing;
                    background-color: rgba(13, 110, 253, 0.2);
                }
                
                /* Специальный стиль для хелпера */
                .drag-helper {
                    cursor: grabbing !important;
                    transition: transform 0.2s, box-shadow 0.2s;
                }
                
                .save-order-indicator {
                    animation: fadeIn 0.3s, fadeOut 0.3s 2.7s;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                @keyframes fadeOut {
                    from { opacity: 1; transform: translateY(0); }
                    to { opacity: 0; transform: translateY(-20px); }
                }
            </style>
        `);

        loadCases();
    }

    init();
});
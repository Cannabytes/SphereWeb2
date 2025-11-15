$('input[autocomplete="off"]').val('');
const tooltipTriggerList = document.querySelectorAll(
  '[data-bs-toggle="tooltip"]'
);
const tooltipList = [...tooltipTriggerList].map(
  (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
);
const popoverTriggerList = document.querySelectorAll(
    '[data-bs-toggle="popover"]'
);
const popoverList = [...popoverTriggerList].map(
    (popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl)
);

function basename(str) {
    var base = new String(str).substring(str.lastIndexOf('\\') + 1);
    if (base.lastIndexOf("\\") != -1)
        base = base.substring(0, base.lastIndexOf("\\"));
    return base;
}

$('.copy').on('click', function() {
    const elementId = $(this).data('object-id');
    var textToCopy = $('#' + elementId).val().trim();
    navigator.clipboard.writeText(textToCopy).then(function () {
    }).catch(function (error) {
        console.error("Ошибка при копировании: ", error);
    });
});

function AjaxSend(url, method, data, isReturn = false, timeout = 5, funcName = null) {
    // Автоматически добавляем CSRF токен для POST запросов
    const httpMethod = (method || 'GET').toUpperCase();
    if (httpMethod === 'POST' || httpMethod === 'PUT' || httpMethod === 'DELETE' || httpMethod === 'PATCH') {
        const csrfToken = window.CSRF && typeof window.CSRF.getToken === 'function' ? window.CSRF.getToken() : null;
        if (csrfToken) {
            if (data instanceof FormData) {
                data.append(window.CSRF.getTokenName(), csrfToken);
            } else if (typeof data === 'string') {
                const separator = data.length > 0 ? '&' : '';
                data += separator + encodeURIComponent(window.CSRF.getTokenName()) + '=' + encodeURIComponent(csrfToken);
            } else if (typeof data === 'object' && data !== null) {
                data[window.CSRF.getTokenName()] = csrfToken;
            } else {
                // Если формат неизвестен, конвертируем в объект
                data = {
                    [window.CSRF.getTokenName()]: csrfToken
                };
            }
        }
    }

    return new Promise(function(resolve, reject) {
        $.ajax({
            url: url,
            type: httpMethod,
            data: data,
            timeout: timeout * 1000,
            dataType: 'json',
            success: function (response) {
                if (isReturn) {
                    resolve(response);
                } else {
                    if (response === null) {
                        resolve(null);
                        return;
                    }

                    // Проверка существования поля g-recaptcha
                    if (response.hasOwnProperty('g-recaptcha-response')) {
                        if (response.ok === false) {
                            grecaptcha.reset();
                        }
                    }

                    if (funcName && typeof window[funcName] === 'function') {
                        window[funcName](response);
                    }

                    responseAnalysis(response);
                    AjaxEvent(url, method, data, response);
                    resolve(response); // Возвращаем response вместо пустого resolve
                }
            },
            error: function (xhr, status, error) {
                console.error('Ошибка при выполнении AJAX-запроса:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });

                // Пытаемся распарсить ответ сервера, если он есть
                try {
                    const errorResponse = xhr.responseText ? JSON.parse(xhr.responseText) : null;
                    reject(errorResponse || {
                        ok: false,
                        message: 'Произошла ошибка при выполнении запроса',
                        error: error
                    });
                } catch (e) {
                    reject({
                        ok: false,
                        message: 'Произошла ошибка при выполнении запроса',
                        error: error
                    });
                }
            }
        });
    });
}

function AjaxEvent(url, method, data, response) {

    if (typeof data === 'string') {
        data = data.split('&').reduce(function(obj, pair) {
            var parts = pair.split('=');
            obj[decodeURIComponent(parts[0])] = decodeURIComponent(parts[1]);
            return obj;
        }, {});
    }

    if(method === "POST") {
        if(url === "/registration/account") {
            if (response.ok === true) {
                let prefix = $('.account_prefix').text().trim();
                let login = prefix + data.login;
                let password = data.password;
                if ($('#password_hide').is(':checked')) {
                    password = " * * * * * *";
                }
                $("#player_account_list").append("<tr><td>" + login + "</td><td><i role='button' class='fe fe-settings btn-change-password' data-account='" + login + "' data-bs-toggle='modal' data-bs-effect='effect-slide-in-right' data-bs-target='#changepassword'></i> " + password + "</td><td><i class='bi bi-people ms-2 text-muted' ></i></td></tr>");
              }
        }
        if(url === "/player/account/change/password") {
            if (response.ok === true) {
                $("#player_account_list").find("tr").each(function() {
                    if($(this).find("td:nth-child(1)").text() === data.login){
                        $(this).find("td:nth-child(2)").text(data.password);
                    }
                });
              }
        }
        $('#changepassword').modal('hide');
    }
}

$.deparam = function(query) {
    var pairs, i, keyValuePair, key, value, map = {};
    // Remove leading question mark if it exists
    query = query.replace(/^\?/, '');
    // Split the query into key/value pairs
    pairs = query.split('&');
    for (i = 0; i < pairs.length; i++) {
        keyValuePair = pairs[i].split('=');
        key = decodeURIComponent(keyValuePair[0]);
        value = (keyValuePair.length > 1) ? decodeURIComponent(keyValuePair[1]) : undefined;
        map[key] = value;
    }
    return map;
};

$(document).on('submit', 'form', function (event) {
    event.preventDefault();
    let url = $(this).attr('action');
    let method = $(this).attr('method');

    let data = $(this).find('input, select, textarea').filter(function () {
        if (this.type === 'checkbox') {
            return this.checked ? $(this).val('true') : $(this).prop('checked', false).val('false');
        }
        if (this.type === 'radio') {
            return this.checked ? true : false;
        }
        return this.type !== 'checkbox' && this.type !== 'radio' || this.checked;
    }).serialize();

    let funcName = $(this).find('button[data-func]').attr('data-func');
    AjaxSend(url, method, data, false, 10, funcName);
});


function response(response, form){
    responseAnalysis(response, form)
}

/**
 * Анимирует изменение значения счетчика.
 * @param {number} targetValue - Новое значение счетчика.
 * @param {number} duration - Длительность анимации в миллисекундах (по умолчанию 500).
 */
function animateCounter(targetValue, duration = 1500) {
    const $counter = $(".count_sphere_coin");

    // Парсим текущее значение как число с плавающей точкой, игнорируя все нецифровые символы
    let currentValue = parseFloat($counter.text().replace(/[^\d.-]/g, '')) || 0;
    if (currentValue === targetValue) return;

    // Если целевое значение отрицательное, вычитаем это значение из текущего
    targetValue = currentValue + targetValue;


    // Если текущее значение уже равно целевому, не запускаем анимацию

    // Анимируем изменение от текущего значения к целевому
    $({ value: currentValue }).animate(
        { value: targetValue },
        {
            duration: duration,
            easing: "swing",
            step: function (now) {
                $counter.text(now.toFixed(1)); // Округляем до одного знака после запятой
            },
            complete: function () {
                $counter.text(targetValue.toFixed(1)); // В конце точно ставим целевое значение
            }
        }
    );
}

/**
 * Создает HTML для элемента склада с поддержкой разных структур данных
 *
 * @param {Object} warehouse - объект предмета склада
 * @param {boolean} canSplit - можно ли разбить предмет
 * @returns {string} - HTML строка для элемента списка
 */
function createWarehouseItemHtml(warehouse, canSplit) {
    // Проверки на существование данных и инициализация переменных с безопасными значениями
    if (!warehouse) return '';

    // Получаем информацию о предмете из разных возможных источников
    const itemInfo = warehouse.itemInfo || warehouse.item || {};
    const itemName = itemInfo.itemName || warehouse.name || 'Предмет';
    const itemIcon = itemInfo.icon || (warehouse.item && warehouse.item.getIcon ? warehouse.item.getIcon() : '');
    const count = warehouse.count || 1;
    const enchant = warehouse.enchant || 0;
    const objectId = warehouse.id || 0;

    // Функция для безопасного получения фразы
    const safePhrase = (phraseId) => {
        if (typeof phrase === 'function') {
            try {
                return phrase(phraseId);
            } catch (e) {
                return phraseId.toString();
            }
        }
        return phraseId.toString();
    };

    // Получаем фразу
    const phraseText = warehouse.phrase
        ? (typeof warehouse.phrase === 'string' ? warehouse.phrase : safePhrase(warehouse.phrase))
        : 'Предмет склада';

    // Создаем HTML
    return `
        <li class="list-group-item list-group-item-action p-1 border-bottom js-warehouse-item" data-item-count="${count}">
            <div class="d-flex align-items-center">
                <div class="form-check me-2">
                    <input data-object-id="${objectId}"
                        class="form-check-input warehouseInventory js-warehouse-item-checkbox"
                        type="checkbox" id="item_${objectId}" checked>
                </div>
                <div class="item-icon me-3">
                    <div class="position-relative">
                        <img src="${itemIcon}"
                            alt="${itemName}"
                            class="img-fluid border rounded p-1 js-item-icon"
                            style="width: 40px; height: 40px; object-fit: contain;">
                    </div>
                </div>
                <div class="item-details-n flex-grow-1">
                    <div class="fw-medium d-flex justify-content-between align-items-center">
                        <div>
                            ${enchant > 0 ? `<span class="badge bg-danger me-1">+${enchant}</span>` : ''}
                            <label class="mb-0 cursor-pointer js-item-name" for="item_${objectId}">
                                ${itemName}
                                ${count > 1 ? `<span class="bottom-0 end-0 badge rounded-pill bg-secondary js-item-count">x${count}</span>` : ''}
                            </label>
                        </div>
                        <div>
                            ${(count > 1 && canSplit) ? `<span role="button" class="badge bg-blue splitItem js-split-item-btn me-1">${safePhrase('unstack')}</span>` : ''}
                        </div>
                    </div>
                    <div class="small text-muted">${phraseText}</div>
                </div>
            </div>
        </li>
    `;
}


/**
 * Улучшенная функция для обновления списка предметов на складе
 * с учетом правил разбития предметов и обработки пустого склада
 *
 * @param {Object} response - ответ от сервера
 */
function updateWarehouseItemsList(response) {
    // Проверяем, что ответ содержит warehouse
    if (!response || !response.hasOwnProperty('warehouse')) {
        return;
    }

    const items = Array.isArray(response.warehouse) ? response.warehouse : [];
    const isAllowAllItemsSplitting = response.isAllowAllItemsSplitting || false;
    const splittableItems = response.splittableItems || [];

    // Находим контейнер, где должен быть список предметов
    let $cardBody = $('#warehouseModal .card-body').first();

    // Проверяем, пуст ли склад
    if (items.length === 0) {
        // Обновляем счетчик предметов на 0
        updateWarehouseCounter(0);

        // Показываем сообщение о пустом складе
        const emptyWarehouseHTML = `
            <div class="text-center p-4">
                <i class="ri-inbox-line text-muted display-4"></i>
                <p class="mt-2 text-muted">${safePhrase('no_items_send_char')}</p>
            </div>
        `;

        // Находим список предметов, если он существует
        let $itemsList = $('.js-warehouse-items-list');

        if ($itemsList.length > 0) {
            // Если список существует, заменяем его
            $itemsList.replaceWith(emptyWarehouseHTML);
        } else {
            // Если списка нет, заменяем все содержимое первого card-body
            $cardBody.html(emptyWarehouseHTML);
        }

        // Блокируем кнопку отправки
        $('#warehouseSendItemsToPlayer').prop('disabled', true);
        return;
    }

    // Обрабатываем случай, когда есть предметы
    let $itemsList = $('.js-warehouse-items-list');

    // Если список не существует, нужно создать его
    if ($itemsList.length === 0) {
        // Создаем структуру для списка предметов, заменяя существующее содержимое
        const listContainerHTML = `
            <ul class="list-group list-group-flush js-warehouse-items-list">
            </ul>
        `;
        $cardBody.html(listContainerHTML);
        $itemsList = $('.js-warehouse-items-list'); // Переопределяем переменную с новым элементом
    } else {
        // Если список существует, очищаем его
        $itemsList.empty();
    }

    // Генерируем HTML для каждого предмета
    items.forEach(function(item) {
        // Определяем, можно ли разбить предмет
        const canSplit = determineIfItemCanBeSplit(item, isAllowAllItemsSplitting, splittableItems);

        // Создаем HTML элемент предмета с учетом возможности разбития
        const itemHtml = createWarehouseItemHtml(item, canSplit);

        // Добавляем элемент в список
        $itemsList.append(itemHtml);
    });

    // Обновляем счетчик предметов
    updateWarehouseCounter(items.length);

    // Активируем кнопку отправки предметов
    $('#warehouseSendItemsToPlayer').prop('disabled', false);
}

/**
 * Обновляет счетчик предметов на складе
 * @param {number} count - количество предметов на складе
 */
function updateWarehouseCounter(count) {
    const $badgeElement = $('.js-warehouse-items-count');

    if (count > 0) {
        // Если есть предметы, обновляем все счетчики на странице
        $badgeElement.text(count);

        // Обновляем badge в заголовке модального окна, если он есть
        const $modalBadge = $('.js-warehouse-modal-count');
        if ($modalBadge.length > 0) {
            $modalBadge.text(count);
        } else if (count > 0) {
            $('#warehouseModalLabel').append(`<span class="badge rounded-pill bg-danger ms-2 js-warehouse-modal-count countWarehouseItems">${count}</span>`);
        }
        $(".countWarehouseItems").text(count);
    } else {
        // Если предметов нет, удаляем все счетчики
        $badgeElement.remove();
        $('.js-warehouse-modal-count').remove();
    }
}

/**
 * Показывает сообщение о пустом складе
 */
function showEmptyWarehouse() {
    const $itemsList = $('.js-warehouse-items-list');

    // Создаем HTML для пустого склада
    const emptyWarehouseHTML = `
    <div class="text-center p-4">
      <i class="ri-inbox-line text-muted display-4"></i>
      <p class="mt-2 text-muted">{{phrase('warehouse_is_empty')}}</p>
    </div>
  `;

    // Заменяем содержимое списка
    if ($itemsList.length) {
        $itemsList.replaceWith(emptyWarehouseHTML);
    } else {
        $('#warehouseModal .card-body').first().html(emptyWarehouseHTML);
    }

    // Блокируем кнопку отправки
    $('#warehouseSendItemsToPlayer').prop('disabled', true);
}

function responseAnalysis(response, form) {
    if (response && response.csrf_token && window.CSRF && typeof window.CSRF.updateToken === 'function') {
        window.CSRF.updateToken(response.csrf_token);
    }
    //Если существует переменная count_sphere_coin то обновляем счетчик class .count_sphere_coin
    let sphereCoin;
    if (response.sphereCoin !== undefined) {
        sphereCoin = $(".count_sphere_coin").text();
        animateCounter(response.sphereCoin-sphereCoin)
    }
    if (response.warehouse !== undefined) {
        updateWarehouseItemsList(response)
    }

    if (response.type === "notice") {
        ResponseNotice(response)
    } else if (response.type === "notice_registration") {
        ResponseNoticeRegistration(response)
    } else if (response.type === "notice_set_avatar") {
        ResponseNoticeSetAvatar(response)
    } else if (response.type === "bonus") {
        $(".bonus_code_img_src").attr('src', response.icon);
        $(".bonus_name_item").text(response.name);
        noticeSuccess(response.message);
    } else if (response.blockLoad) {


        if (response.title !== undefined) {
            document.title = response.title;
        }
        $.each(response.blocks, function (index, block) {
            let element = "";
            if (block.isID) {
                element = $("#" + block.name)
            } else {
                element = $("." + block.name)
            }
            if (block.action === "append") {
                element.append(block.html);
            } else if (block.action === "prepend") {
                element.prepend(block.html);
            } else if (block.action === "update") {
                element.empty();
                element.html(block.html);
            } else if (block.action === "remove") {
                element.remove();
            } else if (block.action === "replace") {
                element.replaceWith(block.html);
            }
        });

        $.each(response.changeVal, function (index, val) {
            let element = "";
            if (val.isID) {
                element = $("#" + val.name)
            } else {
                element = $("." + val.name)
            }
            element.val(val.value);
        });

        $.each(response.changeText, function (index, val) {
            let element = "";
            if (val.isID) {
                element = $("#" + val.name)
            } else {
                element = $("." + val.name)
            }
            element.text(val.value);
        });

        $.each(response.JSCode, function (index, code) {
            eval(code);
        });

        if (form !== undefined) {
            form.find(':input:not(:hidden)').val('');
        }


    }


}

function ResponseNotice(response) {
    if (response && response.csrf_token && window.CSRF && typeof window.CSRF.updateToken === 'function') {
        window.CSRF.updateToken(response.csrf_token);
    }
    if(response.type!=="notice"){
        return false;
    }
    if(response === ""){
        return false
    }

    if(response.ok){
        noticeSuccess(response.message)
    }else {
        noticeError(response.message)
    }

    if(response.reloadCaptcha){
        get_captcha()
    }
    let timeout = 1000;
    if (response.reloadIsNow === true) {
        timeout = response.reloadIsNow;
    }
    if (response.reload === true){
        setTimeout(function() {
            window.location.replace(window.location.pathname + '?cache_bust=' + Date.now());
        }, timeout);
    }
    if (response.redirect !== undefined) {
        setTimeout(function() {
            if (response.redirect === "refresh") {
                window.location.replace(window.location.pathname + '?cache_bust=' + Date.now());
            } else {
                window.location.href = response.redirect;
            }
        }, timeout);
    }

    return response.ok;
}


function noticeSuccess(message) {
    $("#successTitleMessageNotice").text("Success")
    $("#successContentMessageNotice").html(message)
    const successToast = ('#successToast')
    let toast = new bootstrap.Toast(successToast)
    toast.show()
}
function noticeError(message) {
    $("#dangerTitleMessageNotice").text("Error")
    $("#dangerContentMessageNotice").html(message)
    const dangerToast = ('#dangerToast')
    let toast = new bootstrap.Toast(dangerToast)
    toast.show()
}

function ResponseNoticeRegistration(response) {
    noticeSuccess(response.message)
    if (response.prefix) {
        $(".prefix-text").text(response.prefix);
    }
    if(response.isDownload){
        var blob = new Blob([response.content], { type: "text/plain" });
        var link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = response.title;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    if (response.redirect !== undefined){
        setTimeout(function() {
            window.location.href = response.redirect;
        }, 1000);
    }
}


$(document).on('click', '.setChangeServer', function(e) {
    e.preventDefault();
    const serverId = $(this).data('server-id');
    AjaxSend('/user/change/server', 'POST', {
        id: serverId
    }).then(function(response) {
        location.reload();
    }).catch(function(error) {
        console.error('Произошла ошибка:', error);
    });
});

// При изменении выбора в выпадающем списке
$('.select_default_server').on('change', function() {
    AjaxSend('/user/change/server', 'POST', {
        id: $(this).val()
    }).then(function (response) {
        location.reload();
    }).catch(function (error) {
        console.error('Произошла ошибка:', error);
    });
});

// Отправка коинов в игру, на персонажа
$(document).on('click', '#sendToPlayerBtn', function () {
    const $btn = $(this);
    const originalText = $btn.html();

    // Блокируем кнопку и добавляем анимацию загрузки
    $btn.prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Отправка...');

    let playerName = $('#send_player_name').val();
    let coin = $('#muchSphereCoin').val();
    let account = $('#send_player_name option:selected').data('account');

    AjaxSend('/send/to/player', 'POST', {
        player: playerName,
        coin: coin,
        account: account
    }, true).then(function (response) {
        responseAnalysis(response);
        if (response.ok) {
            $('#sendToPlayerModal').modal('hide');
        }
    }).catch(function (error) {
        // Обработка ошибок
        console.error('Ошибка при отправке:', error);
    }).finally(function () {
        // Восстанавливаем кнопку в любом случае
        $btn.prop('disabled', false).html(originalText);
    });
});

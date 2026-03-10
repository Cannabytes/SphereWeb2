/**
 * JavaScript для административной панели плагина FreeKassa
 */

// Глобальные переменные
let instanceModal = null;

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация модального окна
    const modalElement = document.getElementById('instanceModal');
    if (modalElement) {
        instanceModal = new bootstrap.Modal(modalElement);
    }

    // Событие при открытии модального окна (сброс формы)
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function() {
            resetInstanceForm();
        });
    }

    // Обработчик переключателя плагина
    const pluginToggle = document.getElementById('plugin_enabled');
    if (pluginToggle) {
        pluginToggle.addEventListener('change', function() {
            togglePluginEnabled(this.checked);
        });
    }

    const saveButton = document.getElementById('saveSettings');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            saveGlobalSettings();
        });
    }

});

/**
 * Открыть модальное окно для создания/редактирования магазина
 */
function openInstanceModal() {
    resetInstanceForm();
    document.getElementById('instanceModalLabel').textContent = 'Редактировать магазин';
    
    // Пытаемся загрузить существующий магазин
    loadShop();
}

/**
 * Загрузить существующий магазин (если есть)
 */
function loadShop() {
    showLoader();

    const formData = new FormData();

    sendRequest('/admin/plugin/freekassa/instance/get', formData, function(response) {
        hideLoader();
        
        if (response.ok || response.success) {
            const shop = response.instance;
            
            // Заполняем форму существующими данными
            document.getElementById('instance_name').value = shop.name || '';
            document.getElementById('instance_shop_id').value = shop.shop_id || '';
            document.getElementById('instance_api_key').value = shop.api_key || '';
            document.getElementById('instance_secret_word').value = shop.secret_word || '';
            document.getElementById('instance_secret_word_2').value = shop.secret_word_2 || '';
            document.getElementById('instance_description').value = shop.description || '';
        } else {
            // Магазина нет - для создания
            document.getElementById('instanceModalLabel').textContent = 'Добавить магазин';
        }
        
        if (instanceModal) {
            instanceModal.show();
        }
    });
}

/**
 * Редактировать магазин (используется как кнопка редактирования в таблице)
 */
function editInstance() {
    openInstanceModal();
}

/**
 * Сохранить магазин
 */
function saveInstance() {
    const form = document.getElementById('instanceForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    document.querySelectorAll('input[name="supported_countries[]"]:checked').forEach(function(checkbox) {
        formData.append('supported_countries[]', checkbox.value);
    });
    
    // Удалим id из formData так как он больше не нужен
    formData.delete('id');

    showLoader();

    sendRequest('/admin/plugin/freekassa/instance/create', formData, function(response) {
        hideLoader();
        
        if (response.ok || response.success) {
            showNotification('Успешно', response.message || 'Магазин успешно сохранен', 'success');
            
            if (instanceModal) {
                instanceModal.hide();
            }
            
            // Перезагружаем страницу через 1 секунду
            setTimeout(function() {
                location.reload();
            }, 1000);
        } else {
            showNotification('Ошибка', response.message || 'Не удалось сохранить магазин', 'error');
        }
    });
}

/**
 * Обновить валюты магазина
 */
function refreshCurrencies() {
    showLoader();
    
    const formData = new FormData();

    sendRequest('/admin/plugin/freekassa/instance/refresh_currencies', formData, function(response) {
        hideLoader();
        if (response.status === 'success') {
            showNotification('Успех', response.message || 'Валюты успешно обновлены', 'success');
        } else {
            showNotification('Ошибка', response.message || 'Не удалось обновить валюты', 'error');
        }
    });
}

/**
 * Удалить магазин
 */
function deleteInstance() {
    if (!confirm('Вы уверены, что хотите удалить этот магазин?')) {
        return;
    }

    showLoader();

    const formData = new FormData();

    sendRequest('/admin/plugin/freekassa/instance/delete', formData, function(response) {
        hideLoader();
        
        if (response.ok || response.success) {
            showNotification('Успешно', response.message || 'Магазин успешно удален', 'success');
            
            // Перезагружаем страницу через 1 секунду
            setTimeout(function() {
                location.reload();
            }, 1000);
        } else {
            showNotification('Ошибка', response.message || 'Не удалось удалить магазин', 'error');
        }
    });
}

/**
 * Включить/выключить плагин
 */
function togglePluginEnabled(isEnabled) {
    const pluginToggle = document.getElementById('plugin_enabled');
    const params = new URLSearchParams();
    params.append('enabled', isEnabled ? 'true' : 'false');
    params.append('save_context', 'toggle');

    showLoader();

    AjaxSend('/admin/plugin/freekassa/settings/save', 'POST', params.toString(), true)
    .then(function(response) {
        hideLoader();

        if (response && response.ok) {
            window.location.reload();
            return;
        }

        if (pluginToggle) {
            pluginToggle.checked = !isEnabled;
        }

        showNotification('Ошибка', response?.message || 'Ошибка при сохранении', 'error');
    })
    .catch(function(error) {
        if (pluginToggle) {
            pluginToggle.checked = !isEnabled;
        }

        console.error('Error:', error);
        hideLoader();
        showNotification('Ошибка', error?.message || 'Произошла сетевая ошибка', 'error');
    });
}

function saveGlobalSettings() {
    const saveButton = document.getElementById('saveSettings');
    if (saveButton && saveButton.dataset.submitting === 'true') {
        return;
    }

    const pluginToggle = document.getElementById('plugin_enabled');
    const params = new URLSearchParams();
    params.append('enabled', pluginToggle && pluginToggle.checked ? 'true' : 'false');
    params.append('save_context', 'global');

    const descriptionField = document.getElementById('plugin_description');
    params.append('PLUGIN_DESCRIPTION', descriptionField ? (descriptionField.value || '') : '');

    document.querySelectorAll('input[name="supported_countries[]"]:checked').forEach(function(checkbox) {
        params.append('supported_countries[]', checkbox.value);
    });

    if (saveButton) {
        saveButton.dataset.submitting = 'true';
        saveButton.disabled = true;
    }

    showLoader();

    AjaxSend('/admin/plugin/freekassa/settings/save', 'POST', params.toString(), true)
    .then(function(response) {
        hideLoader();

        if (saveButton) {
            saveButton.dataset.submitting = 'false';
            saveButton.disabled = false;
        }

        if (response && (response.ok || response.success || (response.type === 'notice' && response.ok))) {
            showNotification('Успешно', response.message || 'Настройки успешно сохранены', 'success');
            return;
        }

        showNotification('Ошибка', response?.message || 'Не удалось сохранить настройки', 'error');
    })
    .catch(function(error) {
        hideLoader();

        if (saveButton) {
            saveButton.dataset.submitting = 'false';
            saveButton.disabled = false;
        }

        console.error('Error:', error);
        showNotification('Ошибка', error?.message || 'Не удалось сохранить настройки', 'error');
    });
}

/**
 * Сбросить форму магазина
 */
function resetInstanceForm() {
    const form = document.getElementById('instanceForm');
    if (form) {
        form.reset();
        const idField = document.getElementById('instance_id');
        if (idField) idField.value = '0';
    }
}

/**
 * Отправить AJAX запрос
 */
function sendRequest(url, formData, callback) {
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (callback) {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideLoader();
        showNotification('Ошибка', 'Произошла ошибка при отправке запроса', 'error');
    });
}

/**
 * Показать лоадер
 */
function showLoader() {
    // Используем встроенную функцию если есть
    if (typeof spinner === 'function') {
        spinner(true);
    }
}

/**
 * Скрыть лоадер
 */
function hideLoader() {
    // Используем встроенную функцию если есть
    if (typeof spinner === 'function') {
        spinner(false);
    }
}

/**
 * Показать уведомление
 */
function showNotification(title, message, type = 'info', isHtml = false) {
    // Используем встроенную систему уведомлений если есть
    if (typeof showAlert === 'function') {
        showAlert(message, type);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            html: isHtml ? message : undefined,
            text: isHtml ? undefined : message,
            icon: type === 'success' ? 'success' : type === 'error' ? 'error' : 'info',
            confirmButtonText: 'OK'
        });
    } else {
        alert(title + '\n' + message);
    }
}

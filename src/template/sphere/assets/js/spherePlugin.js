const isActive = (pluginActive.toString().toLowerCase() === 'true' || pluginActive.toString() === '1');
if (!isActive) {
    $('.pluginSetting').prop('disabled', true);
}

// Включение/отключение плагина
$('#enablePlugin').on('change', function () {
    var setting = $(this).attr('id');
    var value = $(this).is(':checked');
    AjaxSend("/admin/plugin/save/activator", "POST", {
        "pluginName": pluginName,
        "setting": setting,
        "value": value,
        "serverId": 0,
    }, true).then(function () {
        window.location.reload();
    })
});

// Обработка изменения настроек плагина
$('.pluginSetting').on('change', function () {
    var setting = $(this).attr('id');
    var value = $(this).is(':checked');
    var dataType = $(this).data('type');
    AjaxSend("/admin/plugin/save/config", "POST", {
        "pluginName": pluginName,
        "setting": setting,
        "value": value,
        "type": dataType,
        "serverId": 0,
    }, false);
});
const isActive = (pluginActive.toString().toLowerCase() === 'true' || pluginActive.toString() === '1');
if (!isActive) {
    $('.pluginSetting').prop('disabled', true);
}
// Включение/отключение плагина
$('#enablePlugin').on('change', function () {
    var setting = $(this).attr('id');
    var value = $(this).is(':checked');
    var serverPluginID = typeof serverId !== 'undefined' ? serverId : 0;

    AjaxSend("/admin/plugin/save/activator", "POST", {
        "pluginName": pluginName,
        "setting": setting,
        "value": value,
        "serverId": serverPluginID,
    }, true).then(function () {
        window.location.reload();
    })
});

// Обработка изменения настроек плагина
$('.pluginSetting').on('change', function () {
    var setting = $(this).attr('id');
    var value = $(this).is(':checked');
    var dataType = $(this).data('type');
    var serverPluginID = typeof serverId !== 'undefined' ? serverId : 0;

    AjaxSend("/admin/plugin/save/config", "POST", {
        "pluginName": pluginName,
        "setting": setting,
        "value": value,
        "type": dataType,
        "serverId": serverPluginID,
    }, false);
});
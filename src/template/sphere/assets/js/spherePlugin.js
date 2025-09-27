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

// Обработка изменения настроек плагина (checkbox/select/text)
$('.pluginSetting').on('change', function () {
    var $el = $(this);
    var setting = $el.attr('id') || $el.attr('name');
    var dataType = $el.data('type') || 'string';
    var value;
    if ($el.is(':checkbox')) {
        value = $el.is(':checked');
    } else {
        value = $el.val();
    }
    var serverPluginID = typeof serverId !== 'undefined' ? serverId : 0;

    AjaxSend("/admin/plugin/save/config", "POST", {
        "pluginName": pluginName,
        "setting": setting,
        "value": value,
        "type": dataType,
        "serverId": serverPluginID,
    }, false);
});
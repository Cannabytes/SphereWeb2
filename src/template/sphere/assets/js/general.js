$('input[autocomplete="off"]').val('');
const tooltipTriggerList = document.querySelectorAll(
  '[data-bs-toggle="tooltip"]'
);
const tooltipList = [...tooltipTriggerList].map(
  (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
);

function basename(str) {
    var base = new String(str).substring(str.lastIndexOf('\\') + 1);
    if (base.lastIndexOf("\\") != -1)
        base = base.substring(0, base.lastIndexOf("\\"));
    return base;
}

function AjaxSend(url, method, data, isReturn = false, timeout = 2, funcName = null) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: url,
            type: method,
            data: data,
            timeout: timeout * 1000,
            dataType: 'json',
            success: function (response) {
                if (isReturn) {
                    resolve(response);
                } else {

                    if(response===null){
                        return;
                    }

                    // Проверка существования поля g-recaptcha
                    if (response.hasOwnProperty('g-recaptcha-response')) {
                        if (response.ok === false) {
                            grecaptcha.reset();
                        }
                    }

                    if (funcName) {
                        window[funcName](response);
                    }

                    responseAnalysis(response);
                    AjaxEvent(url, method, data, response);
                    resolve();
                }
            },
            error: function (xhr, status, error) {
                console.error('Ошибка при выполнении AJAX-запроса:', error);
                reject(error);
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

    if(method == "POST") {
        if(url == "/registration/account") {
            if (response.ok === true) {
                $("#player_account_list").append("<tr><td>" + data.login + "</td><td>" + data.password + "</td><td><i role='button' class='fe fe-settings btn-change-password' data-account='" + data.login + "' data-bs-toggle='modal' data-bs-effect='effect-slide-in-right' data-bs-target='#changepassword'></i></td></tr>");
              }
        }
        if(url == "/player/account/change/password") {
            if (response.ok === true) {
                //Нужно заменить пароль в списке
                $("#player_account_list").find("tr").each(function() {
                    if($(this).find("td:nth-child(1)").text() == data.login){
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
    AjaxSend(url, method, data, false, 2, funcName);
});




function responseAnalysis(response, form) {
    //Если существует переменная count_sphere_coin то обновляем счетчик class .count_sphere_coin
    if (response.sphereCoin !== undefined){
        $(".count_sphere_coin").text(response.sphereCoin)
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
    if(response.type!=="notice"){
        return false;
    }
    if(response === undefined || response === ""){
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


    if (response.reload === true){
        setTimeout(function() {
            window.location.reload();
        }, 1000);
    }

    if (response.redirect !== undefined) {
        setTimeout(function() {
            if (response.redirect === "refresh") {
                window.location.reload();
            } else {
                window.location.href = response.redirect;
            }
        }, 1000);
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
    let playerName = $('#send_player_name').val();
    let coin = $('#muchSphereCoin').val();
    let account = $('#send_player_name option:selected').data('account');
    AjaxSend('/send/to/player', 'POST', {
        player: playerName,
        coin: coin,
        account: account // Добавляем поле account
    }, true).then(function (response) {
        responseAnalysis(response);
        if (response.ok) {
            $('#sendToPlayerModal').modal('hide');
        }
    });
});

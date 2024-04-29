function AjaxSend(url, method, data, isReturn = false) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: url,
            type: method,
            data: data,
            dataType: 'json',
            success: function (response) {
                if (isReturn) {
                    resolve(response);
                } else {
                    responseAnalysis(response);
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

$(document).on('submit', 'form', function (event) {
    event.preventDefault();
    let url = $(this).attr('action');
    let method = $(this).attr('method');
    let data = $(this).serialize(); // Используйте serialize для сбора данных формы
    AjaxSend(url, method, data);
});


function responseAnalysis(response, form) {
    if (response.type === "notice") {
        ResponseNotice(response)
    } else if (response.type === "notice_registration") {
        ResponseNoticeRegistration(response)
    } else if (response.type === "notice_set_avatar") {
        ResponseNoticeSetAvatar(response)
    } else if (response.type === "ticket_comment_add") {
        ResponseTicketCommentAdd(response, form)
    } else if (response.type === "bonus") {
        $(".bonus_code_img_src").attr('src', response.icon);
        $(".bonus_name_item").text(response.name);
        noticeSuccess(response.message);
        updateInventory()
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

    if (response.redirect !== undefined){
        setTimeout(function() {
            window.location.href = response.redirect;
        }, 1000);
    }
    return response.ok;
}

function noticeSuccess(message) {
    $("#successTitleMessageNotice").text("Успешно")
    $("#successContentMessageNotice").text(message)
    const successToast = ('#successToast')
    let toast = new bootstrap.Toast(successToast)
    toast.show()
}
function noticeError(message) {
    $("#dangerTitleMessageNotice").text("Oops...")
    $("#dangerContentMessageNotice").text(message)
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
$('#select_default_server').on('change', function() {
    AjaxSend('/user/change/default/server', 'POST', {
        id: $(this).val()
    }).then(function (response) {
        location.reload();
    }).catch(function (error) {
        console.error('Произошла ошибка:', error);
    });
});

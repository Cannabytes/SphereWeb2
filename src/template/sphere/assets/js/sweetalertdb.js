

$(".connectionQualityCheck").on("click", function () {
    let type = $(this).attr("data-type");
    let serverId = $(this).attr("data-server-id");

    Swal.fire({
        title: 'Оценка качества соединения с Вашей БД',
        html: `
            <div id="loading-icon" style="display: flex; justify-content: center; align-items: center;">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `,
        showCloseButton: true,
        showConfirmButton: false,
        allowOutsideClick: true,  // Разрешаем закрытие при клике за пределами окна
        didOpen: () => {
            // Блокируем клавишу ESC для предотвращения закрытия по нажатию
            Swal.getPopup().addEventListener('keydown', (e) => {
                if (e.key === "Escape") {
                    e.stopPropagation();
                }
            });
        }
    });

    // Выполнение AJAX-запроса
    $.ajax({
        url: '/admin/server/db/quality',
        data: {
            type: type,
            id: serverId,
        },
        method: 'POST',
        success: (response) => {
            let data;
            try {
                data = typeof response === 'string' ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Ошибка парсинга JSON:", e);
                Swal.update({
                    html: `<p>Ошибка парсинга ответа сервера.</p>`,
                    showConfirmButton: true
                });
                return;
            }

            const formatTime = (ms) => Math.round(ms) + ' мсек';
            const formatUptime = (sec) => {
                const d = Math.floor(sec / 86400);
                const h = Math.floor((sec % 86400) / 3600);
                const m = Math.floor((sec % 3600) / 60);
                return `${d}д ${h}ч ${m}м`;
            };

            let responseHtml = `
            <p><strong>Качество соединения:</strong> ${data.evaluate || 'Нет данных'}</p>
            <p><strong>Время соединения:</strong> ${data.connectionTime !== undefined ? formatTime(data.connectionTime) : 'Нет данных'}</p>
            <p><strong>Время пинга:</strong> ${data.pingTime !== undefined ? formatTime(data.pingTime) : 'Нет данных'}</p>
            <p><strong>Время выполнения запроса:</strong> ${data.queryTime !== undefined ? formatTime(data.queryTime) : 'Нет данных'}</p>
            <hr>
            <p><strong>Платформа:</strong> ${data.platform || '—'}</p>
            <p><strong>Имя БД:</strong> ${data.name || '—'}</p>
            <p><strong>Тип БД:</strong> ${data.db_type || '—'}</p>
            <p><strong>Версия:</strong><br><pre style="white-space:pre-wrap;">${data.db_version || '—'}</pre></p>
            <p><strong>Хост:</strong> ${data.host || '—'}</p>
            <p><strong>Порт:</strong> ${data.port || '—'}</p>
            <hr>
            <p><strong>Сессий:</strong> ${data.stats?.sessions ?? '—'}</p>
            <p><strong>Аптайм:</strong> ${data.stats?.uptime_sec !== undefined ? formatUptime(data.stats.uptime_sec) : '—'}</p>
        `;

            Swal.update({
                html: responseHtml,
                showConfirmButton: true
            });
        },
        error: () => {
            Swal.update({
                html: `<p>Ошибка соединения с сервером.</p>`,
                showConfirmButton: true
            });
        }
    });

});




$(".portQualityCheck").on("click", function () {
    let type = $(this).attr("data-type");
    let serverId = $(this).attr("data-server-id");
    let name = "GameServer"
    if (type==="login"){
        name = "LoginServer"
    }

    // Показываем SweetAlert с анимацией загрузки до выполнения запроса
    Swal.fire({
        title: 'Оценка качества соединения с ' + name,
        html: `
            <div id="loading-icon" style="display: flex; justify-content: center; align-items: center;">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `,
        showCloseButton: true,
        showConfirmButton: false,
        allowOutsideClick: true,  // Разрешаем закрытие при клике за пределами окна
        didOpen: () => {
            // Блокируем клавишу ESC для предотвращения закрытия по нажатию
            Swal.getPopup().addEventListener('keydown', (e) => {
                if (e.key === "Escape") {
                    e.stopPropagation();
                }
            });
        }
    });

    // Выполнение AJAX-запроса
    $.ajax({
        url: '/admin/server/port/quality',
        data: {
            type: type,
            id: serverId,
        },
        method: 'POST',
        success: (response) => {
            let data;
            try {
                data = typeof response === 'string' ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Ошибка парсинга JSON:", e);
                Swal.update({
                    html: `<p>Ошибка парсинга ответа сервера.</p>`,
                    showConfirmButton: true
                });
                return;
            }

            // Формируем HTML-контент на основе данных JSON
            let responseHtml = `
                <p><strong>Качество соединения:</strong> ${data.evaluate || 'Нет данных'}</p>
                <p><strong>Время пинга:</strong> ${data.pingTime ? data.pingTime  + ' мсек' : 'Нет данных'}</p>
            `;

            Swal.update({
                html: responseHtml,
                showConfirmButton: true
            });
        },
        error: () => {
            Swal.update({
                html: `<p>Ошибка соединения с сервером.</p>`,
                showConfirmButton: true
            });
        }
    });
});



<?php

return [

    [
        'date' => '05:55 04.03.2025',
        'message' => [
            'ru' => 'Добавлена отдельная страница вывода ошибок синтаксиса шаблона.<br>
Обновил раздел логирования, автоматически подгружает новые логи, при прокрутке вниз подгружает старые логи. <br>
Добавлен греческая локализация.<br>
В плагине редактирования предметов, писалось что нужно загрузить изображение предмета в формате .webp, это не актуальная была информация и устарела, вводила людей в заблуждение, там любого формата изображение можно было загружать, код автоматически сконвертирует в webp.<br>
Оказалось что я упустил в редакторе предметов возможность удаления кастомных предметов, реализовал удаление кастомных предметов.<br>
Страница пополнения баланса переделана.<br>
Доработан бонус-коды, теперь можно генерировать без множества кодов.<br>
При инсталляции были добавлены страницы с переводом на испанский, португальский, греческий язык.<br>
Устранено "тормозило", при котором при использовании сканера файлов могло притормаживать, оказалось дебаг забыл убрать ранее когда анализировал работу.<br>
При удалении сервера, удаляется из БД все ранее записи , что имели отношения к этому серверу.<br>',

            'en' => 'A separate page has been added to display template syntax errors.<br>
The logging section has been updated to automatically load new logs, and when scrolling down, to load older logs.<br>
Greek localization has been added.<br>
In the item editing plugin, it was stated that you need to upload the item image in .webp format, but that was outdated and misleading information, as you can upload images of any format. The code automatically converts them to webp.<br>
It turned out that I had missed the ability to delete custom items in the item editor, so I implemented custom item deletion.<br>
The balance top-up page has been redesigned.<br>
Bonus codes have been improved, now they can be generated without creating multiple codes.<br>
During installation, pages with Spanish, Portuguese, and Greek translations were added.<br>
A slowdown was fixed that occurred when using the file scanner; it turned out I had forgotten to remove the debug option used previously.<br>
When deleting a server, all related records in the database are also removed.<br>',

            'pt' => 'Foi adicionada uma página separada para exibir erros de sintaxe de modelos.<br>
A seção de registros foi atualizada para carregar automaticamente novos logs e, ao rolar para baixo, carregar os antigos.<br>
Adicionada a localização em grego.<br>
No plugin de edição de itens, constava que era necessário fazer upload da imagem do item em formato .webp, mas essa informação estava desatualizada e enganosa, pois é possível enviar imagens de qualquer formato. O código converte automaticamente para webp.<br>
Descobriu-se que faltava a capacidade de excluir itens personalizados no editor de itens, então implementei a exclusão desses itens.<br>
A página de recarga de saldo foi reformulada.<br>
Os códigos de bônus foram aprimorados para que possam ser gerados sem criar vários códigos.<br>
Durante a instalação, foram adicionadas páginas com traduções para espanhol, português e grego.<br>
Foi corrigida uma lentidão que ocorria ao usar o scanner de arquivos; constatei que havia esquecido de remover o modo de depuração que estava ativo.<br>
Ao excluir um servidor, todos os registros anteriores relacionados a esse servidor são removidos do banco de dados.<br>',

            'es' => 'Se agregó una página independiente para mostrar errores de sintaxis de plantillas.<br>
Se actualizó la sección de registro para cargar automáticamente nuevos registros y, al desplazarse hacia abajo, cargar los registros antiguos.<br>
Se añadió la localización griega.<br>
En el plugin de edición de artículos, se indicaba que era necesario subir la imagen en formato .webp, pero esa información estaba desactualizada y resultaba engañosa, ya que se puede subir una imagen de cualquier formato. El código la convierte automáticamente a webp.<br>
Descubrí que faltaba la capacidad de eliminar artículos personalizados en el editor de artículos, así que implementé esta función.<br>
Se rediseñó la página de recarga de saldo.<br>
Se mejoraron los códigos de bonificación, ahora es posible generarlos sin tener que crear muchos códigos.<br>
Durante la instalación, se agregaron páginas con traducciones al español, portugués y griego.<br>
Se corrigió una ralentización que ocurría al usar el escáner de archivos; resultó que había dejado activa la depuración de forma involuntaria.<br>
Al eliminar un servidor, se borran de la base de datos todos los registros relacionados con ese servidor.<br>',

            'gr' => 'Προστέθηκε ξεχωριστή σελίδα για την εμφάνιση σφαλμάτων σύνταξης προτύπου.<br>
Ενημερώθηκε η ενότητα καταγραφής, ώστε να φορτώνει αυτόματα νέα αρχεία καταγραφής και, κατά την κύλιση προς τα κάτω, να φορτώνει παλαιότερα.<br>
Προστέθηκε ελληνική τοπικοποίηση.<br>
Στο πρόσθετο επεξεργασίας αντικειμένων, αναφερόταν ότι έπρεπε να ανεβάσετε την εικόνα του αντικειμένου σε μορφή .webp, αλλά αυτό ήταν ξεπερασμένο και παραπλανητικό, καθώς μπορείτε να ανεβάσετε εικόνες οποιασδήποτε μορφής. Ο κώδικας τις μετατρέπει αυτόματα σε webp.<br>
Ανακάλυψα ότι δεν υπήρχε η δυνατότητα διαγραφής προσαρμοσμένων αντικειμένων στον επεξεργαστή αντικειμένων, οπότε υλοποίησα αυτή τη λειτουργία.<br>
Η σελίδα ανανέωσης υπολοίπου ανασχεδιάστηκε.<br>
Βελτιώθηκαν οι κωδικοί μπόνους, ώστε τώρα να μπορούν να δημιουργηθούν χωρίς τη δημιουργία πολλών κωδικών.<br>
Κατά την εγκατάσταση, προστέθηκαν σελίδες με μεταφράσεις στα Ισπανικά, Πορτογαλικά και Ελληνικά.<br>
Διορθώθηκε μια επιβράδυνση κατά τη χρήση του σαρωτή αρχείων· αποδείχθηκε ότι είχα ξεχάσει να απενεργοποιήσω τον εντοπισμό σφαλμάτων που είχα ενεργοποιήσει προηγουμένως.<br>
Όταν διαγράφεται ένας διακομιστής, διαγράφονται από τη βάση δεδομένων όλα τα σχετικά παλαιότερα αρχεία.<br>',
        ],
    ],

    [
        'date' => '05:37 26.02.2025',
        'message' => [
            'ru' => 'Добавлен плагин Розыгрышей (для проф. пользователей)<br>
                     Добавлен португальский (Бразилия) язык.<br>
                     Добавлен испанский язык.<br>
                     Добавлено красивое окно ошибки.',
            'en' => 'Added Prank plugin (for professional users)<br>
                     Added Portuguese (Brazil) language.<br>
                     Added Spanish language.<br>
                     Added a nice error window.',
            'es' => 'Se agregó el plugin de Bromas (para usuarios profesionales)<br>
                     Se agregó el idioma portugués (Brasil).<br>
                     Se agregó el idioma español.<br>
                     Se agregó una ventana de error bonita.',
            'pt' => 'Adicionado o plugin de Pegadinhas (para usuários profissionais)<br>
                        Adicionado o idioma português (Brasil).<br>
                        Adicionado o idioma espanhol.<br>
                        Adicionada uma janela de erro bonita.',
            'gr' => 'Προστέθηκε πρόσθετο Κληρώσεων (για επαγγελματίες χρήστες)<br> Προστέθηκε η πορτογαλική (Βραζιλία) γλώσσα.<br> Προστέθηκε η ισπανική γλώσσα.<br> Προστέθηκε ένα όμορφο παράθυρο σφάλματος.',
        ]
    ],
    [
        'date' => '14:48 16.02.2025',
        'message' => [
            'ru' => 'Обновлено API платежной системы Freekassa',
            'en' => 'Freekassa payment system API updated',
            'es' => 'Actualizada la API del sistema de pago Freekassa',
            'pt' => 'API do sistema de pagamento Freekassa atualizada',
            'gr' => 'Ενημερώθηκε το API του συστήματος πληρωμών Freekassa.',
        ]
    ],
    [
        'date' => '12:46 14.02.2025',
        'message' => [
            'ru' => 'Добавлены новые проверочные IP для платежной системы pally',
            'en' => 'Added new verification IPs for Pally payment system',
            'es' => 'Se agregaron nuevas IPs de verificación para el sistema de pago Pally',
            'pt' => 'Adicionados novos IPs de verificação para o sistema de pagamento Pally',
            'gr' => 'Προστέθηκαν νέες διευθύνσεις IP επαλήθευσης για το σύστημα πληρωμών pally.',
        ]
    ],
    [
        'date' => '01:45 12.02.2025',
        'message' => [
            'ru' => 'Добавлена возможность создавать кастомные страницы, которые имеют приоритет загрузки. Добавление суффикса custom_(название файла).html',
            'en' => 'Added the ability to create custom pages that have loading priority. Adding the suffix custom_(file name).html',
            'es' => 'Se agregó la posibilidad de crear páginas personalizadas que tienen prioridad de carga. Agregando el sufijo custom_(nombre del archivo).html',
            'pt' => 'Adicionada a capacidade de criar páginas personalizadas com prioridade de carregamento. Adicionando o sufixo custom_(nome do arquivo).html',
            'gr' => 'Προστέθηκε η δυνατότητα δημιουργίας προσαρμοσμένων σελίδων που έχουν προτεραιότητα φόρτωσης. Προσθέστε το επίθημα custom_(όνομα_αρχείου).html.',
        ]
    ],
    [
        'date' => '23:22 11.02.2025',
        'message' => [
            'ru' => 'Добавлен новый плагин форума, можно создавать полноценный форум в личном кабинете.',
            'en' => 'A new forum plugin has been added, you can create a full-fledged forum in your personal account.',
            'es' => 'Se ha añadido un nuevo plugin de foro, puedes crear un foro completo en tu cuenta personal.',
            'pt' => 'Foi adicionado um novo plugin de fórum, você pode criar um fórum completo na sua conta pessoal.',
            'gr' => 'Προστέθηκε νέο πρόσθετο φόρουμ, επιτρέποντας τη δημιουργία ενός πλήρους φόρουμ στον προσωπικό λογαριασμό.',
        ]
    ],
    [
        'date' => '22:10 11.02.2025',
        'message' => [
            'ru' => 'Добавлен новый плагин для редактирования стандартных запросов к серверу.',
            'en' => 'Added a new plugin for editing standard server requests',
            'es' => 'Se añadió un nuevo plugin para editar solicitudes estándar al servidor',
            'pt' => 'Adicionado um novo plugin para edição de solicitações padrão ao servidor',
            'gr' => 'Προστέθηκε νέο πρόσθετο για την επεξεργασία των стандартικών αιτημάτων προς τον διακομιστή.',
        ]
    ],
    [
        'date' => '23:19 15.12.2024',
        'message' => [
            'ru' => 'В админ-панели вывел данные для просмотра источников трафика на сайт. Там можно узнать кол-во переходов к Вам на сайт (из какого сайта), кол-во тех юзеров сколько зарегалось и сколько их них задонатили.',
            'en' => 'In the admin panel, I displayed data for viewing traffic sources to the site. There you can find out the number of transitions to your site (from which site), the number of users who registered and how many of them donated.',
            'es' => 'En el panel de administración, mostré datos para ver las fuentes de tráfico al sitio. Ahí puedes saber el número de transiciones a tu sitio (desde qué sitio), el número de usuarios que se registraron y cuántos de ellos donaron.',
            'pt' => 'No painel de administração, exibi dados para visualização das fontes de tráfego do site. Lá você pode descobrir o número de transições para o seu site (de qual site), o número de usuários que se registraram e quantos deles doaram.',
            'gr' => 'Στο πίνακα διαχείρισης εμφανίζονται δεδομένα για την παρακολούθηση των πηγών κίνησης προς τον ιστότοπό σας. Εκεί μπορείτε να δείτε τον αριθμό των επισκέψεων στον ιστότοπό σας (από ποιον ιστότοπο προήλθαν), πόσοι χρήστες εγγράφηκαν και πόσοι από αυτούς έκαναν δωρεές.',
        ]
    ],
    [
        'date' => '17:44 15.12.2024',
        'message' => [
            'ru' => 'Теперь можно добавлять помимо стримов YouTube (ссылка на видео), Twitch (ссылка на канал), теперь добавлять можно и Kick.com (ссылкой на канал).',
            'en' => 'Now you can add, in addition to YouTube streams (link to video), Twitch (link to channel), now you can also add Kick.com (link to channel).',
            'es' => 'Ahora puedes agregar, además de transmisiones de YouTube (enlace al video), Twitch (enlace al canal), también Kick.com (enlace al canal).',
            'pt' => 'Agora você pode adicionar, além de transmissões do YouTube (link para o vídeo), Twitch (link para o canal), também o Kick.com (link para o canal).',
            'gr' => 'Τώρα μπορείτε να προσθέτετε εκτός από τις ροές YouTube (σύνδεσμος βίντεο) και Twitch (σύνδεσμος καναλιού), και το Kick.com (σύνδεσμος καναλιού).',
        ]
    ],
    [
        'date' => '14:26 13.12.2024',
        'message' => [
            'ru' => 'Добавлена возможность получать уведомления событий (донат, бонусы, перевод в игру $) в телеграмм бот.',
            'en' => 'Added the ability to receive event notifications (donations, bonuses, transfer to the game $) in the telegram bot',
            'es' => 'Se agregó la posibilidad de recibir notificaciones de eventos (donaciones, bonos, transferencia al juego $) en el bot de Telegram',
            'pt' => 'Adicionada a possibilidade de receber notificações de eventos (doações, bônus, transferência para o jogo $) no bot do Telegram',
            'gr' => 'Προστέθηκε η δυνατότητα λήψης ειδοποιήσεων για γεγονότα (δωρεές, μπόνους, μεταφορές χρημάτων στο παιχνίδι $) στο Telegram bot.',

        ]
    ],
    [
        'date' => '11:27 08.12.2024',
        'message' => [
            'ru' => 'Удален тикет и был заменен на Техническую Поддержку.',
            'en' => 'The ticket has been removed and replaced with Technical Support.',
            'es' => 'El ticket ha sido eliminado y reemplazado por Soporte Técnico.',
            'pt' => 'O ticket foi removido e substituído por Suporte Técnico.',
            'gr' => 'Αφαιρέθηκε το ticket και αντικαταστάθηκε με την Τεχνική Υποστήριξη.',

        ]
    ],
    [
        'date' => '06:53 02.12.2024',
        'message' => [
            'ru' => 'У некоторые браузеры блокируют открытие новой страницы при переходе для оплаты. Теперь будет добавляться ссылка.',
            'en' => 'Some browsers block opening a new page when going to pay. Now a link will be added.',
            'es' => 'Algunos navegadores bloquean la apertura de una nueva página al ir a pagar. Ahora se añadirá un enlace.',
            'pt' => 'Alguns navegadores bloqueiam a abertura de uma nova página ao ir para o pagamento. Agora será adicionado um link.',
            'gr' => 'Ορισμένοι φυλλομετρητές μπλοκάρουν το άνοιγμα νέας σελίδας κατά τη μετάβαση για πληρωμή. Τώρα θα προστίθεται σύνδεσμος.',

        ]
    ],
    [
        'date' => '07:17 27.11.2024',
        'message' => [
            'ru' => 'Обновлены языковые пакеты',
            'en' => 'Language packs updated',
            'es' => 'Paquetes de idiomas actualizados',
            'pt' => 'Pacotes de idiomas atualizados',
            'gr' => 'Ενημερώθηκαν τα πακέτα γλωσσών.',

        ]
    ],
    [
        'date' => '02:06 26.11.2024',
        'message' => [
            'ru' => 'Фикс бага с кэшем, иногда могло показывало что якобы нет игровых персонажей. И уменьшено время обновления актуальных персонажей на аккаунте до 10 сек.',
            'en' => 'Fixed a bug with the cache, sometimes it could show that there were supposedly no game characters. And the update time for current characters on the account was reduced to 10 seconds.',
            'es' => 'Se corrigió un error con el caché, a veces podía mostrar que supuestamente no había personajes de juego. Y se redujo el tiempo de actualización de los personajes actuales en la cuenta a 10 segundos.',
            'pt' => 'Corrigido um bug com o cache, às vezes podia mostrar que supostamente não havia personagens no jogo. E o tempo de atualização dos personagens atuais na conta foi reduzido para 10 segundos.',
            'gr' => 'Διορθώθηκε το σφάλμα με την προσωρινή μνήμη, το οποίο μερικές φορές εμφάνιζε ότι δεν υπήρχαν χαρακτήρες παιχνιδιού. Επίσης, μειώθηκε ο χρόνος ενημέρωσης των ενεργών χαρακτήρων στον λογαριασμό σε 10 δευτερόλεπτα.',

            ]
    ],
    [
        'date' => '22:27 25.11.2024',
        'message' => [
            'ru' => 'Изменение реферальной системы и вынесение к настройкам сервера. Необходимо будет перенастроить и сохранить реферальную систему.',
            'en' => 'Changing the referral system and moving it to server settings. It will be necessary to reconfigure and save the referral system.',
            'es' => 'Cambio en el sistema de referidos y traslado a la configuración del servidor. Será necesario reconfigurar y guardar el sistema de referidos.',
            'pt' => 'Mudança no sistema de referência e transferência para as configurações do servidor. Será necessário reconfigurar e salvar o sistema de referência.',
            'gr' => 'Αλλαγή στο σύστημα παραπομπών και μεταφορά του στις ρυθμίσεις του διακομιστή. Θα χρειαστεί να αναδιαρθρώσετε και να αποθηκεύσετε το σύστημα παραπομπών.',
        ]
    ],
];
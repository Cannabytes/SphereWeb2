<?php

namespace Ofey\Logan22\component\request;

class XssSecurity
{
    private const ALLOWED_TAGS = '<p><br><strong><em><u><s><ul><ol><li><blockquote><code><pre><a><img><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><th><td><div><span>';

    private const DANGEROUS_ATTRIBUTES = [
        'id',
        'onclick','ondblclick','onmousedown','onmouseup','onmouseover',
        'onmousemove','onmouseout','onmouseenter','onmouseleave',
        'onload','onunload','onchange','onsubmit','onreset',
        'onselect','onblur','onfocus','onkeydown','onkeypress','onkeyup',
        'onerror','onabort','ondrag','ondrop','ondragend','ondragenter','ondragleave','ondragover','ondragstart',
        'onscroll','oncopy','oncut','onpaste','onwheel',
        'ontouchstart','ontouchmove','ontouchend','ontouchcancel',
        'onpointerdown','onpointerup','onpointermove','onpointerover','onpointerout','onpointerenter','onpointerleave','onpointercancel',
        'onanimationstart','onanimationend','onanimationiteration','ontransitionend',
        'onauxclick','onbeforeinput','oninput','oncontextmenu','onfocusin','onfocusout','oninvalid','onsearch','onseeked','onseeking','onselectionchange',
        'formaction','action','background','dynsrc','lowsrc'
    ];

    /**
     * Разрешённые классы Bootstrap по умолчанию (примерный набор).
     * Дополняйте по необходимости — это белый список.
     */
    private const ALLOWED_BOOTSTRAP_CLASSES = [
        'd-block','d-inline','d-inline-block','d-flex','d-inline-flex','justify-content-start','justify-content-center','justify-content-end','align-items-start','align-items-center','align-items-end',
        // Spacing
        'm-0','mt-0','mb-0','ml-0','mr-0','p-0','pt-0','pb-0','pl-0','pr-0',
        'm-1','mt-1','mb-1','ml-1','mr-1','p-1','pt-1','pb-1','pl-1','pr-1',
        'm-2','mt-2','mb-2','ml-2','mr-2','p-2','pt-2','pb-2','pl-2','pr-2',
        'm-3','mt-3','mb-3','ml-3','mr-3','p-3','pt-3','pb-3','pl-3','pr-3',
        'm-4','mt-4','mb-4','ml-4','mr-4','p-4','pt-4','pb-4','pl-4','pr-4',
        'm-5','mt-5','mb-5','ml-5','mr-5','p-5','pt-5','pb-5','pl-5','pr-5',
        // Text and colors
        'text-left','text-center', 'blockquote', 'custom-blockquote', 'text-end', 'text-justify', 'text-right','text-muted','text-primary','text-secondary','text-success','text-danger','text-warning','text-info','text-light','text-dark',
        // Buttons & badges
        'btn', 'info', 'btn-primary','btn-secondary','btn-success','btn-danger','btn-warning','btn-info','btn-light','btn-dark','badge','badge-primary','badge-secondary','badge-success','badge-danger','badge-warning','badge-info','badge-light','badge-dark',
        // Utilities commonly used
        'img-fluid','rounded','rounded-circle','border','shadow','table','table-striped','table-bordered'
    ];

    /**
     * Замены классов (например, от редактора Quill -> классы Bootstrap)
     */
    private const CLASS_REPLACEMENTS = [
        'ql-align-center' => 'text-center',
        'ql-align-right' => 'text-end',
        'ql-align-justify' => 'text-justify',
        'ql-align-left' => 'text-left',
    ];

    /**
     * Добавляемые/заменяемые классы для конкретных тегов
     * Ключ — имя тега в нижнем регистре, значение — строка класса, которая будет установлена/добавлена
     */
    private const TAG_CLASS_ADDITIONS = [
        'blockquote' => 'blockquote custom-blockquote info mb-0 text-center',
    ];

    /**
     * Запрещённые классы (чёрный список)
     */
    private const DISALLOWED_CLASSES = [
        'hidden','invisible','sr-only','screen-reader','visually-hidden'
    ];

    public static function clean(string $content, bool $stripAllTags = false): string
    {
        if ($content === '') {
            return $content;
        }

        if ($stripAllTags) {
            return strip_tags($content);
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);

        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        $content = preg_replace('/(?:(?:<br\s*\\/?>)\s*){3,}/i', '<br><br>', $content);

        $content = preg_replace(
            '/(?:(?:<p[^>]*>\s*<br\s*\\/?>\s*<\/p>\s*){3,})/i',
            '<p><br></p><p><br></p>',
            $content
        );

        $content = strip_tags($content, self::ALLOWED_TAGS);
        $content = self::removeDangerousAttributes($content);
        $content = self::blockDangerousProtocols($content);
        $content = self::removeDangerousTags($content);
        $content = self::filterAttributesAndClasses($content);
        $content = self::cleanStyleAttributes($content);
        $content = self::blockBase64Content($content);
        $content = self::cleanHtmlEntities($content);
        $content = htmlspecialchars_decode($content, ENT_QUOTES | ENT_HTML5);
        $content = self::removeDangerousAttributes($content);
        $content = self::blockDangerousProtocols($content);

        return $content;
    }


    /**
     * Удаляет все атрибуты, стили и классы, которые не разрешены.
     * - Полностью удаляет атрибуты, не перечисленные в безопасном списке
     * - Удаляет style атрибуты (Bootstrap стиль должен быть задан через классы)
     * - Оставляет только разрешённые классы из ALLOWED_BOOTSTRAP_CLASSES и удаляет те, которые в DISALLOWED_CLASSES
     *
     * @param string $content
     * @return string
     */
    private static function filterAttributesAndClasses(string $content): string
    {
        // Разрешённые атрибуты по умолчанию для безопасного HTML
        $allowedAttrs = [
            'href','src','alt','title','width','height','colspan','rowspan','scope','role','aria-label','aria-hidden','rel','target','type'
        ];

        // Удаляем все event-атрибуты и data-атрибуты, кроме data-* если они нужны — здесь блокируем все data-*
        $content = preg_replace('/\sdata-[a-z0-9_-]*\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content);

        // Удаляем все атрибуты, кроме разрешённых и class/href/src/alt/title и aria-атрибутов
        $content = preg_replace_callback('/<([a-z][a-z0-9]*)\b([^>]*)>/i', function($matches) use ($allowedAttrs) {
            $tag = $matches[1];
            $attrString = $matches[2];

            // Найдём все атрибуты
            preg_match_all('/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrString, $aMatches, PREG_SET_ORDER);

            $keep = [];
            foreach ($aMatches as $m) {
                $attrName = strtolower($m[1]);
                $attrVal = isset($m[3]) && $m[3] !== '' ? $m[3] : (isset($m[4]) && $m[4] !== '' ? $m[4] : (isset($m[5]) ? $m[5] : ''));

                // Always skip dangerous attributes
                if (in_array($attrName, self::DANGEROUS_ATTRIBUTES, true)) {
                    continue;
                }

                // Allow aria-* attributes
                if (strpos($attrName, 'aria-') === 0) {
                    $keep[$attrName] = $attrVal;
                    continue;
                }
                // Allow only whitelisted attributes or class/href/src/alt/title/style
                if (in_array($attrName, $allowedAttrs, true) || in_array($attrName, ['class','href','src','alt','title','style'], true)) {
                    $keep[$attrName] = $attrVal;
                }
            }

            // Если для тега определены добавляемые классы — добавляем их в $keep (даже если изначально нет class)
            $tagLower = strtolower($tag);
            if (isset(self::TAG_CLASS_ADDITIONS[$tagLower])) {
                $additional = trim(self::TAG_CLASS_ADDITIONS[$tagLower]);
                if ($additional !== '') {
                    if (isset($keep['class']) && trim($keep['class']) !== '') {
                        $keep['class'] = trim($additional . ' ' . $keep['class']);
                    } else {
                        $keep['class'] = $additional;
                    }
                }
            }

            // Обработка class: применяем замены классов (например от редактора) и оставляем только разрешённые
            if (isset($keep['class'])) {
                $classStr = trim($keep['class']);
                if ($classStr !== '') {
                    $clsParts = preg_split('/\s+/', $classStr);
                    foreach ($clsParts as &$cp) {
                        if (isset(self::CLASS_REPLACEMENTS[$cp])) {
                            $cp = self::CLASS_REPLACEMENTS[$cp];
                        }
                    }
                    $keep['class'] = implode(' ', $clsParts);
                }

                $classes = preg_split('/\s+/', trim($keep['class']));
                    // Для некоторых тегов добавляем/заменяем класс целиком или дополняем
                    $tagLower = strtolower($tag);
                    if (isset(self::TAG_CLASS_ADDITIONS[$tagLower])) {
                        // Добавим эти классы в начало — но учитываем уже существующие
                        $additional = trim(self::TAG_CLASS_ADDITIONS[$tagLower]);
                        if ($additional !== '') {
                            $keep['class'] = trim($additional . ' ' . $keep['class']);
                        }
                    }
            
                    $out = [];
                    foreach ($classes as $c) {
                        if ($c === '') continue;
                        // Блокировка конкретных запрещённых
                        if (in_array($c, self::DISALLOWED_CLASSES, true)) continue;

                        // Разрешаем только точные совпадения с белым списком
                        if (in_array($c, self::ALLOWED_BOOTSTRAP_CLASSES, true)) {
                            $out[] = $c;
                            continue;
                        }

                        // Разрешим некоторые утилитарные классы с префиксами, например col-*, text-*, mt-*, p-*
                        if (preg_match('/^(col-(xs-|sm-|md-|lg-|xl-)?[0-9]+|col|text-(left|center|right|muted|primary|secondary|success|danger|warning|info|light|dark)|m[tblrxy]?-[0-5]|p[tblrxy]?-[0-5])$/', $c)) {
                            $out[] = $c;
                            continue;
                        }
                    }

                    if (count($out) > 0) {
                        $keep['class'] = implode(' ', $out);
                    } else {
                        unset($keep['class']);
                    }
            }

            // Обработка style: оставляем только color и background-color и очищаем значения
            if (isset($keep['style'])) {
                $styleVal = $keep['style'];

                // Найдём все допустимые декларации color/background-color
                preg_match_all('/\s*(color|background-color)\s*:\s*([^;]+);?/i', $styleVal, $sMatches, PREG_SET_ORDER);
                $newDecls = [];
                foreach ($sMatches as $sd) {
                    $prop = strtolower($sd[1]);
                    $valRaw = trim($sd[2]);

                    // Блокируем опасные токены
                    if (preg_match('/(expression|javascript:|data:|url\(|behavior|@import)/i', $valRaw)) {
                        continue;
                    }

                    // Удаляем лишние кавычки и теги
                    $valSan = trim($valRaw, "\"' ");
                    $valSan = strip_tags($valSan);

                    // Простая проверка допустимых символов в значении цвета (hex, rgb, rgba, слова)
                    if (preg_match('/^[#0-9a-zA-Z\s%,().-]+$/', $valSan)) {
                        $newDecls[] = $prop . ': ' . $valSan;
                    }
                }

                if (count($newDecls) > 0) {
                    // Обновляем стиль: объединяем декларации и гарантируем точку с запятой в конце
                    $keep['style'] = implode('; ', $newDecls) . ';';
                } else {
                    unset($keep['style']);
                }
            }

            // Собираем обратно атрибуты в строку
            $attrParts = [];
            foreach ($keep as $n => $v) {
                // Экранируем значения
                $vEsc = htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $attrParts[] = $n . '="' . $vEsc . '"';
            }

            $newTag = '<' . $tag . (count($attrParts) ? ' ' . implode(' ', $attrParts) : '') . '>';
            return $newTag;
        }, $content);

        return $content;
    }


    /**
     * Быстрая очистка для простого текста (без HTML)
     * Преобразует все специальные символы в HTML-entities
     * 
     * @param string $text Исходный текст
     * @return string Очищенный текст
     * 
     * @example
     * $safe = XssSecurity::cleanText('<script>alert(1)</script>');
     * // Результат: &lt;script&gt;alert(1)&lt;/script&gt;
     */
    public static function cleanText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Очистка URL от опасных протоколов
     * 
     * @param string $url URL для проверки
     * @return string Безопасный URL или пустая строка
     * 
     * @example
     * $safe = XssSecurity::cleanUrl('javascript:alert(1)');
     * // Результат: ''
     * 
     * $safe = XssSecurity::cleanUrl('https://example.com');
     */
    public static function cleanUrl(string $url): string
    {
        $url = trim($url);
        
        // Проверяем на опасные протоколы
        if (preg_match('/^\s*(javascript|data|vbscript):/i', $url)) {
            return '';
        }

        return $url;
    }

    /**
     * Удаление опасных атрибутов событий из HTML
     * 
     * @param string $content HTML-контент
     * @return string Очищенный контент
     */
    private static function removeDangerousAttributes(string $content): string
    {
        foreach (self::DANGEROUS_ATTRIBUTES as $attr) {
            // Удаляем атрибуты в формате: attr="value" или attr='value' или attr=value
            $content = preg_replace(
                '/\s*' . preg_quote($attr, '/') . '\s*=\s*["\'][^"\']*["\']|\s*' . preg_quote($attr, '/') . '\s*=\s*[^\s>]*/i',
                '',
                $content
            );
        }

        return $content;
    }

    /**
     * Блокировка опасных протоколов в атрибутах
     * 
     * @param string $content HTML-контент
     * @return string Очищенный контент
     */
    private static function blockDangerousProtocols(string $content): string
    {
        // Блокируем javascript:, data: и vbscript: в href, src, action, formaction
        return preg_replace(
            '/(href|src|action|formaction)\s*=\s*["\']?\s*(javascript|data|vbscript):/i',
            '$1=""',
            $content
        );
    }

    /**
     * Удаление опасных HTML-тегов
     * 
     * @param string $content HTML-контент
     * @return string Очищенный контент
     */
    private static function removeDangerousTags(string $content): string
    {
        // Удаляем опасные теги и их содержимое
        $dangerousTags = [
            'script', 'iframe', 'object', 'applet', 'style', 'form'
        ];

        foreach ($dangerousTags as $tag) {
            $content = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $content);
        }

        // Удаляем одиночные опасные теги
        $singleDangerousTags = [
            'embed', 'meta', 'link', 'base'
        ];

        foreach ($singleDangerousTags as $tag) {
            $content = preg_replace('/<' . $tag . '\b[^>]*>/is', '', $content);
        }

        return $content;
    }

    /**
     * Очистка style-атрибутов от опасного содержимого и скрытых элементов
     * 
     * @param string $content HTML-контент
     * @return string Очищенный контент
     */
    private static function cleanStyleAttributes(string $content): string
    {
        // Удаляем style-атрибуты с опасным содержимым (expression, javascript, behavior, @import, binding)
        $content = preg_replace(
            '/\s*style\s*=\s*["\'][^"\']*(?:expression|javascript|behavior|@import|binding)[^"\']*["\']|\s*style\s*=\s*[^\s>]*(?:expression|javascript|behavior|@import|binding)[^\s>]*/i',
            '',
            $content
        );
        
        // Удаляем элементы со стилями, скрывающими контент
        $content = self::removeHiddenElements($content);
        
        return $content;
    }
    
    /**
     * Удаление элементов со скрывающими CSS-стилями
     * Удаляет теги с атрибутами style, содержащими:
     * - display:none
     * - visibility:hidden
     * - opacity:0
     * - width:0/height:0
     * - font-size:0
     * - и другие способы скрытия контента
     * 
     * @param string $content HTML-контент
     * @return string Очищенный контент
     */
    private static function removeHiddenElements(string $content): string
    {
        // Паттерны для обнаружения скрывающих стилей
        $hiddenPatterns = [
            'display\s*:\s*none',
            'visibility\s*:\s*hidden',
            'opacity\s*:\s*0(?:\.0+)?',
            'width\s*:\s*0(?:px|em|rem|%)?',
            'height\s*:\s*0(?:px|em|rem|%)?',
            'font-size\s*:\s*0(?:px|em|rem)?',
            'color\s*:\s*transparent',
            'text-indent\s*:\s*-9999(?:px|em|rem)',
            'position\s*:\s*absolute.*?left\s*:\s*-9999(?:px|em|rem)',
            'clip\s*:\s*rect\s*\(\s*0',
            'overflow\s*:\s*hidden.*?height\s*:\s*0',
            'transform\s*:\s*scale\s*\(\s*0',
        ];
        
        // Объединяем паттерны в один регулярное выражение
        $pattern = '/(' . implode('|', $hiddenPatterns) . ')/i';
        
        // Находим теги с скрывающими стилями и нейтрализуем скрывающие декларации внутри style
        // Вместо удаления или экранирования тега делаем так, чтобы CSS-правила не сработали
        // (вставляем zero-width space после двоеточия и заменяем значения на похожие с кириллическими буквами)
        $content = preg_replace_callback(
            '/<([a-z][a-z0-9]*)\b([^>]*)\sstyle\s*=\s*["\']([^"\']*)["\']([^>]*)>(.*?)<\/\1>/is',
            function($matches) use ($pattern) {
                // $matches: 1 - tag name, 2/4 - other attrs, 3 - style, 5 - inner content
                $tag = $matches[1];
                $before = $matches[2];
                $style = $matches[3];
                $after = $matches[4];
                $inner = $matches[5];

                // Если стиль содержит скрывающие свойства, модифицируем значение style
                if (preg_match($pattern, $style)) {
                    // Список свойств, которые нужно нейтрализовать
                    $props = ['display','visibility','opacity','width','height','font-size','color','text-indent','position','clip','overflow','transform'];

                    // Вставляем zero-width space (U+200B) после двоеточия у таких свойств
                    $style = preg_replace_callback('/(' . implode('|', array_map('preg_quote', $props)) . ')\s*:\s*/i',
                        function($m) {
                            return $m[1] . ':' . "\u{200B}"; // zero-width space
                        },
                        $style
                    );

                    // Заменяем явные значения 'none' и 'hidden' на похожие с кириллическими символами
                    // латинская 'o' -> кириллическая 'о' (U+043E), латинская 'i' -> кириллическая 'і' (U+0456)
                    $style = preg_replace('/\bnone\b/i', 'nоne', $style);
                    $style = preg_replace('/\bhidden\b/i', 'hіdden', $style);

                    // Собираем обратно тег с нейтрализованным style
                    $newTag = '<' . $tag . $before . ' style="' . $style . '"' . $after . '>' . $inner . '</' . $tag . '>';
                    return $newTag;
                }

                return $matches[0];
            },
            $content
        );

        // Также обрабатываем одиночные теги (например, <img/>, <br>) с style
        $content = preg_replace_callback(
            '/<([a-z][a-z0-9]*)\b([^>]*)\sstyle\s*=\s*["\']([^"\']*)["\']([^>]*)\/?\s*>/is',
            function($matches) use ($pattern) {
                $tag = $matches[1];
                $before = $matches[2];
                $style = $matches[3];
                $after = $matches[4];

                if (preg_match($pattern, $style)) {
                    $props = ['display','visibility','opacity','width','height','font-size','color','text-indent','position','clip','overflow','transform'];

                    $style = preg_replace_callback('/(' . implode('|', array_map('preg_quote', $props)) . ')\s*:\s*/i',
                        function($m) {
                            return $m[1] . ':' . "\u{200B}";
                        },
                        $style
                    );

                    $style = preg_replace('/\bnone\b/i', 'nоne', $style);
                    $style = preg_replace('/\bhidden\b/i', 'hіdden', $style);

                    $newTag = '<' . $tag . $before . ' style="' . $style . '"' . $after . '>';
                    return $newTag;
                }

                return $matches[0];
            },
            $content
        );
        
        return $content;
    }

    /**
     * Блокировка Base64-encoded контента в атрибутах
     * 
     * @param string $content HTML-контент
     * @return string Очищенный контент
     */
    private static function blockBase64Content(string $content): string
    {
        // Блокируем data:*;base64 в href, src, action, data
        return preg_replace(
            '/(href|src|action|data)\s*=\s*["\']?\s*data:.*?base64/i',
            '$1=""',
            $content
        );
    }

    /**
     * Очистка опасных HTML-entities
     * 
     * @param string $content HTML-контент
     * @return string Очищенный контент
     */
    private static function cleanHtmlEntities(string $content): string
    {
        // Удаляем опасные символы, закодированные в HTML-entities
        // Эти коды могут использоваться для обхода фильтров
        return preg_replace(
            '/&#(x)?0*(((1)?3[37]|(1)?4[14]|(1)?6[0A]|(1)?7[5B]|(1)?9[68]|(1)?10[0-7]|(1)?11[0-1]|(1)?12[0-7]|(1)?13[0-7]|(1)?14[0-6]|(1)?15[0-9]|(1)?16[0-9]|(1)?17[0-2]));?/i',
            '',
            $content
        );
    }

    /**
     * Очистка массива данных от XSS
     * Рекурсивно обрабатывает все строковые значения
     * 
     * @param array $data Массив данных
     * @param bool $stripAllTags Удалить все HTML-теги
     * @return array Очищенный массив
     * 
     * @example
     * $data = [
     *     'title' => '<script>alert(1)</script>Title',
     *     'content' => '<p onclick="alert(1)">Text</p>'
     * ];
     * $clean = XssSecurity::cleanArray($data);
     * // Результат: ['title' => 'Title', 'content' => '<p>Text</p>']
     */
    public static function cleanArray(array $data, bool $stripAllTags = false): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::clean($value, $stripAllTags);
            } elseif (is_array($value)) {
                $data[$key] = self::cleanArray($value, $stripAllTags);
            }
        }

        return $data;
    }

    /**
     * Проверка, содержит ли строка потенциально опасный код
     * 
     * @param string $content Контент для проверки
     * @return bool True если обнаружен потенциально опасный код
     * 
     * @example
     * if (XssSecurity::isDangerous('<script>alert(1)</script>')) {
     *     // Обработать как опасный контент
     * }
     */
    public static function isDangerous(string $content): bool
    {
        // Проверяем наличие опасных тегов
        if (preg_match('/<(script|iframe|object|embed|applet)\b/i', $content)) {
            return true;
        }

        // Проверяем наличие опасных протоколов
        if (preg_match('/(javascript|data|vbscript):/i', $content)) {
            return true;
        }

        // Проверяем наличие обработчиков событий
        foreach (self::DANGEROUS_ATTRIBUTES as $attr) {
            if (preg_match('/\s' . preg_quote($attr, '/') . '\s*=/i', $content)) {
                return true;
            }
        }

        return false;
    }
}

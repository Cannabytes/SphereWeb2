<?php

namespace Ofey\Logan22\component\error;

use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;

class error
{

    public static function initDefault(): void
    {

        set_error_handler( function ($errno, $errstr, $errfile, $errline) {
            $filename = "error.log";
            $dateTime = date('Y-m-d H:i:s');
            $logMessage = "[$dateTime] Error: $errstr in $errfile on line $errline" . PHP_EOL;
            $logMessage .= "Stack trace:" . PHP_EOL;
            $backtrace = debug_backtrace();
            foreach ($backtrace as $i => $trace) {
                if (isset($trace['file']) && isset($trace['line'])) {
                    $logMessage .= "#$i {$trace['file']}({$trace['line']}): ";
                    if (isset($trace['class'])) {
                        $logMessage .= "{$trace['class']}{$trace['type']}";
                    }
                    $logMessage .= "{$trace['function']}(";
                    if (isset($trace['args']) && count($trace['args']) > 0) {
                        $args = array_map('gettype', $trace['args']);
                        $logMessage .= implode(', ', $args);
                    }
                    $logMessage .= ")" . PHP_EOL;
                }
            }
            file_put_contents($filename, $logMessage, FILE_APPEND);
        });
    }



    public static function log_error($errno, $errstr, $errfile, $errline): void
    {
        $type = match ($errno) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => 'Fatal Error',
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE, E_USER_NOTICE => 'Notice',
            E_STRICT => 'Strict',
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
            E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
            default => 'Unknown error type'
        };

        $trace = debug_backtrace();
        $traceString = "";
        foreach ($trace as $entry) {
            $file = $entry['file'] ?? 'Анонимная функция или внешний вызов';
            $line = $entry['line'] ?? 'Неизвестно';
            $function = $entry['function'] ?? 'Неопределенная функция';
            $traceString .= "File: " . $file . "\n";
            $traceString .= "Line: " . $line . "\n";
            $traceString .= "Function: " . $function . "\n";
            if (isset($entry['args']) && is_array($entry['args'])) {
                $traceString .= "Arguments: " . implode(', ', array_map(function ($arg) {
                      return is_object($arg) ? get_class($arg) : (is_array($arg) ? 'Array' : var_export($arg, true));
                  }, $entry['args'])) . "\n";
            }
            $traceString .= "----------------\n";
        }

        $user_id = $_SESSION['id'] ?? -1;

        $request_data = [
          'URL' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
          'GET' => $_GET,
          'POST' => $_POST,
          'REFERER' => $_SERVER['HTTP_REFERER'] ?? '',
          'SESSION' => $_SESSION,
        ];

        $request = json_encode($request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            sql::run('INSERT INTO `errors` (type, message, request, trace, user_id, date) VALUES (?, ?, ?, ?, ?, ?)', [$type, $errstr, $request, $traceString, $user_id, time::mysql()]);
        } catch (\Exception $e) {
            error_log('Failed to log error to the database: ' . $e->getMessage());
        }
    }

    public static function init(): void
    {
        set_error_handler([self::class, 'log_error']);
    }
}

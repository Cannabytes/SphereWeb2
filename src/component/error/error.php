<?php

namespace Ofey\Logan22\component\error;

use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\user\user;

class error
{

    public static function log_exception(\Throwable $exception): void
    {
        self::log_error(E_ERROR, $exception->getMessage(), $exception->getFile(), $exception->getLine());
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
        try {
//            server::send(type::ERROR_REPORT, [
//              'URL'     => $_SERVER['REQUEST_URI'] ?? 'Unknown',
//              'GET'     => $_GET,
//              'POST'    => $_POST,
//              'REFERER' => $_SERVER['HTTP_REFERER'] ?? '',
//              'SESSION' => $_SESSION,
//              'USER'    => user::self()->toArray(),
//              'TYPE'    => $type,
//              'TRACE'   => debug_backtrace(),
//            ]);
        } catch (\Exception $e) {
            error_log('Failed to log error to the database: ' . $e->getMessage());
        }
    }

    private static function get_trace_as_string(): string
    {
        $trace       = debug_backtrace();
        $traceString = "";
        foreach ($trace as $entry) {
            $file        = $entry['file'] ?? 'Анонимная функция или внешний вызов';
            $line        = $entry['line'] ?? 'Неизвестно';
            $function    = $entry['function'] ?? 'Неопределенная функция';
            $traceString .= "File: " . $file . "\n";
            $traceString .= "Line: " . $line . "\n";
            $traceString .= "Function: " . $function . "\n";
            if (isset($entry['args']) && is_array($entry['args'])) {
                $traceString .= "Arguments: " . implode(', ', array_map(function ($arg) {
                      return is_object($arg) ? get_class($arg) : (is_array($arg) ? 'Array' : var_export($arg, true));
                  }, $entry['args'])) . "\n";
            }
            $data[]       = $entry['object'] ?? null;
            $traceString .= "\n";
        }

        return $traceString;
    }

    public static function handle_fatal_error(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log_error($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    public static function init(): void
    {
        set_error_handler([self::class, 'log_error']);
        set_exception_handler([self::class, 'log_exception']);
        register_shutdown_function([self::class, 'handle_fatal_error']);
    }

}

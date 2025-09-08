<?php

namespace Provider\PaymentGateway;

class Logger
{
    private static $logFile = null;

    /**
     * Initialize logger with log file path
     */
    public static function init($logFile = null)
    {
        if ($logFile === null) {
            $logFile = dirname(__DIR__) . '/logs/payment_gateway.log';
        }
        self::$logFile = $logFile;
        
        // Create logs directory if it doesn't exist
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Write log entry to file
     */
    private static function writeLog($level, $message, $context = [])
    {
        if (self::$logFile === null) {
            self::init();
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log info message
     */
    public static function info($message, $context = [])
    {
        self::writeLog('INFO', $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning($message, $context = [])
    {
        self::writeLog('WARNING', $message, $context);
    }

    /**
     * Log error message
     */
    public static function error($message, $context = [])
    {
        self::writeLog('ERROR', $message, $context);
    }

    /**
     * Log debug message
     */
    public static function debug($message, $context = [])
    {
        self::writeLog('DEBUG', $message, $context);
    }
}
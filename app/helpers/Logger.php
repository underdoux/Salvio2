<?php

class Logger {
    protected static $logFile = __DIR__ . '/../../storage/logs/app.log';

    public static function log($message) {
        $date = date('Y-m-d H:i:s');
        $entry = "[{$date}] {$message}\n";
        file_put_contents(self::$logFile, $entry, FILE_APPEND);
    }
}

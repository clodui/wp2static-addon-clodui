<?php

namespace WP2StaticClodui;


class Logger {

    private static $log_level_map = [
        'FATAL' => 100,
        'ERROR' => 200,
        'WARN' => 300,
        'INFO' => 400,
        'DEBUG' => 500,
        'TRACE' => 600
    ];

    public static $log_level = 'INFO';
    
    private static function log(string $level, string $message) {
        if(self::$log_level_map[strtoupper($level)] <= self::$log_level_map[strtoupper(self::$log_level)]) {
            $prefix = 'Clodui ['. $level .'] ';
            \WP2Static\WsLog::l( $prefix.$message );
        }
    }

    public static function info(string $message) {
        self::log("INFO", $message);
    }

    public static function warn(string $message) {
        self::log("WARN", $message);
    }

    public static function error(string $message) {
        self::log("ERROR", $message);
    }

    public static function debug(string $message) {
        self::log("DEBUG", $message);
    }
}
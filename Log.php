<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Help class for Log class
 * @file Log.php
 */
class Wx_LogLevel
{
    const DISABLE = 0;
    const ERROR = 1;
    const WARNING = 2;
    const INFO = 3;
    const DEBUG = 4;
    public static function getName($level) {
        switch ($level) {
            case 0: return 'DISABLE';
            case 1: return 'ERROR';
            case 2: return 'WARNING';
            case 3: return 'INFO';
            case 4: return 'DEBUG';
            default: return 'UNKNOWN';
        }
    }
}

class Wx_Log
{
    public static $LOG_LEVEL = 0;
    public static $LOG_FILE = '';
    public static function INIT() {
        if (defined('CCI_APP_LOG_FILE')) {
            self::$LOG_LEVEL = Wx_LogLevel::DEBUG;  // default log_level
            self::$LOG_FILE = CCI_APP_LOG_FILE;
        }
    }
    /**
     * Write log files to $LOG_FILE
     */
    static private function logging($msg, $name = NULL)
    {
        $currTime = strftime("%b %d %Y %X", time());
        $file = fopen($name, "a+");
        if (flock($file, LOCK_EX)) {
            fwrite($file, $currTime . ":" . $msg . "\n");
            flock($file, LOCK_UN);
        }
        fclose($file);
    }
    /**
     * Mapping from PHP's errors into our logging levels
     */
    public static $ERROR_CODE_TO_LOG_LEVEL = NULL;

    static public function logMsg($msg, $data, $level, $file = NULL, $line = NULL)
    {
        if (self::$LOG_LEVEL <= 0 || self::$LOG_LEVEL < $level) {
            return;
        }
        self::logMsgEx($msg, $data, $level, $file, $line, static::$LOG_FILE);
    }
    static public function logMsgEx($msg, $data, $level, $file, $line, $name) {
        // The message prefix including $file and $line information
        $msgPrefix = getmypid() . ": ";
        if (!is_null($file)) {
            $msgPrefix .= basename($file) . ": ";
        }
        if (!is_null($line)) {
            $msgPrefix .= $line . ": ";
        }
        // The full message to be logged to a file, need to convert $data into a string
        $logMessage = $msgPrefix . $msg;
        if (!is_null($data)) {
            if (is_string($data)) {
                $logMessage .= ":" . $data;
            } else {
                $logMessage .= ":" . var_export($data, true);
            }
        }
        self::logging(Wx_LogLevel::getName($level) . ":" . $logMessage, $name);
    }
    static public function info($msg, $data = NULL, $file = NULL, $line = NULL) {
        self::logMsg($msg, $data, Wx_LogLevel::INFO, $file, $line);
    }
    static public function debug($msg, $data = NULL, $file = NULL, $line = NULL) {
        self::logMsg($msg, $data, Wx_LogLevel::DEBUG, $file, $line);
    }
    static public function error($msg, $data = NULL, $file = NULL, $line = NULL) {
        self::logMsg($msg, $data, Wx_LogLevel::ERROR, $file, $line);
    }
    static public function warn($msg, $data = NULL, $file = NULL, $line = NULL) {
        self::logMsg($msg, $data, Wx_LogLevel::WARNING, $file, $line);
    }
}
// Should be initialized in the class, but php doesn't allow initializing static members to an expression
Wx_Log::$ERROR_CODE_TO_LOG_LEVEL = array(
    E_ERROR => Wx_LogLevel::ERROR,
    E_WARNING => Wx_LogLevel::WARNING,
    E_PARSE => Wx_LogLevel::ERROR,
    E_NOTICE => Wx_LogLevel::INFO,
    E_CORE_ERROR => Wx_LogLevel::ERROR,
    E_CORE_WARNING => Wx_LogLevel::WARNING,
    E_COMPILE_ERROR => Wx_LogLevel::ERROR,
    E_COMPILE_WARNING => Wx_LogLevel::WARNING,
    E_USER_ERROR => Wx_LogLevel::ERROR,
    E_USER_WARNING => Wx_LogLevel::WARNING,
    E_USER_NOTICE => Wx_LogLevel::INFO,
    E_STRICT => Wx_LogLevel::WARNING
);
Wx_Log::INIT();
?>


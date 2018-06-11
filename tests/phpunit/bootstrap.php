<?php
/**
 * @package forecastio-augmentation
 * @copyright 2014 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

defined('DARKSKY_KEY') || define('DARKSKY_KEY', getenv('DARKSKY_KEY') ? getenv('DARKSKY_KEY') : 'darksky_api_key');

require_once __DIR__ . '/../vendor/autoload.php';

set_error_handler(
    function ($errno, $errstr, $errfile, $errline, array $errcontext) {
        if (0 === error_reporting()) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
);

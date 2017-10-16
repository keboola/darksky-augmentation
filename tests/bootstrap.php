<?php
/**
 * @package forecastio-augmentation
 * @copyright 2014 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

defined('DARKSKY_KEY') || define('DARKSKY_KEY', getenv('DARKSKY_KEY') ? getenv('DARKSKY_KEY') : 'darksky_api_key');

require_once __DIR__ . '/../vendor/autoload.php';

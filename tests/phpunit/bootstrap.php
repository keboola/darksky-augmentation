<?php

declare(strict_types=1);

defined('DARKSKY_KEY') || define('DARKSKY_KEY', getenv('DARKSKY_KEY') ?? 'darksky_api_key');

require_once __DIR__ . '/../../vendor/autoload.php';

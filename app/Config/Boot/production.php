<?php

/*
 |--------------------------------------------------------------------------
 | ERROR DISPLAY
 |--------------------------------------------------------------------------
 | Don't show ANY in production environments. Instead, let the system catch
 | it and display a generic error message.
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

defined('CI_DEBUG') || define('CI_DEBUG', false);

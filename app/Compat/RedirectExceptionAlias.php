<?php

/**
 * CI4 4.7 moved RedirectException from CodeIgniter\Router\Exceptions
 * to CodeIgniter\HTTP\Exceptions. Myth\Auth still uses the old namespace.
 * This shim creates a class alias for backward compatibility.
 */

if (! class_exists('CodeIgniter\Router\Exceptions\RedirectException', false)) {
    class_alias(
        'CodeIgniter\HTTP\Exceptions\RedirectException',
        'CodeIgniter\Router\Exceptions\RedirectException'
    );
}

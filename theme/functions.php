<?php
/**
 * Theme bootstrap file for the OH Digital theme.
 *
 * @package OHTheme
 */

declare(strict_types=1);

namespace OHTheme;

if (! defined('ABSPATH')) {
    exit;
}

const THEME_VERSION = '0.1.0';
const THEME_TEXT_DOMAIN = 'oh-digital';

// Simple PSR-4 style autoloader for classes placed under the inc/ directory.
spl_autoload_register(
    static function (string $class): void {
        if (strpos($class, __NAMESPACE__) !== 0) {
            return;
        }

        $relative = str_replace(__NAMESPACE__ . '\\', '', $class);
        $map      = [
            'ThemeSetup' => 'class-theme-setup.php',
            'WCOverrides' => 'class-wc-overrides.php',
            'Ajax'        => 'class-ajax.php',
        ];

        if (isset($map[$relative])) {
            $path = __DIR__ . '/inc/' . $map[$relative];
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
);

// Include helper functions.
require_once __DIR__ . '/inc/helpers.php';

ThemeSetup::init();
WCOverrides::init();
Ajax::init();

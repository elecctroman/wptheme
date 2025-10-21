<?php
/**
 * Front page loader.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

require get_template_directory() . '/templates/page-home.php';

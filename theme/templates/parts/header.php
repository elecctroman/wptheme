<?php
/**
 * Theme header partial.
 *
 * @package OHTheme
 */

declare(strict_types=1);

use function OHTheme\get_theme_palette;
use function OHTheme\render_gateway_badges;

$palette = get_theme_palette();
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
$cart_url    = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '#';
$cart_count  = (function_exists('WC') && WC() && WC()->cart) ? (int) WC()->cart->get_cart_contents_count() : 0;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class('oh-theme dark-mode'); ?> style="--color-primary: <?php echo esc_attr($palette['primary']); ?>;">
<header class="oh-header">
    <div class="oh-header__top">
        <div class="oh-container">
            <span class="oh-header__badge"><?php esc_html_e('Dijital teslimatta 7/24 hizmet', 'oh-digital'); ?></span>
            <div class="oh-header__gateways"><?php echo render_gateway_badges(); ?></div>
            <a class="oh-header__support" href="https://wa.me/905551112233" target="_blank" rel="noopener">
                <?php esc_html_e('WhatsApp Destek', 'oh-digital'); ?>
            </a>
        </div>
    </div>
    <div class="oh-header__main">
        <div class="oh-container">
            <div class="oh-logo"><a href="<?php echo esc_url(home_url('/')); ?>">E-PİN</a></div>
            <nav class="oh-primary-nav" aria-label="<?php esc_attr_e('Ana Menü', 'oh-digital'); ?>">
                <?php wp_nav_menu(['theme_location' => 'primary', 'fallback_cb' => false]); ?>
            </nav>
            <div class="oh-header__actions">
                <button class="oh-header__search" type="button" aria-label="<?php esc_attr_e('Ara', 'oh-digital'); ?>">
                    <span class="dashicons dashicons-search"></span>
                </button>
                <a class="oh-header__account" href="<?php echo esc_url($account_url); ?>">
                    <span class="dashicons dashicons-admin-users"></span>
                </a>
                <a class="oh-header__cart" href="<?php echo esc_url($cart_url); ?>">
                    <span class="dashicons dashicons-cart"></span>
                    <span class="oh-header__cart-count"><?php echo esc_html($cart_count); ?></span>
                </a>
            </div>
        </div>
    </div>
    <div class="oh-header__mega">
        <div class="oh-container">
            <?php get_search_form(); ?>
        </div>
    </div>
</header>
<main class="oh-main">

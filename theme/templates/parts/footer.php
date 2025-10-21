<?php
/**
 * Theme footer partial.
 *
 * @package OHTheme
 */

declare(strict_types=1);

use function OHTheme\render_gateway_badges;

?>
</main>
<footer class="oh-footer">
    <div class="oh-container oh-footer__widgets">
        <div class="oh-footer__column">
            <h4><?php esc_html_e('Hakkımızda', 'oh-digital'); ?></h4>
            <p><?php esc_html_e('E-PİN dijital lisans ve oyun içi para satışında güvenilir partneriniz.', 'oh-digital'); ?></p>
        </div>
        <div class="oh-footer__column">
            <h4><?php esc_html_e('Kategoriler', 'oh-digital'); ?></h4>
            <?php wp_nav_menu(['theme_location' => 'secondary', 'fallback_cb' => false]); ?>
        </div>
        <div class="oh-footer__column">
            <h4><?php esc_html_e('Destek', 'oh-digital'); ?></h4>
            <ul>
                <li><a href="mailto:support@example.com"><?php esc_html_e('E-Posta', 'oh-digital'); ?></a></li>
                <li><a href="tel:+905551112233"><?php esc_html_e('Canlı Destek', 'oh-digital'); ?></a></li>
            </ul>
        </div>
    </div>
    <div class="oh-container oh-footer__bottom">
        <div class="oh-footer__gateways"><?php echo render_gateway_badges(); ?></div>
        <p>&copy; <?php echo esc_html(date('Y')); ?> E-PİN. <?php esc_html_e('Tüm hakları saklıdır.', 'oh-digital'); ?></p>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

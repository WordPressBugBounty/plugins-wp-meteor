<?php

/**
 * WP_Meteor
 *
 * @package   WP_Meteor
 * @author    Aleksandr Guidrevitch <alex@excitingstartup.com>
 * @copyright 2020 wp-meteor.com
 * @license   GPL 2.0+
 * @link      https://wp-meteor.com
 */
?>

<div class="wrap">

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <form id="settings" method="post">
        <input type="hidden" name="wpmeteor_action" value="save_settings" />
        <?php wp_nonce_field('wpmeteor_save_settings_nonce', 'wpmeteor_save_settings_nonce'); ?>

        <div id="tabs" class="settings-tab">
            <ul>
                <li><a href="#settings" class="tab-handle"><?php esc_html_e('Settings', WPMETEOR_TEXTDOMAIN); ?></a></li>
                <li><a href="#exclusions" class="tab-handle"><?php esc_html_e('Exclusions', WPMETEOR_TEXTDOMAIN); ?></a></li>
                <li><a href="#elementor" class="tab-handle"><?php esc_html_e('Elementor', WPMETEOR_TEXTDOMAIN); ?></a></li>
            </ul>
            <div id="settings" class="tab">
                <?php do_action(WPMETEOR_TEXTDOMAIN . '-backend-display-settings-ultimate'); ?>
                <div className="field">
                    <input type="submit" name="submit" id="submit" class="button" value="Save Changes" />
                </div>
            </div>
            <div id="exclusions" class="tab">
                <?php do_action(WPMETEOR_TEXTDOMAIN . '-backend-display-settings-exclusions'); ?>
                <div className="field">
                    <input type="submit" name="submit" id="submit" class="button" value="Save Changes" />
                </div>
            </div>
            <div id="elementor" class="tab">
                <?php do_action(WPMETEOR_TEXTDOMAIN . '-backend-display-settings-elementor'); ?>
                <div className="field">
                    <input type="submit" name="submit" id="submit" class="button" value="Save Changes" />
                </div>
            </div>
        </div>
    </form>
    <section class="banner">
        <span class="image">
            <a href="https://watchero.io/?utm_source=wpmeteor&utm_campaign=settings-banner" target="_blank">
                <strong>Watchero</strong>
            </a>
        </span>
        <span class="line">
               - visual checks for WordPress updates and performance changes
        </span>
        <span class="button-wrap">
            <a href="https://watchero.io/?utm_source=wpmeteor&utm_campaign=settings-banner" target="_blank" class="button">TRY WATCHERO</a>
        </span>
    </section>
</div>

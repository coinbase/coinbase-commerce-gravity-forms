<?php

class GFCoinbaseCommerceSettings
{
    const APP_KEY_PARAM = 'app_key';
    const APP_SECRET_PARAM = 'app_secret';
    const SETTINGS_OPTIONS = 'gf_coinbase_commerce_options';
    const SETTINGS_PAGE = 'gf_coinbase_commerce_settings';

    function __construct()
    {
        // check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        RGForms::add_settings_page('Coinbase Commerce', array($this, 'options_page_html'));
        $this->settings_enqueue_scripts();
        add_action('admin_init', array($this, 'settings_init'));
    }

    /**
     * admin_enqueue_scripts
     * Used to enqueue custom styles
     * @return void
     */
    function settings_enqueue_scripts()
    {
        wp_enqueue_style('gf_coinbase_commerce_settings', plugins_url('assets/css/settings.css', __FILE__));
    }

    function options_page_html()
    {
        if (isset($_GET['settings-updated']) && !get_settings_errors(self::SETTINGS_OPTIONS)) {
            // add settings saved message with the class of "updated"
            add_settings_error(self::SETTINGS_OPTIONS, 'gf_coinbase_commerce_message', __('Settings Saved', 'heremobility'), 'updated');
        }

        $this->settings_errors(self::SETTINGS_OPTIONS);
        $siteUrl = get_site_url(null, '', 'https');
        $webhookUrl = add_query_arg(array('callback' => GF_COINBASE_COMMERCE_SLUG), $siteUrl);

        ?>
            <div class="settings-wrap">
                <h2>Coinbase Commerce Settings</h2>
                <div>
                    <h3>How to use</h3>
                    <ul>
                        <li>
                            <p>1. Open the page "Settings" of Coinbase Commerce Dashboard: <a
                                        href="https://commerce.coinbase.com/dashboard/settings">https://commerce.coinbase.com/dashboard/settings</a>
                            </p>
                        </li>
                        <li>
                            <p>2. In the section "Webhook subscriptions", click "Add an endpoint". In the form that opens
                                paste <b><?php echo $webhookUrl; ?></b> into the field "New Webhook Subscription" and click
                                "Save".</p>
                        </li>
                        <li>
                            <p>3. Copy "Api Key" from Api Keys section, "Shared Secret" from Webhook subscriptions section
                                and past below.</p>
                            <p>Note: the webhook URL must use https:// and have a valid SSL certificate configured.</p>
                        </li>
                    </ul>
                </div>

                <form action="options.php" method="post">
                    <?php
                    // output security fields for the registered setting
                    settings_fields(self::SETTINGS_PAGE);
                    // output setting sections and their fields
                    do_settings_sections(self::SETTINGS_PAGE);
                    // output save settings button
                    submit_button('Save Settings');
                    ?>
                </form>
            </div>
        <?php
    }

    function settings_errors($setting = '', $sanitize = false, $hide_on_update = false)
    {
        if ($hide_on_update && !empty($_GET['settings-updated'])) {
            return;
        }

        $settings_errors = get_settings_errors($setting, $sanitize);

        if (empty($settings_errors)) {
            return;
        }

        $output = '';
        foreach ($settings_errors as $key => $details) {
            $css_id = 'setting-error-' . $details['code'];
            $css_class = 'my-' . $details['type'] . ' settings-error';
            $output .= "<div id='$css_id' class='$css_class'> \n";
            $output .= "<p><strong>{$details['message']}</strong></p>";
            $output .= "</div> \n";
        }
        echo $output;
    }

    function settings_init()
    {
        // register a new setting
        register_setting(self::SETTINGS_PAGE, self::SETTINGS_OPTIONS, array($this, 'sanitize_callback'));

        $sectionCredentials = 'gf_coinbase_commerce_keys';

        // register a new section
        add_settings_section(
            $sectionCredentials,
            __('', 'heremobility'),
            array($this, 'section_developers_cb'),
            self::SETTINGS_PAGE
        );

        add_settings_field(
            self::APP_KEY_PARAM,
            'Api Key',
            array($this, 'setting_cb'),
            self::SETTINGS_PAGE,
            $sectionCredentials,
            array(
                'id' => self::APP_KEY_PARAM
            )
        );

        add_settings_field(
            self::APP_SECRET_PARAM,
            'Shared Secret Key',
            array($this, 'setting_cb'),
            self::SETTINGS_PAGE,
            $sectionCredentials,
            array(
                'id' => self::APP_SECRET_PARAM,
            )
        );
    }

    function sanitize_callback($value)
    {
        $message = '';
        $error = false;

        if (empty($value[self::APP_KEY_PARAM])) {
            $error = true;
            $message .= __('Api Key is required.</br>', 'heremobility');
        }

        if (empty($value[self::APP_SECRET_PARAM])) {
            $error = true;
            $message .= __('Shared Secret Key is required.</br>', 'heremobility');
        }

        if ($error) {
            $message = __('Settings were not saved.</br>', 'heremobility') . $message;
            add_settings_error(self::SETTINGS_OPTIONS, 'gf_coinbase_commerce_message', $message, 'error');
            return get_option(self::SETTINGS_OPTIONS);
        }

        return $value;
    }

    function setting_cb($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option(self::SETTINGS_OPTIONS);
        // output the field
        ?>
        <input type="text"
               id="<?php echo esc_attr($args['id']); ?>"
               name="gf_coinbase_commerce_options[<?php echo esc_attr($args['id']); ?>]"
               value="<?php echo $options[$args['id']]; ?>"
        />
        <?php
    }

    function section_developers_cb($args)
    {
        ?>
        <p id="<?php echo esc_attr($args['id']); ?>"></p>
        <?php
    }
}

<?php
class GFCoinbaseCommerceAdmin
{
    public $settingsURL;

    public function __construct()
    {
        // handle change in settings pages
        if (true === class_exists('GFCommon')) {
            if (version_compare(GFCommon::$version, '1.6.99999', '<')) {
                // pre-v1.7 settings
                $this->settingsURL = admin_url('admin.php?page=gf_settings&addon=Coinbase+Commerce');
            } else {
                // post-v1.7 settings
                $this->settingsURL = admin_url('admin.php?page=gf_settings&subview=Coinbase+Commerce');
            }
        }

        // add action hook for adding plugin action links
        add_action('plugin_action_links_' . PLUGIN_BASENAME, array($this, 'add_settings_links'));

        // show notice if gf plugin not installed and enabled
        add_action('admin_notices', array($this, 'admin_notices'));

        register_deactivation_hook(PLUGIN_FILE, array($this, 'deactivation'));
    }

    public function deactivation()
    {
        delete_option(GFCoinbaseCommerceSettings::SETTINGS_OPTIONS);
    }

    /**
     * action hook for adding plugin action links
     */
    public function add_settings_links($links)
    {
        // add settings link, but only if GravityForms plugin is active
        if (class_exists('RGForms')) {
            $settings_link = sprintf('<a href="%s">%s</a>', $this->settingsURL, __('Settings'));
            array_unshift($links, $settings_link);
        }

        return $links;
    }

    public function admin_notices()
    {
        if (class_exists('RGForms') === false) {
             echo "<div class='error'><p><strong>Coinbase Commerce plugin for Gravity Forms requires <a href=\"http://www.gravityforms.com/\">Gravity Forms</a> to be installed and activated.</div></p></strong>";
        }
    }
}

<?php

if ( ! class_exists( 'WMobilePack_Options' ) ) {

    /**
     * Overall Option Management class
     *
     * Instantiates all the options and offers a number of utility methods to work with the options
     */
    class WMobilePack_Options
    {

        /* ----------------------------------*/
        /* Properties						 */
        /* ----------------------------------*/

        public static $prefix = 'wmpack_';
        public static $transient_prefix = 'wmp_';

        public static $options = array(

            // themes
            'theme' => 1,
            'color_scheme' => 1,
            'custom_colors' => array(),
            'theme_timestamp' => '',
            'font_headlines' => 1,
            'font_subtitles' => 1,
            'font_paragraphs' => 1,

            // images
            'logo' => '',
            'icon' => '',
            'cover' => '',

            // content
            'inactive_categories' => array(),
            'inactive_pages' => array(),
            'ordered_categories' => array(),
            'ordered_pages' => array(),

            // other settings
            'display_mode' => 'normal',
            'google_analytics_id' => '',
            'display_website_link' => 1,

            // premium accounts with api key
            'premium_api_key'		 => '',
            'premium_config_path'	 => '',
            'premium_active'		 => 0,

            // administrative
            'joined_waitlists' => array(),
            'whats_new_updated' => 0,
            'whats_new_last_updated' => 0,
            'allow_tracking' => 0
        );

        /* ----------------------------------*/
        /* Methods							 */
        /* ----------------------------------*/

        /**
         * The get_setting method is used to read an option value (or options) from the database.
         *
         * @param $option - array / string
         *
         * If the $option param is an array, the method will return an array with the values,
         * otherwise it will return only the requested option value.
         *
         */
        public static function get_setting($option)
        {

            // if the passed param is an array, return an array with all the settings
            if (is_array($option)) {

                foreach ($option as $option_name => $option_value) {
                    if (get_option(self::$prefix . $option_name) == '')
                        $wmp_settings[$option_name] = self::$options[$option_name];
                    else
                        $wmp_settings[$option_name] = get_option(self::$prefix . $option_name);
                }

                // return array
                return $wmp_settings;

            } elseif (is_string($option)) { // if option is a string, return the value of the option

                // check if the option is added in the db
                if (get_option(self::$prefix . $option) === false) {
                    $wmp_setting = self::$options[$option];
                } else {
                    $wmp_setting = get_option(self::$prefix . $option);
                }

                return $wmp_setting;
            }

        }


        /**
         *
         * The save_settings method is used to save an option value (or options) in the database.
         *
         * @param $option - array / string
         * @param $option_value - optional, mandatory only when $option is a string
         *
         * @return bool
         *
         */
        public static function save_settings($option, $option_value = '')
        {

            if (current_user_can('manage_options')) {

                if (is_array($option) && !empty($option)) {

                    // set option not saved variable
                    $option_not_saved = false;

                    foreach ($option as $option_name => $option_value) {

                        if (array_key_exists($option_name, self::$options))
                            add_option(self::$prefix . $option_name, $option_value);
                        else
                            $option_not_saved = true; // there is at least one option not in the default list
                    }

                    if (!$option_not_saved)
                        return true;
                    else
                        return false; // there was an error

                } elseif (is_string($option) && $option_value != '') {

                    if (array_key_exists($option, self::$options))
                        return add_option(self::$prefix . $option, $option_value);

                }
            }

            return false;
        }

        /**
         *
         * The update_settings method is used to update the setting/settings of the plugin in options table in the database.
         *
         * @param $option - array / string
         * @param $option_value - optional, mandatory only when $option is a string
         *
         * @return bool
         *
         */
        public static function update_settings($option, $option_value = null)
        {

            if (current_user_can('manage_options')) {

                if (is_array($option) && !empty($option)) {

                    $option_not_updated = false;

                    foreach ($option as $option_name => $option_value) {

                        // set option not saved variable
                        if (array_key_exists($option_name, self::$options))
                            update_option(self::$prefix . $option_name, $option_value);
                        else
                            $option_not_updated = true; // there is at least one option not in the default list
                    }

                    if (!$option_not_updated)
                        return true;
                    else
                        return false; // there was an error

                } elseif (is_string($option) && $option_value !== null) {

                    if (array_key_exists($option, self::$options))
                        return update_option(self::$prefix . $option, $option_value);

                }
            }

            return false;
        }


        /**
         *
         * The delete_settings method is used to delete the setting/settings of the plugin from the options table in the database.
         *
         * @param $option - array / string
         *
         * @return bool
         *
         */
        public static function delete_settings($option)
        {

            if (current_user_can('manage_options')) {

                if (is_array($option) && !empty($option)) {

                    foreach ($option as $option_name => $option_value) {

                        if (array_key_exists($option_name, self::$options))
                            delete_option(self::$prefix . $option_name);
                    }

                    return true;

                } elseif (is_string($option)) {

                    if (array_key_exists($option, self::$options))
                        return delete_option(self::$prefix . $option);

                }
            }
        }


        /**
         *
         * Delete all transients and temporary data when the plugin is deactivated
         *
         * @todo Check delete transients when the plugin is deactivated.
         * @todo What happens with the old WPMP_Tracking_Hash after the plugin is upgraded? Is the option reset at upgrade?
         *
         */
        public static function deactivate()
        {

            // delete tracking hash
            delete_option(self::$transient_prefix.'tracking_hash');

            // remove transients
            foreach (array('whats_new_updates', 'news_updates', 'more_updates', 'premium_config_path', 'tracking_cache') as $transient_name) {

                if (get_transient(self::$transient_prefix.$transient_name) !== false)
                    delete_transient(self::$transient_prefix.$transient_name);
            }

            self::update_settings('allow_tracking', 0);
        }

        /**
         *
         * Delete all options and transients when the plugin is uninstalled
         *
         */
        public static function uninstall()
        {

            // delete plugin settings
            self::delete_settings(self::$options);

            // remove pages settings
            global $wpdb;
            $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '".self::$prefix."page_%'" );
        }
    }
}
<?php

namespace WP2StaticClodui;

class Controller {
    public function run() : void {
        add_filter( 'wp2static_add_menu_items', [ 'WP2StaticClodui\Controller', 'addSubmenuPage' ] );

        add_action(
            'admin_post_wp2static_clodui_save_options',
            [ $this, 'saveOptionsFromUI' ],
            15,
            1
        );

        add_action(
            'wp2static_deploy',
            [ $this, 'deploy' ],
            15,
            2
        );

        add_action(
            'admin_menu',
            [ $this, 'addOptionsPage' ],
            15,
            1
        );

        do_action(
            'wp2static_register_addon',
            'wp2static-addon-clodui',
            'deploy',
            'Clodui Deployment',
            'https://www.clodui.com/wordpress/',
            'Deploys to Clodui'
        );

        if ( defined( 'WP_CLI' ) ) {
            \WP_CLI::add_command(
                'wp2static clodui',
                [ 'WP2StaticClodui\CLI', 'clodui' ]
            );
        }
    }

    /**
     *  Get all add-on options
     *
     *  @return mixed[] All options
     */
    public static function getOptions() : array {
        global $wpdb;
        $options = [];

        $table_name = $wpdb->prefix . 'wp2static_addon_clodui_options';

        $rows = $wpdb->get_results( "SELECT * FROM $table_name" );

        foreach ( $rows as $row ) {
            $options[ $row->name ] = $row;
        }

        return $options;
    }

    /**
     * Seed options
     */
    public static function seedOptions() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_clodui_options';

        $query_string =
            "INSERT IGNORE INTO $table_name (name, value, label, description) VALUES (%s, %s, %s, %s);";

        $query = $wpdb->prepare(
            $query_string,
            'websiteID',
            '',
            'Clodui Website ID',
            'Website id is found in website details page'
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'username',
            '',
            'Clodui Username',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'tokenID',
            '',
            'Clodui Token ID',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'token',
            '',
            'Clodui Token',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'logLevel',
            'INFO',
            'Logging Level',
            ''
        );

        $wpdb->query( $query );
    }

    /**
     * Save options
     *
     * @param mixed $value option value to save
     */
    public static function saveOption( string $name, $value ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_clodui_options';

        $query_string = "INSERT INTO $table_name (name, value) VALUES (%s, %s);";
        $query = $wpdb->prepare( $query_string, $name, $value );

        $wpdb->query( $query );
    }

    public static function renderCloduiPage() : void {
        self::createOptionsTable();
        self::seedOptions();

        $view = [];
        $view['nonce_action'] = 'wp2static-clodui-options';
        $view['uploads_path'] = \WP2Static\SiteInfo::getPath( 'uploads' );
        $clodui_path = \WP2Static\SiteInfo::getPath( 'uploads' ) . 'wp2static-processed-site.clodui';

        $view['options'] = self::getOptions();

        $view['clodui_url'] =
            is_file( $clodui_path ) ?
                \WP2Static\SiteInfo::getUrl( 'uploads' ) . 'wp2static-processed-site.clodui' : '#';

        require_once __DIR__ . '/../views/clodui-page.php';
    }


    public function deploy( string $processed_site_path ) : void {
        // TODO: Error in WP bcz func expecting two params
        // if ( $enabled_deployer !== 'wp2static-addon-clodui' ) {
        //    return;
        // }

        Logger::$log_level = self::getValue('logLevel') ?: 'DEBUG';
        Logger::info('Starting deployment...');

        $clodui_deployer = new Deployer();
        $clodui_deployer->upload_files( $processed_site_path );
    }

    public static function createOptionsTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_clodui_options';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL UNIQUE,
            value VARCHAR(255) NOT NULL,
            label VARCHAR(255) NULL,
            description VARCHAR(255) NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function activate_for_single_site(): void {
        self::createOptionsTable();
        self::seedOptions();
    }

    public static function deactivate_for_single_site() : void {
    }

    public static function deactivate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::deactivate_for_single_site();
            }

            restore_current_blog();
        } else {
            self::deactivate_for_single_site();
        }
    }

    public static function activate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::activate_for_single_site();
            }

            restore_current_blog();
        } else {
            self::activate_for_single_site();
        }
    }

    /**
     * Add WP2Static submenu
     *
     * @param mixed[] $submenu_pages array of submenu pages
     * @return mixed[] array of submenu pages
     */
    public static function addSubmenuPage( array $submenu_pages ) : array {
        $submenu_pages['clodui'] = [ 'WP2StaticClodui\Controller', 'renderCloduiPage' ];

        return $submenu_pages;
    }

    public static function saveOptionsFromUI() : void {
        check_admin_referer( 'wp2static-clodui-options' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_clodui_options';

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['websiteID'] ) ],
            [ 'name' => 'websiteID' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['username'] ) ],
            [ 'name' => 'username' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['tokenID'] ) ],
            [ 'name' => 'tokenID' ]
        );

        $secret_token =
            $_POST['token'] ?
            \WP2Static\CoreOptions::encrypt_decrypt(
                'encrypt',
                sanitize_text_field( $_POST['token'] )
            ) : '';

        $wpdb->update(
            $table_name,
            [ 'value' => $secret_token ],
            [ 'name' => 'token' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['logLevel'] ) ],
            [ 'name' => 'logLevel' ]
        );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-addon-clodui' ) );
        exit;
    }

    /**
     * Get option value
     *
     * @return string option value
     */
    public static function getValue( string $name ) : string {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_clodui_options';

        $sql = $wpdb->prepare(
            "SELECT value FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name
        );

        $option_value = $wpdb->get_var( $sql );

        if ( ! is_string( $option_value ) ) {
            return '';
        }

        return $option_value;
    }

    public function addOptionsPage() : void {
         add_submenu_page(
             null,
             'Clodui Deployment Options',
             'Clodui Deployment Options',
             'manage_options',
             'wp2static-addon-clodui',
             [ $this, 'renderCloduiPage' ]
         );
    }
}


<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Register\Admin;

use SkautisIntegration\Rules\RulesManager;

final class Admin {

    private $rulesManager;
    private $adminDirUrl = '';

    public function __construct( RulesManager $rulesManager ) {
        $this->rulesManager = $rulesManager;
        $this->adminDirUrl  = plugin_dir_url( __FILE__ ) . 'public/';
        ( new Settings( $this->rulesManager ) );
        $this->initHooks();
    }

    private function initHooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueStyles' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
        add_action( 'admin_footer', [ $this, 'initRulesOptions' ] );
        add_action( 'admin_footer', [ $this, 'initRulesData' ] );
    }

    public function enqueueStyles() {
        if ( get_current_screen()->id == 'skautis_page_skautis-integration_modules_register' ) {
            wp_enqueue_style(
                SKAUTISINTEGRATION_NAME . '_modules_register',
                $this->adminDirUrl . 'css/skautis-modules-register-admin.css',
                [],
                SKAUTISINTEGRATION_VERSION,
                'all'
            );
        }
    }

    public function enqueueScripts() {
        if ( get_current_screen()->id == 'skautis_page_skautis-integration_modules_register' ) {
            wp_enqueue_script( 'jquery-ui-sortable' );

            wp_enqueue_script(
                SKAUTISINTEGRATION_NAME . '_jquery.repeater',
                SKAUTISINTEGRATION_URL . 'bundled/jquery.repeater.min.js',
                [ 'jquery' ],
                SKAUTISINTEGRATION_VERSION,
                TRUE
            );

            wp_enqueue_script(
                SKAUTISINTEGRATION_NAME . '_modules_register',
                $this->adminDirUrl . 'js/skautis-modules-register-admin.js',
                [ SKAUTISINTEGRATION_NAME . '_jquery.repeater' ],
                SKAUTISINTEGRATION_VERSION,
                TRUE
            );
        }
    }

    public function initRulesOptions() {
        if ( get_current_screen()->id == 'skautis_page_skautis-integration_modules_register' ) {
            $rules = [];
            foreach ( (array)$this->rulesManager->getAllRules() as $rule ) {
                $rules[ $rule->ID ] = $rule->post_title;
            }
            ?>
            <script>
                window.rulesOptions = <?php echo wp_json_encode( $rules ); ?>;
            </script>
            <?php
        }
    }

    public function initRulesData() {
        if ( get_current_screen()->id == 'skautis_page_skautis-integration_modules_register' ) {
            $data = get_option( SKAUTISINTEGRATION_NAME . '_modules_register_rules' );
            ?>
            <script>
                window.rulesData = <?php echo wp_json_encode( $data ); ?>;
            </script>
            <?php
        }
    }

}

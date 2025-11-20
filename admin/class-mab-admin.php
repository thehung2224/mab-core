<?php
class MaB_Core_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_mab_save_hosters', [ $this, 'save_hosters' ] );
    }

    public function add_menu() {
        add_menu_page(
            __( 'MaB Core', 'mab-core' ),
            __( 'MaB Core', 'mab-core' ),
            'manage_options',
            'mab-core',
            [ $this, 'settings_page' ],
            'dashicons-download',
            58
        );
    }

    public function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_mab-core' ) return;

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_style( 'mab-admin-css', MAB_CORE_URL . 'admin/assets/css/admin-style.css', [], MAB_CORE_VERSION );
        wp_enqueue_script( 'mab-admin-js', MAB_CORE_URL . 'admin/assets/js/admin-script.js', [ 'jquery', 'wp-color-picker' ], MAB_CORE_VERSION, true );
        wp_localize_script( 'mab-admin-js', 'mab_ajax', [
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'mab_nonce' ),
        ] );
    }

    public function settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'download-links';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'MaB Core Settings', 'mab-core' ); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=mab-core&tab=download-links" class="nav-tab <?php echo esc_attr( $active_tab === 'download-links' ? 'nav-tab-active' : '' ); ?>">
                    <?php esc_html_e( 'Download Links', 'mab-core' ); ?>
                </a>
                <!-- Future tabs -->
            </nav>
            <div class="tab-content">
                <?php
                if ( $active_tab === 'download-links' ) {
                    include MAB_CORE_PATH . 'admin/partials/settings-download-links.php';
                }
                ?>
            </div>
        </div>
        <?php
    }

    public function save_hosters() {
        check_ajax_referer( 'mab_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $hosters = isset( $_POST['hosters'] ) ? wp_unslash( $_POST['hosters'] ) : [];
        $sanitized = [];
        $seen_names = [];
        foreach ( $hosters as $hoster ) {
            $name = preg_replace('/^https?:\/\//', '', trim( $hoster['name'] ) ); // Strip https://
            $dead_messages = trim( $hoster['dead_messages'] );
            $lower_name = strtolower( $name );
            if ( isset( $seen_names[$lower_name] ) ) {
                wp_send_json_error( 'Duplicate File hosting: ' . $name );
            }
            $seen_names[$lower_name] = true;
            if ( empty( $name ) || ! preg_match( '/^([a-z0-9-]{1,63}\.)+[a-z]{2,6}$/i', $name ) ) {
                wp_send_json_error( 'Invalid File hosting format: Use abc.xxx' );
            }
            if ( empty( $dead_messages ) || ! preg_match( '/^[a-zA-Z ,-]+$/', $dead_messages ) ) {
                wp_send_json_error( 'Invalid Dead messages: Only letters, commas, spaces, hyphens' );
            }
            $sanitized[] = [
                'name' => sanitize_text_field( $name ),
                'dead_messages' => sanitize_text_field( $dead_messages ),
                'bg_color' => sanitize_hex_color( $hoster['bg_color'] ),
                'text_color' => sanitize_hex_color( $hoster['text_color'] ),
            ];
        }
        update_option( 'mab_hosters', $sanitized );
        wp_send_json_success();
    }
}
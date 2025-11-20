<?php
class MaB_Shortcode_Download {

    public function __construct() {
        add_shortcode( 'mab_downloads', [ $this, 'render' ] );
        add_action( 'wp_ajax_mab_check_links', [ $this, 'check_links' ] );
        add_action( 'wp_ajax_nopriv_mab_check_links', [ $this, 'check_links' ] );
    }

    public function render( $atts ) {
        global $post;
        $links = get_post_meta( $post->ID, '_mab_links', true ) ?: get_option( 'mab_global_links', [] );

        ob_start();
        include MAB_CORE_PATH . 'templates/download-box.php';
        return ob_get_clean();
    }

    public function check_links() {
        check_ajax_referer( 'mab_nonce', 'nonce' );

        $urls = isset( $_POST['urls'] ) ? array_map( 'esc_url_raw', wp_unslash( $_POST['urls'] ) ) : [];
        $results = [];

        foreach ( $urls as $url ) {
            $results[ $url ] = MaB_Link_Checker::is_alive( $url );
        }

        wp_send_json_success( $results );
    }
}
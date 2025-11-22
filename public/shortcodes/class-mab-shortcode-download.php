<?php
class MaB_Shortcode_Download {

    public function __construct() {
        add_shortcode( 'mab_downloads', [ $this, 'render' ] );
    }

    public function render( $atts ) {
        global $post;
        $links = get_post_meta( $post->ID, '_mab_links', true ) ?: get_option( 'mab_global_links', [] );

        ob_start();
        include MAB_CORE_PATH . 'templates/download-box.php';
        return ob_get_clean();
    }
}
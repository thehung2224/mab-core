<?php
/**
 * New helper class for reusable functions.
 */
class MaB_Helpers {

    /**
     * Get normalized hoster domains for matching.
     *
     * @param array $hosters Optional hosters array; defaults to option.
     * @return array Normalized domains.
     */
    public static function get_normalized_domains( $hosters = [] ) {
        if ( empty( $hosters ) ) {
            $hosters = get_option( 'mab_hosters', [] );
        }
        $domains = [];
        foreach ( $hosters as $hoster ) {
            $name_clean = preg_replace( '/^(https?:\/\/|www\.)/i', '', strtolower( trim( $hoster['name'] ) ) );
            $domains[] = strpos( $name_clean, '.' ) === false ? $name_clean . '.com' : $name_clean;
        }
        return $domains;
    }

    /**
     * Get dead phrases for a URL based on matching hoster.
     *
     * @param string $url The URL to match.
     * @return array Dead phrases.
     */
    public static function get_dead_phrases( $url ) {
        $hosters = get_option( 'mab_hosters', [] );
        $dead_phrases = [ 'file not found', 'has been removed', 'file has been deleted', 'no longer available' ];

        foreach ( $hosters as $hoster ) {
            $name_clean = preg_replace( '/^(https?:\/\/|www\.)/i', '', strtolower( trim( $hoster['name'] ) ) );
            $match_domain = strpos( $name_clean, '.' ) === false ? $name_clean . '.com' : $name_clean;

            if ( stripos( $url, $match_domain ) !== false ) {
                return array_map( 'trim', explode( ',', strtolower( trim( $hoster['dead_messages'] ) ) ) );
            }
        }

        return $dead_phrases;
    }
    
    /**
     * Verify AJAX request security (nonce and capability).
     * Sends error and exits if invalid.
     */
    public static function verify_ajax_request() {
        check_ajax_referer( 'mab_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
    }

    /**
     * Save an option and send success response.
     *
     * @param string $option_name The option key.
     * @param mixed  $data        The data to save.
     */
    public static function save_option_and_success( $option_name, $data ) {
        update_option( $option_name, $data );
        wp_send_json_success();
    }    
}
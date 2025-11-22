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

    /**
     * Extract the first external featured image URL from post content matching allowed domains.
     *
     * @param int    $post_id         The post ID.
     * @param array  $allowed_domains Array of allowed domains (cleaned, e.g., ['fastpic.org']).
     * @return string The image URL or empty string if none found.
     */
    public static function get_external_featured_image( $post_id, $allowed_domains = [] ) {
        if ( empty( $allowed_domains ) ) {
            return '';
        }

        $post_content = get_post_field( 'post_content', $post_id );
        if ( empty( $post_content ) ) {
            return '';
        }

        if ( ! preg_match_all( '/https?:\/\/[^\s\)"\']+\.(?:jpg|jpeg|png|gif|webp|bmp|svg)/i', $post_content, $matches ) ) {
            return '';
        }

        $allowed_domains_lower = array_map( 'strtolower', $allowed_domains );

        foreach ( $matches[0] as $img_url ) {
            $parsed = parse_url( $img_url );
            $host = isset( $parsed['host'] ) ? preg_replace( '/^www\./i', '', strtolower( $parsed['host'] ) ) : '';
            foreach ( $allowed_domains_lower as $domain ) {
                if ( substr( $host, -strlen( $domain ) ) === $domain ) {
                    return esc_url( $img_url );
                }
            }
        }

        return '';
    }
}
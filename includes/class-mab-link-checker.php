<?php
class MaB_Link_Checker {

    public static function is_alive( $url ) {
        $cache_key = 'mab_link_' . md5( $url );
        $cached = get_transient( $cache_key );
        if ( $cached !== false ) {
            return $cached;
        }

        $args = [
            'timeout'     => 15,
            'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'headers'     => [ 'Referer' => 'https://google.com' ],
            'sslverify'   => false,
            'redirection' => 5,
        ];

        $response = wp_remote_get( $url, $args );
        if ( is_wp_error( $response ) ) {
            set_transient( $cache_key, false, HOUR_IN_SECONDS );
            return false;
        }

        $body = strtolower( wp_remote_retrieve_body( $response ) );
        $dead_phrases = MaB_Helpers::get_dead_phrases( $url );

        foreach ( $dead_phrases as $phrase ) {
            if ( stripos( $body, $phrase ) !== false ) {
                set_transient( $cache_key, false, HOUR_IN_SECONDS );
                return false;
            }
        }

        set_transient( $cache_key, true, HOUR_IN_SECONDS );
        return true;
    }
}
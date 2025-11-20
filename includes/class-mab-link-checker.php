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
            set_transient( $cache_key, false, 600 );
            return false;
        }

        $body = strtolower( wp_remote_retrieve_body( $response ) );

        $hosters = get_option( 'mab_hosters', [] );
        $dead_phrases = [];

        foreach ( $hosters as $hoster ) {
            $match_domain = strtolower( str_replace( ' ', '', $hoster['name'] ) ) . '.com'; // Derive from name
            if ( strpos( $url, $match_domain ) !== false ) {
                $dead_phrases = array_map( 'trim', explode( ',', strtolower( trim( $hoster['dead_messages'] ) ) ) );
                break;
            }
        }

        if ( empty( $dead_phrases ) ) {
            $dead_phrases = [ 'file not found', 'has been removed', 'file has been deleted', 'no longer available' ];
        }

        foreach ( $dead_phrases as $phrase ) {
            if ( strpos( $body, $phrase ) !== false ) {
                set_transient( $cache_key, false, 600 );
                return false;
            }
        }

        set_transient( $cache_key, true, 600 );
        return true;
    }
}
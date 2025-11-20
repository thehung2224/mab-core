<?php
class MaB_Core_Public {

    public function __construct() {
        add_filter( 'the_content', [ $this, 'auto_link_urls' ], 10 );
        add_filter( 'the_content', [ $this, 'smart_download_heading' ], 90 );
        add_filter( 'the_content', [ $this, 'convert_to_buttons' ], 100 );
        add_action( 'wp_head', [ $this, 'add_dynamic_css' ] );
        add_action( 'wp_footer', [ $this, 'add_status_js' ] );  // Ensure this matches method name
        add_action( 'wp_ajax_mab_check_links', [ $this, 'check_links' ] );
        add_action( 'wp_ajax_nopriv_mab_check_links', [ $this, 'check_links' ] );
        
    }

    public function auto_link_urls( $content ) {
        if ( ! is_singular() || is_admin() ) return $content;

        $keywords = [];
        $hosters = get_option( 'mab_hosters', [] );
        foreach ( $hosters as $hoster ) {
            $name_clean = preg_replace('/^(https?:\/\/|www\.)/i', '', strtolower(trim($hoster['name'])));
            $match_domain = (strpos($name_clean, '.') === false) ? $name_clean . '.com' : $name_clean;
            $keywords[] = $match_domain;
        }
        if ( empty( $keywords ) ) return $content;

        $pattern = '~(?<!["\'])\bhttps?://[^\s<]+~i';

        $content = preg_replace_callback( $pattern, function( $matches ) use ( $keywords ) {
            $url = $matches[0];

            foreach ( $keywords as $keyword ) {
                if ( strpos( $url, $keyword ) !== false ) {
                    $escaped = esc_url( $url );
                    return '<a href="' . $escaped . '" target="_blank">' . $escaped . '</a>';
                }
            }

            return $url;
        }, $content );

        return $content;
    }

    public function smart_download_heading( $content ) {
        if ( ! is_singular() || is_admin() ) return $content;

        return preg_replace_callback( '#(<blockquote[^>]*>)(.*?)(</blockquote>)#is', function( $m ) {
            $blockquote_open = $m[1];
            $inside = $m[2];
            $blockquote_close = $m[3];

            // Check if already has "download links" text
            if ( preg_match( '/download\s+links/i', $inside ) ) {
                return $m[0]; // Skip - already has heading
            }

            // Add heading before first button/link
            $heading = '<h4>Download from free hosting</h4>';
            $inside = $heading . $inside;

            return $blockquote_open . $inside . $blockquote_close;
        }, $content );
    }

    public function convert_to_buttons( $content ) {
        if ( ! is_singular() || is_admin() ) return $content;

        $hosts = [];
        $hosters = get_option( 'mab_hosters', [] );
        foreach ( $hosters as $hoster ) {
            $name_clean = preg_replace('/^(https?:\/\/|www\.)/i', '', strtolower(trim($hoster['name'])));
            $match_domain = (strpos($name_clean, '.') === false) ? $name_clean . '.com' : $name_clean;
            $hosts[] = preg_quote( $match_domain, '#' );
        }
        if ( empty( $hosts ) ) return $content;

        $hosts_pattern = implode( '|', $hosts );
        $post_title = esc_attr( get_the_title() );

        $pattern = '#<a\s+(?:[^>]*?\s+)?href=["\'](https?://(?:[^/]*\.)?(' . $hosts_pattern . ')/[^"\']*)["\'](?:[^>]*)>([^<]+)</a>#i';

        return preg_replace_callback( $pattern, function( $m ) use ( $post_title ) {
            $url = $m[1];

            preg_match( '#//([^/]+)#', $url, $host_match );
            $host = str_replace( 'www.', '', $host_match[1] );
            $host_lower = strtolower( explode( '.', $host )[0] );
            if ( $host_lower === 'DDOWNLOAD' ) $host_lower = 'ddownload';

            $display_name = $host_lower;

            return '<a href="' . esc_url( $url ) . '" 
                       title="Download ' . $post_title . '" 
                       class="download-btn download-btn-' . esc_attr( $host_lower ) . '" 
                       target="_blank" rel="noopener noreferrer">
                <span class="btn-dot"></span> ' . esc_html( $display_name ) . '
            </a>';
        }, $content );
    }

    public function add_dynamic_css() {
        if ( ! is_singular() ) return;

        $hosters = get_option( 'mab_hosters', [] );
        $css = '';
        foreach ( $hosters as $hoster ) {
            $name_clean = preg_replace('/^(https?:\/\/|www\.)/i', '', strtolower(trim($hoster['name'])));
            $host_lower = preg_replace('/\..*$/', '', $name_clean); // Remove TLD for class
            $css .= ".download-btn-{$host_lower} { background: {$hoster['bg_color']}; color: {$hoster['text_color']}; }\n";
        }

        // Base + alive/dead CSS
        $css .= ".download-btn{
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 0.375em;
    border-radius: 0.375em;
    color: #fff;
    transition: all 0.3s;
	line-height: 1.2;
}

.download-btn:hover{
    color: #fff;
}

.btn-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
}
            .download-btn.alive .btn-dot {
                background: #28a745; box-shadow: 0 0 8px #28a745; animation: pulse-green 2s infinite;
            }
            .download-btn.dead .btn-dot { background: #ff0000; }
            @keyframes pulse-green {
                0%, 100% { box-shadow: 0 0 20px #28a745; }
                50% { box-shadow: 0 0 30px #28a745; }
            }
        ";

        echo '<style>' . wp_strip_all_tags( $css ) . '</style>';
    }

    public function add_status_js() {
        if ( ! is_singular() ) return;
        ?>
        <script>
        jQuery(function($) {
            var buttons = $('.download-btn');
            var urls = [];

            buttons.each(function() {
                var url = $(this).attr('href');
                if (urls.indexOf(url) === -1) urls.push(url);
                $(this).addClass('checking');
            });

            if (urls.length === 0) return;

            function checkLinks() {
                $.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
                    action: 'mab_check_links',
                    nonce: '<?php echo wp_create_nonce( "mab_nonce" ); ?>',
                    urls: urls
                }, function(res) {
                    if (res.success) {
                        $.each(res.data, function(url, alive) {
                            var selector = '.download-btn[href="' + url + '"]';
                            $(selector).removeClass('checking alive dead').addClass(alive ? 'alive' : 'dead');
                        });
                    }
                });
            }

            checkLinks();
            setInterval(checkLinks, 60000);
        });
        </script>
        <?php
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
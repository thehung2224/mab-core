<?php
class MaB_Core_Public {

    public function __construct() {
        add_filter( 'the_content', [ $this, 'auto_link_urls' ], 10 );
        add_filter( 'the_content', [ $this, 'smart_download_heading' ], 90 );
        add_filter( 'the_content', [ $this, 'convert_to_buttons' ], 100 );
        add_filter( 'the_content', [ $this, 'append_related_posts' ], 110 );
        add_action( 'wp_head', [ $this, 'add_dynamic_css' ] );
        add_action( 'wp_footer', [ $this, 'add_status_js' ] );
        add_action( 'wp_ajax_mab_check_links', [ $this, 'check_links' ] );
        add_action( 'wp_ajax_nopriv_mab_check_links', [ $this, 'check_links' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
    }

    /**
     * Enqueues public-facing styles.
     */
    public function enqueue_public_assets() {
        wp_enqueue_style( 'mab-public-css', MAB_CORE_URL . 'public/assets/css/public-style.css', [], MAB_CORE_VERSION );
    }

    /**
     * Automatically links plain URLs matching hosters to <a> tags.
     */
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
                    return '<a href="' . $escaped . '" target="_blank" rel="noopener noreferrer">' . $escaped . '</a>';
                }
            }

            return $url;
        }, $content );

        return $content;
    }

    /**
     * Adds a heading to download blockquote if missing "download links" text.
     */
    public function smart_download_heading( $content ) {
        if ( ! is_singular() || is_admin() ) return $content;

        return preg_replace_callback( '#(<blockquote[^>]*>)(.*?)(</blockquote>)#is', function( $m ) {
            $blockquote_open = $m[1];
            $inside = $m[2];
            $blockquote_close = $m[3];

            if ( preg_match( '/download\s+links/i', $inside ) ) {
                return $m[0];
            }

            $heading = '<h4 class="mab-download-heading" style="text-align:center;margin:20px 0 15px;font-size:20px;color:#333;">Download from free hosting</h4>';
            $inside = $heading . $inside;

            return $blockquote_open . $inside . $blockquote_close;
        }, $content );
    }

    /**
     * Converts linked URLs to styled buttons with title attribute.
     */
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

    /**
     * Adds dynamic CSS for buttons to wp_head.
     */
    public function add_dynamic_css() {
        if ( ! is_singular() ) return;

        $hosters = get_option( 'mab_hosters', [] );
        $css = '';
        foreach ( $hosters as $hoster ) {
            $name_clean = preg_replace('/^(https?:\/\/|www\.)/i', '', strtolower(trim($hoster['name'])));
            $host_lower = preg_replace('/\..*$/', '', $name_clean);
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

    /**
     * Adds JS for link status checking to wp_footer.
     */
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

    /**
     * Handles AJAX for link status checking.
     */
    public function check_links() {
        check_ajax_referer( 'mab_nonce', 'nonce' );

        $urls = isset( $_POST['urls'] ) ? array_map( 'esc_url_raw', wp_unslash( $_POST['urls'] ) ) : [];
        $results = [];

        foreach ( $urls as $url ) {
            $results[ $url ] = MaB_Link_Checker::is_alive( $url );
        }

        wp_send_json_success( $results );
    }
    
    /**
     * Appends related posts grid to single post content if enabled.
     *
     * @param string $content The post content.
     * @return string Modified content with related posts grid.
     */
    public function append_related_posts( $content ) {
        if ( ! is_singular( 'post' ) || is_admin() ) {
            return $content;
        }
    
        $settings = get_option( 'mab_posts_related_settings', [
            'all'              => true,
            'categories'       => [],
            'custom_heading'   => [],
            'placeholder_image'=> []
        ] );
    
        $current_post_cats = wp_get_post_categories( get_the_ID(), [ 'fields' => 'ids' ] );
    
        // Determine if related posts should be shown
        $show_related = $settings['all'] || array_intersect( $current_post_cats, (array)$settings['categories'] );
    
        if ( ! $show_related ) {
            return $content;
        }
    
        // Query related posts
        $related_query = new WP_Query( [
            'category__in'   => $current_post_cats,
            'post__not_in'   => [ get_the_ID() ],
            'posts_per_page' => 4,
            'orderby'        => 'rand',
        ] );
    
        if ( ! $related_query->have_posts() ) {
            return $content;
        }
    
        // Determine heading and placeholder based on current post's categories
        $heading     = __( 'Related Posts', 'mab-core' );
        $placeholder = '';
    
        foreach ( $current_post_cats as $cat_id ) {
            if ( ! empty( $settings['custom_heading'][$cat_id] ) ) {
                $heading = esc_html( $settings['custom_heading'][$cat_id] );
            }
            if ( ! empty( $settings['placeholder_image'][$cat_id] ) ) {
                $placeholder = esc_url( $settings['placeholder_image'][$cat_id] );
                // You can remove `break;` if you want last matching category to win
            }
        }
    
        ob_start();
        ?>
        <div class="mab-related-posts">
            <h5><?php echo $heading; ?></h5>
            <div class="mab-related-grid">
                <?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
                    <div class="mab-related-item">
                        <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium', [ 'loading' => 'lazy' ] ); ?>
                            <?php elseif ( $placeholder ) : ?>
                                <img src="<?php echo $placeholder; ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                            <?php else : ?>
                                <div style="background:#f0f0f0;width:100%;height:180px;display:flex;align-items:center;justify-content:center;color:#999;font-size:14px;">
                                    <?php esc_html_e( 'No Image', 'mab-core' ); ?>
                                </div>
                            <?php endif; ?>
                        </a>
                        <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    
        return $content . ob_get_clean();
    }
}
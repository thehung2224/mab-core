<?php
class MaB_Core_Public {

    public function __construct() {
        add_filter( 'the_content', [ $this, 'auto_link_urls' ], 10 );
        add_filter( 'the_content', [ $this, 'smart_download_heading' ], 90 );
        add_filter( 'the_content', [ $this, 'convert_to_buttons' ], 100 );
        add_filter( 'the_content', [ $this, 'append_related_posts' ], 110 );
        add_action( 'wp_head', [ $this, 'add_dynamic_css' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
        add_action( 'wp_ajax_mab_check_links', [ $this, 'check_links' ] );
        add_action( 'wp_ajax_nopriv_mab_check_links', [ $this, 'check_links' ] );
    }

    public function enqueue_public_assets() {
        wp_enqueue_style( 'mab-public-css', MAB_CORE_URL . 'public/assets/css/public-style.css', [], MAB_CORE_VERSION );
        wp_enqueue_script( 'mab-public-js', MAB_CORE_URL . 'public/assets/js/public-script.js', [ 'jquery' ], MAB_CORE_VERSION, true );
        wp_localize_script( 'mab-public-js', 'mab_ajax', [
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'mab_nonce' ),
        ] );
    }

    public function auto_link_urls( $content ) {
        if ( ! is_singular() || is_admin() ) return $content;

        $domains = MaB_Helpers::get_normalized_domains();
        if ( empty( $domains ) ) return $content;

        $pattern = '~(?<!["\'])\bhttps?://[^\s<]+~i';

        $content = preg_replace_callback( $pattern, function( $matches ) use ( $domains ) {
            $url = $matches[0];

            foreach ( $domains as $domain ) {
                if ( stripos( $url, $domain ) !== false ) {
                    $escaped = esc_url( $url );
                    return '<a href="' . $escaped . '" target="_blank" rel="noopener noreferrer">' . $escaped . '</a>';
                }
            }

            return $url;
        }, $content );

        return $content;
    }

    public function smart_download_heading( $content ) {
        if ( ! is_singular() || is_admin() ) return $content;

        return preg_replace_callback( '#(<blockquote[^>]*>)(.*?)(</blockquote>)#is', function( $m ) {
            $inside = $m[2];

            if ( preg_match( '/download\s+links/i', $inside ) ) {
                return $m[0];
            }

            $heading = '<h4 class="mab-download-heading" style="text-align:center;margin:20px 0 15px;font-size:20px;color:#333;">Download from free hosting</h4>';
            return $m[1] . $heading . $inside . $m[3];
        }, $content );
    }

    public function convert_to_buttons( $content ) {
        if ( ! is_singular() || is_admin() ) return $content;

        $domains = MaB_Helpers::get_normalized_domains();
        if ( empty( $domains ) ) return $content;

        $hosts_pattern = implode( '|', array_map( 'preg_quote', $domains ) );
        $post_title = esc_attr( get_the_title() );

        $pattern = '#<a\s+(?:[^>]*?\s+)?href=["\'](https?://(?:[^/]*\.)?(' . $hosts_pattern . ')/[^"\']*)["\'](?:[^>]*)>([^<]+)</a>#i';

        return preg_replace_callback( $pattern, function( $m ) use ( $post_title ) {
            $url = $m[1];

            preg_match( '#//([^/]+)#', $url, $host_match );
            $host = str_replace( 'www.', '', $host_match[1] );
            $host_lower = strtolower( explode( '.', $host )[0] );
            if ( $host_lower === 'ddownload' ) $host_lower = 'ddownload';

            return '<a href="' . esc_url( $url ) . '" 
                       title="Download ' . $post_title . '" 
                       class="download-btn download-btn-' . esc_attr( $host_lower ) . '" 
                       target="_blank" rel="noopener noreferrer">
                <span class="btn-dot"></span> ' . esc_html( $host_lower ) . '
            </a>';
        }, $content );
    }

    public function add_dynamic_css() {
        if ( ! is_singular() ) return;

        $hosters = get_option( 'mab_hosters', [] );
        $css = '';
        foreach ( $hosters as $hoster ) {
            $name_clean = preg_replace( '/^(https?:\/\/|www\.)/i', '', strtolower( trim( $hoster['name'] ) ) );
            $host_lower = preg_replace( '/\..*$/', '', $name_clean );
            $css .= ".download-btn-{$host_lower} { background: {$hoster['bg_color']}; color: {$hoster['text_color']}; }\n";
        }

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

    public function check_links() {
        check_ajax_referer( 'mab_nonce', 'nonce' );

        $urls = isset( $_POST['urls'] ) ? array_map( 'esc_url_raw', (array) wp_unslash( $_POST['urls'] ) ) : [];
        $results = [];

        foreach ( array_unique( $urls ) as $url ) {
            $results[ $url ] = MaB_Link_Checker::is_alive( $url );
        }

        wp_send_json_success( $results );
    }
    
    public function append_related_posts( $content ) {
        if ( ! is_singular( 'post' ) || is_admin() ) return $content;
    
        $settings = get_option( 'mab_posts_related_settings', [
            'all'              => true,
            'categories'       => [],
            'custom_heading'   => [],
            'placeholder_image'=> []
        ] );
    
        $current_post_cats = wp_get_post_categories( get_the_ID(), [ 'fields' => 'ids' ] );
    
        $show_related = $settings['all'] || array_intersect( $current_post_cats, (array) $settings['categories'] );
    
        if ( ! $show_related ) return $content;
    
        $related_query = new WP_Query( [
            'category__in'   => $current_post_cats,
            'post__not_in'   => [ get_the_ID() ],
            'posts_per_page' => 4,
            'orderby'        => 'rand',
        ] );
    
        if ( ! $related_query->have_posts() ) return $content;
    
        $heading     = __( 'Related Posts', 'mab-core' );
        $placeholder = '';
    
        foreach ( $current_post_cats as $cat_id ) {
            if ( ! empty( $settings['custom_heading'][$cat_id] ) ) $heading = esc_html( $settings['custom_heading'][$cat_id] );
            if ( ! empty( $settings['placeholder_image'][$cat_id] ) ) $placeholder = esc_url( $settings['placeholder_image'][$cat_id] );
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
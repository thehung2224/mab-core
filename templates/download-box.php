<div class="mab-download-box">
    <h3><?php esc_html_e( 'Download from Free File Storage', 'mab-core' ); ?></h3>
    <div class="mab-buttons">
        <?php foreach ( $links as $link ) : ?>
            <a href="<?php echo esc_url( $link['url'] ); ?>" class="mab-btn" data-url="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="nofollow noopener">
                <span class="mab-dot"></span> <?php echo esc_html( $link['name'] ); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
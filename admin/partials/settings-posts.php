<h2><?php esc_html_e( 'Posts Settings', 'mab-core' ); ?></h2>
<p><?php esc_html_e( 'Configure general settings applied to all posts.', 'mab-core' ); ?></p>

<table class="form-table">
    <tr>
        <th scope="row"><?php esc_html_e( 'Custom Image Hosting', 'mab-core' ); ?></th>
        <td>
            <label>
                <input type="checkbox" id="mab_enable_external_images" name="mab_enable_external_images" <?php echo isset( $enable_checked ) ? $enable_checked : ''; ?>>
                <?php esc_html_e( 'Enable custom image domains', 'mab-core' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'This option helps the plugin recognize custom image hosting domains used in posts. Default: fastpic.org', 'mab-core' ); ?></p>

            <div id="mab-external-domains" style="display: <?php echo ( isset( $settings['enable_external_images'] ) && $settings['enable_external_images'] ) ? 'block' : 'none'; ?>; margin-top: 10px;">
                <label for="mab_external_image_domains"><?php esc_html_e( 'Custom domains (one per line, max 5):', 'mab-core' ); ?></label><br>
                <textarea id="mab_external_image_domains" name="mab_external_image_domains" rows="5" cols="50"><?php echo esc_textarea( implode( "\n", (array) ( $settings['external_image_domains'] ?? [] ) ) ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Enter raw or full domains (e.g., example.com). These will be used in addition to the default.', 'mab-core' ); ?></p>
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e( 'Related Posts', 'mab-core' ); ?></th>
        <td>
            <label>
                <input type="checkbox" id="mab_related_all" name="mab_related_all" <?php echo isset( $all_checked ) ? $all_checked : ''; ?>>
                <?php esc_html_e( 'Enable for all categories', 'mab-core' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'If checked, related posts will show on all categories. Uncheck to select specific ones.', 'mab-core' ); ?></p>

            <div id="mab-category-list" style="display: <?php echo ( isset( $settings['all'] ) && ! $settings['all'] ) ? 'block' : 'none'; ?>; margin-top: 10px;">
                <?php 
                $categories = get_categories( [ 'hide_empty' => false ] );
                if ( is_array( $categories ) ) {
                    foreach ( $categories as $cat ) : ?>
                        <div class="mab-category-item">
                            <label>
                                <input type="checkbox" name="mab_related_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, (array) ( $settings['categories'] ?? [] ) ) ); ?> class="mab-category-checkbox">
                                <?php echo esc_html( $cat->name ); ?>
                            </label>
                            <div class="mab-category-options" style="display: none; margin-left: 20px; margin-top: 5px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                                <label>
                                    <?php esc_html_e( 'Custom Heading (optional):', 'mab-core' ); ?>
                                    <input type="text" name="mab_custom_heading[<?php echo esc_attr( $cat->term_id ); ?>]" value="<?php echo esc_attr( $settings['custom_heading'][$cat->term_id] ?? '' ); ?>" placeholder="e.g., Similar Music">
                                </label>
                                <p class="description"><?php esc_html_e( 'Custom heading for related posts in this category.', 'mab-core' ); ?></p>
                
                                <label>
                                    <?php esc_html_e( 'Default Placeholder Image (optional):', 'mab-core' ); ?>
                                    <input type="url" name="mab_placeholder_image[<?php echo esc_attr( $cat->term_id ); ?>]" value="<?php echo esc_attr( $settings['placeholder_image'][$cat->term_id] ?? '' ); ?>" placeholder="https://example.com/placeholder.jpg">
                                    <button type="button" class="button mab-upload-button"><?php esc_html_e( 'Upload Image', 'mab-core' ); ?></button>
                                </label>
                                <p class="description"><?php esc_html_e( 'Fallback image for posts without featured image in this category.', 'mab-core' ); ?></p>
                            </div>
                        </div>
                    <?php endforeach; 
                } ?>
                <p class="description"><?php esc_html_e( 'Select categories where related posts should appear.', 'mab-core' ); ?></p>
            </div>
            <p style="margin-top: 20px;">
                <button type="button" class="button button-primary" id="mab-save-posts"><?php esc_html_e( 'Save Posts Settings', 'mab-core' ); ?></button>
            </p>
            <p id="mab-message-posts"></p>
        </td>
    </tr>
</table>
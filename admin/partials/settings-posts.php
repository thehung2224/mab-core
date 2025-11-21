<h2><?php esc_html_e( 'Posts Settings', 'mab-core' ); ?></h2>
<p><?php esc_html_e( 'Configure general settings applied to all posts.', 'mab-core' ); ?></p>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="mab_related_all"><?php esc_html_e( 'Related Posts', 'mab-core' ); ?></label>
        </th>
        <td>
            <form id="mab-posts-form">
                <?php
                $settings = get_option( 'mab_posts_related_settings', [ 'all' => true, 'categories' => [] ] );
                $all_checked = $settings['all'] ? 'checked' : '';
                $categories = get_categories( [ 'hide_empty' => false ] );
                ?>
                <label>
                    <input type="checkbox" id="mab_related_all" name="mab_related_all" <?php echo $all_checked; ?>>
                    <?php esc_html_e( 'Enable for all categories', 'mab-core' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'If checked, related posts will show on all categories. Uncheck to select specific ones.', 'mab-core' ); ?></p>

                <div id="mab-category-list" style="display: <?php echo $settings['all'] ? 'none' : 'block'; ?>; margin-top: 10px;">
                    <?php foreach ( $categories as $cat ) : ?>
                        <div class="mab-category-item">
                            <label>
                                <input type="checkbox" name="mab_related_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $settings['categories'] ) ); ?> class="mab-category-checkbox">
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
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e( 'Select categories where related posts should appear.', 'mab-core' ); ?></p>
                </div>
                <p style="margin-top: 20px;">
                    <button type="button" class="button button-primary" id="mab-save-posts"><?php esc_html_e( 'Save Posts Settings', 'mab-core' ); ?></button>
                </p>
            </form>
            <p id="mab-message-posts"></p>
        </td>
    </tr>
    <!-- Future rows here -->
</table>
<?php
$hosters = get_option( 'mab_hosters', [] );
?>
<h2><?php esc_html_e( 'Hosters Configuration', 'mab-core' ); ?></h2>
<p><?php esc_html_e( 'Configure file hostings, dead detection messages (comma-separated), and colors. Plugin auto-processes matching URLs in post content.', 'mab-core' ); ?></p>

<table class="wp-list-table widefat fixed striped mab-table">
    <thead>
        <tr>
            <th><?php esc_html_e( 'File hosting', 'mab-core' ); ?></th>
            <th><?php esc_html_e( 'Dead messages', 'mab-core' ); ?></th>
            <th><?php esc_html_e( 'Background Color', 'mab-core' ); ?></th>
            <th><?php esc_html_e( 'Text Colors', 'mab-core' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'mab-core' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( empty( $hosters ) ) : ?>
            <tr><td colspan="5"><?php esc_html_e( 'No hosters configured.', 'mab-core' ); ?></td></tr>
        <?php else : ?>
            <?php foreach ( $hosters as $index => $hoster ) : ?>
                <tr>
                    <td>
                        <div class="mab-input-wrapper">
                            <input type="text" class="mab-required" data-label="File hosting" required pattern="(https?://)?([a-z0-9-]{1,63}\.)+[a-z]{2,6}" title="Enter valid domain like abc.xxx or https://abc.xxx" name="mab_hosters[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $hoster['name'] ); ?>">
                            <span class="required-star">*</span>
                        </div>
                    </td>
                    <td>
                        <div class="mab-input-wrapper">
                            <input type="text" class="mab-required" data-label="Dead messages" required pattern="^[a-zA-Z ,-]+$" title="Only letters, commas, spaces, hyphens" name="mab_hosters[<?php echo esc_attr( $index ); ?>][dead_messages]" value="<?php echo esc_attr( $hoster['dead_messages'] ); ?>" placeholder="not found,removed">
                            <span class="required-star">*</span>
                        </div>
                    </td>
                    <td><input type="text" class="color-picker" name="mab_hosters[<?php echo esc_attr( $index ); ?>][bg_color]" value="<?php echo esc_attr( $hoster['bg_color'] ); ?>"></td>
                    <td><input type="text" class="color-picker" name="mab_hosters[<?php echo esc_attr( $index ); ?>][text_color]" value="<?php echo esc_attr( $hoster['text_color'] ); ?>"></td>
                    <td><button type="button" class="button button-secondary mab-remove-row"><?php esc_html_e( 'Remove', 'mab-core' ); ?></button></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<p>
    <button type="button" class="button button-primary mab-add-row"><?php esc_html_e( 'Add Hoster', 'mab-core' ); ?></button>
    <button type="button" class="button button-primary mab-save-hosters" style="margin-left:10px;"><?php esc_html_e( 'Save Changes', 'mab-core' ); ?></button>
</p>
<p id="mab-message"></p>
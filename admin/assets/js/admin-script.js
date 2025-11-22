// admin-script.js: Handles admin UI interactions for MaB Core plugin settings

jQuery( document ).ready( function( $ ) {
    // Initialize color pickers on page load
    $( '.color-picker' ).wpColorPicker();

    // Function to toggle * visibility for required fields
    function toggleRequiredStar(input) {
        const wrapper = input.closest('.mab-input-wrapper');
        const star = wrapper.querySelector('.required-star');
        const value = input.value.trim();

        star.style.display = (value && input.checkValidity()) ? 'none' : 'inline';
    }

    // Initial check on all required inputs
    document.querySelectorAll('.mab-required').forEach(input => toggleRequiredStar(input));

    // Live monitoring for input changes
    document.addEventListener('input', e => {
        if (e.target.classList.contains('mab-required')) toggleRequiredStar(e.target);
    });

    // Add new row to hosters table
    $( '.mab-add-row' ).on( 'click', function() {
        const index = $( '.mab-table tbody tr' ).length;
        const rowHTML = `
            <tr>
                <td>
                    <div class="mab-input-wrapper">
                        <input type="text" class="mab-required" required pattern="(https?://)?([a-z0-9-]{1,63}\.)+[a-z]{2,6}" title="Enter valid domain like abc.xxx or https://abc.xxx" name="mab_hosters[${index}][name]" placeholder="NitroFlare">
                        <span class="required-star">*</span>
                    </div>
                </td>
                <td>
                    <div class="mab-input-wrapper">
                        <input type="text" class="mab-required" required pattern="^[a-zA-Z ,-]+$|^$" title="Only letters, commas, spaces, hyphens" name="mab_hosters[${index}][dead_messages]" placeholder="not found,removed">
                        <span class="required-star">*</span>
                    </div>
                </td>
                <td><input type="text" class="color-picker" name="mab_hosters[${index}][bg_color]" value="#ffffff"></td>
                <td><input type="text" class="color-picker" name="mab_hosters[${index}][text_color]" value="#000000"></td>
                <td><button type="button" class="button button-secondary mab-remove-row">Remove</button></td>
            </tr>
        `;
        const row = $.parseHTML(rowHTML);
        $( '.mab-table tbody' ).append( row );
        $( row ).find( '.color-picker' ).wpColorPicker();
        $( row ).find( '.mab-required' ).each( function() { toggleRequiredStar(this); } );
    } );

    // Remove table row
    $( document ).on( 'click', '.mab-remove-row', function() {
        $( this ).closest( 'tr' ).remove();
    } );

    // Save hosters settings
    $( '.mab-save-hosters' ).on( 'click', function() {
        const messageEl = $('#mab-message').text('').removeClass('success error');

        let valid = true;
        $( '.mab-table input[required]' ).each(function() {
            if (!this.checkValidity()) {
                messageEl.text('Please check settings: ' + this.title).addClass('error').css('color', 'red');
                valid = false;
                return false;
            }
        });
        if (!valid) return;

        const hosters = [];
        $( '.mab-table tbody tr' ).each( function() {
            const name = $( this ).find( 'input[name*="[name]"]' ).val();
            const dead_messages = $( this ).find( 'input[name*="[dead_messages]"]' ).val();
            const bg_color = $( this ).find( 'input[name*="[bg_color]"]' ).val();
            const text_color = $( this ).find( 'input[name*="[text_color]"]' ).val();
            if ( name && dead_messages ) {
                hosters.push( { name, dead_messages, bg_color, text_color } );
            }
        } );

        $.post( mab_ajax.url, {
            action: 'mab_save_hosters',
            nonce: mab_ajax.nonce,
            hosters: hosters
        }, function( res ) {
            messageEl.text(res.success ? 'Saved!' : 'Please check settings: ' + (res.data || 'Error saving'))
                .addClass(res.success ? 'success' : 'error')
                .css('color', res.success ? 'green' : 'red');
        } );
    } );

    // Toggle external domains textarea
    $('input[name="mab_enable_external_images"]').on('change', function() {
        $('#mab-external-domains').toggle(this.checked);
    });

    // Toggle category list in Posts tab
    $('input[name="mab_related_all"]').on('change', function() {
        $('#mab-category-list').toggle( !this.checked );
    });

    // Toggle category options on check
    $(document).on('change', '.mab-category-checkbox', function() {
        $(this).closest('.mab-category-item').find('.mab-category-options').toggle(this.checked);
    });

    // Initial toggle for checked categories
    $('.mab-category-checkbox:checked').each(function() {
        $(this).closest('.mab-category-item').find('.mab-category-options').show();
    });

    // Media uploader for placeholder image
    $(document).on('click', '.mab-upload-button', function(e) {
        e.preventDefault();
        const button = $(this);
        const input = button.prev('input');
        const uploader = wp.media({
            title: 'Select Placeholder Image',
            button: { text: 'Use this image' },
            multiple: false
        }).on('select', function() {
            const attachment = uploader.state().get('selection').first().toJSON();
            input.val(attachment.url);
        }).open();
    });

    // Save Posts settings
    $('#mab-save-posts').on('click', function() {
        const messageEl = $('#mab-message-posts').text('').removeClass('success error');

        const allChecked = $('input[name="mab_related_all"]').is(':checked');
        const categoriesChecked = $('input[name="mab_related_categories[]"]:checked').length;

        if (!allChecked && categoriesChecked === 0) {
            messageEl.text('Please select at least 1 category.').addClass('error').css('color', 'red');
            return;
        }

        const custom_heading = {};
        $('input[name^="mab_custom_heading"]').each(function() {
            const cat_id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            custom_heading[cat_id] = $(this).val();
        });
        
        const placeholder_image = {};
        $('input[name^="mab_placeholder_image"]').each(function() {
            const match = $(this).attr('name').match(/\[(\d+)\]/);
            if (match) placeholder_image[match[1]] = $(this).val().trim();
        }); 

        const enable_external = $('input[name="mab_enable_external_images"]').is(':checked');
        const external_domains_raw = enable_external ? $('textarea[name="mab_external_image_domains"]').val() : '';

        const data = {
            action: 'mab_save_posts_settings',
            nonce: mab_ajax.nonce,
            all: allChecked ? 1 : 0,
            categories: $('input[name="mab_related_categories[]"]:checked').map(function() { return this.value; }).get(),
            custom_heading: custom_heading,
            placeholder_image: placeholder_image,
            enable_external_images: enable_external ? 1 : 0,
            external_domains: external_domains_raw
        };

        $.post(mab_ajax.url, data, function(res) {
            messageEl.text(res.success ? 'Saved!' : 'Error: ' + res.data)
                .addClass(res.success ? 'success' : 'error')
                .css('color', res.success ? 'green' : 'red');
        });
    });
});
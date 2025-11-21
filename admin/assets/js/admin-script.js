// admin-script.js: Handles admin UI interactions for MaB Core plugin settings
// - Manages dynamic elements like adding/removing rows in tables
// - Validates forms (e.g., required fields, patterns)
// - Saves settings via AJAX (e.g., for Download Links or Posts tabs)
// - Toggles UI elements (e.g., show/hide category lists)
// - Initializes color pickers for dynamic rows

jQuery( document ).ready( function( $ ) {
    // Initialize color pickers on page load
    // Related: Allows users to select colors for hoster buttons in Download Links tab
    $( '.color-picker' ).wpColorPicker();

    // Function to toggle * visibility for required fields
    // Related: Improves UX by showing/hiding required indicators dynamically
    // - Checks if field is empty/invalid → shows *
    // - Hides * when filled validly
    function toggleRequiredStar(input) {
        var wrapper = input.closest('.mab-input-wrapper');
        var star = wrapper.find('.required-star');
        var value = input.val().trim();

        // Hide * if field has valid content OR it's a pre-filled default
        if (value !== '' && input[0].checkValidity()) {
            star.hide();
        } else {
            star.show();
        }
    }

    // Initial check on all required inputs on page load
    // Related: Ensures default/pre-filled rows (e.g., from activator defaults) don't show * unnecessarily
    $('.mab-required').each(function() {
        toggleRequiredStar($(this));
    });

    // Live monitoring for input changes on required fields
    // Related: Provides real-time feedback as user types/deletes
    $(document).on('input change keyup paste', '.mab-required', function() {
        toggleRequiredStar($(this));
    });

    // Add new row to hosters table (for Download Links tab)
    // Related: Allows adding new hosters dynamically without page reload
    $( '.mab-add-row' ).on( 'click', function() {
        var index = $( '.mab-table tbody tr' ).length;
        var rowHTML = `
            <tr>
                <td>
                    <div class="mab-input-wrapper">
                        <input type="text" class="mab-required" data-label="File hosting" required pattern="(https?://)?([a-z0-9-]{1,63}\.)+[a-z]{2,6}" title="Enter valid domain like abc.xxx or https://abc.xxx" name="mab_hosters[${index}][name]" placeholder="NitroFlare">
                        <span class="required-star">*</span>
                    </div>
                </td>
                <td>
                    <div class="mab-input-wrapper">
                        <input type="text" class="mab-required" data-label="Dead messages" required pattern="^[a-zA-Z ,-]+$|^$" title="Only letters, commas, spaces, hyphens; no domains/numbers/special chars" name="mab_hosters[${index}][dead_messages]" placeholder="not found,removed">
                        <span class="required-star">*</span>
                    </div>
                </td>
                <td><input type="text" class="color-picker" name="mab_hosters[${index}][bg_color]" value="#ffffff"></td>
                <td><input type="text" class="color-picker" name="mab_hosters[${index}][text_color]" value="#000000"></td>
                <td><button type="button" class="button button-secondary mab-remove-row">Remove</button></td>
            </tr>
        `;
        var row = $.parseHTML(rowHTML);
        $( '.mab-table tbody' ).append( row );
        $( row ).find( '.color-picker' ).wpColorPicker(); // Init color picker for new row
        $( row ).find( '.mab-required' ).trigger('input'); // Initial * toggle for new row
    } );

    // Remove table row
    // Related: Cleans up UI by allowing deletion of added hosters
    $( document ).on( 'click', '.mab-remove-row', function() {
        $( this ).closest( 'tr' ).remove();
    } );

    // Save hosters settings (for Download Links tab)
    // Related: Validates required fields, sends AJAX to save_hosters, shows success/error messages
    $( '.mab-save-hosters' ).on( 'click', function() {
        var valid = true;
        var messageEl = $('#mab-message');
        messageEl.text('').removeClass('success error');

        $( '.mab-table input[required]' ).each(function() {
            if (!this.checkValidity()) {
                messageEl.text('Please check settings: ' + this.title).addClass('error').css('color', 'red');
                valid = false;
                return false;
            }
        });
        if (!valid) return;

        var hosters = [];
        $( '.mab-table tbody tr' ).each( function() {
            var name = $( this ).find( 'input[name*="[name]"]' ).val();
            var dead_messages = $( this ).find( 'input[name*="[dead_messages]"]' ).val();
            var bg_color = $( this ).find( 'input[name*="[bg_color]"]' ).val();
            var text_color = $( this ).find( 'input[name*="[text_color]"]' ).val();
            if ( name && dead_messages ) {
                hosters.push( { name, dead_messages, bg_color, text_color } );
            }
        } );

        $.post( mab_ajax.url, {
            action: 'mab_save_hosters',
            nonce: mab_ajax.nonce,
            hosters: hosters
        }, function( res ) {
            if ( res.success ) {
                messageEl.text('Saved!').addClass('success').css('color', 'green');
            } else {
                messageEl.text('Please check settings: ' + (res.data || 'Error saving')).addClass('error').css('color', 'red');
            }
        } );
    } );

    // Toggle category list in Posts tab (Related Posts section)
    // Related: Hides/shows specific category checkboxes based on "all categories" toggle
    $('input[name="mab_related_all"]').on('change', function() {
        $('#mab-category-list').toggle( !this.checked );
    });

    // Toggle category options on check (for Posts tab)
    // Related: Shows/hides custom heading/image fields when category checkbox checked
    $(document).on('change', '.mab-category-checkbox', function() {
        var options = $(this).closest('.mab-category-item').find('.mab-category-options');
        options.toggle(this.checked);
    });

    // Initial toggle on load for checked categories
    $('.mab-category-checkbox:checked').each(function() {
        $(this).closest('.mab-category-item').find('.mab-category-options').show();
    });

    // Media uploader for placeholder image (reuses WP media library)
    // Related: Opens WP uploader when "Upload Image" clicked; sets URL to input
    $(document).on('click', '.mab-upload-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var input = button.prev('input');
        var uploader = wp.media({
            title: 'Select Placeholder Image',
            button: { text: 'Use this image' },
            multiple: false
        }).on('select', function() {
            var attachment = uploader.state().get('selection').first().toJSON();
            input.val(attachment.url);
        }).open();
    });

    // Save Posts settings (for Posts tab)
    // Related: Sends AJAX to save_posts_settings with related posts options
    $('#mab-save-posts').on('click', function() {
        var messageEl = $('#mab-message-posts').text('').removeClass('success error'); // Reuse pattern

        var allChecked = $('input[name="mab_related_all"]').is(':checked');
        var categoriesChecked = $('input[name="mab_related_categories[]"]:checked').length;

        // If "all" unchecked and no categories selected, show the error
        if (!allChecked && categoriesChecked === 0) {
            messageEl.text('Please select at least 1 category.').addClass('error').css('color', 'red');
            return; // Prevent save
        }

        // Collect custom headings
        var custom_heading = {};
        $('input[name^="mab_custom_heading"]').each(function() {
            var cat_id = $(this).attr('name').match(/\[(.*?)\]/)[1];
            custom_heading[cat_id] = $(this).val();
        });
        
        // Collect placeholder images
        var placeholder_image = {};
        $('input[name^="mab_placeholder_image"]').each(function() {
            var match = $(this).attr('name').match(/\[(\d+)\]/);
            if (match) placeholder_image[match[1]] = $(this).val().trim();
        });      

        var data = {
            action: 'mab_save_posts_settings',
            nonce: mab_ajax.nonce,
            all: allChecked ? 1 : 0,
            categories: $('input[name="mab_related_categories[]"]:checked').map(function() { return this.value; }).get(),
            custom_heading: custom_heading,
            placeholder_image: placeholder_image
        };

        $.post(mab_ajax.url, data, function(res) {
            if (res.success) {
                messageEl.text('Saved!').addClass('success').css('color', 'green');
            } else {
                messageEl.text('Error: ' + res.data).addClass('error').css('color', 'red');
            }
        });
    });
});
jQuery(document).ready(function($) {
    // Color picker
    $('.color-picker').wpColorPicker();

    // Function to toggle * visibility
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

    // Initial check on all inputs
    $('.mab-required').each(function() {
        toggleRequiredStar($(this));
    });

    // Live check on input/change
    $(document).on('input change keyup paste', '.mab-required', function() {
        toggleRequiredStar($(this));
    });

    // Add new row
    $('.mab-add-row').on('click', function() {
        var index = $('.mab-table tbody tr').length;
        var rowHTML = `
            <tr>
                <td>
                    <div class="mab-input-wrapper">
                        <input type="text" class="mab-required" data-label="File hosting" required pattern="(https?://)?([a-z0-9-]{1,63}\.)+[a-z]{2,6}" title="Enter valid domain like abc.xxx or https://abc.xxx" name="mab_hosters[${index}][name]" placeholder="example.com">
                        <span class="required-star">*</span>
                    </div>
                </td>
                <td>
                    <div class="mab-input-wrapper">
                        <input type="text" class="mab-required" data-label="Dead messages" required pattern="^[a-zA-Z ,-]+$"
                           title="Only letters, commas, spaces, hyphens" name="mab_hosters[${index}][dead_messages]" placeholder="not found,removed">
                        <span class="required-star">*</span>
                    </div>
                </td>
                <td><input type="text" class="color-picker" name="mab_hosters[${index}][bg_color]" value="#006ca2"></td>
                <td><input type="text" class="color-picker" name="mab_hosters[${index}][text_color]" value="#ffffff"></td>
                <td><button type="button" class="button button-secondary mab-remove-row">Remove</button></td>
            </tr>
        `;
        var row = $.parseHTML(rowHTML);
        $('.mab-table tbody').append(row);
        $(row).find('.color-picker').wpColorPicker();
        $(row).find('.mab-required').trigger('input'); // Initial check
    });

    // Remove row
    $(document).on('click', '.mab-remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Save with full validation
    $('.mab-save-hosters').on('click', function() {
        var valid = true;
        var messageEl = $('#mab-message').text('').removeClass('success error');

        $('.mab-required').each(function() {
            if (!this.checkValidity() || this.value.trim() === '') {
                messageEl.text('Please fill: ' + $(this).data('label')).addClass('error').css('color', 'red');
                valid = false;
                return false;
            }
        });

        if (!valid) return;

        var hosters = [];
        $('.mab-table tbody tr').each(function() {
            var name = $(this).find('input[name*="[name]"]').val().trim();
            var dead_messages = $(this).find('input[name*="[dead_messages]"]').val().trim();
            var bg_color = $(this).find('input[name*="[bg_color]"]').val();
            var text_color = $(this).find('input[name*="[text_color]"]').val();
            if (name && dead_messages) {
                hosters.push({ name, dead_messages, bg_color, text_color });
            }
        });

        $.post(mab_ajax.url, {
            action: 'mab_save_hosters',
            nonce: mab_ajax.nonce,
            hosters: hosters
        }, function(res) {
            if (res.success) {
                messageEl.text('Saved!').addClass('success').css('color', 'green');
            } else {
                messageEl.text('Error: ' + (res.data || 'Unknown')).addClass('error').css('color', 'red');
            }
        });
    });
});
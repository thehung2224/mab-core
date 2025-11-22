// Combined public script for both shortcode and content buttons
jQuery( function( $ ) {
    function updateLinkStatuses( selector = '.mab-btn, .download-btn' ) {
        const $btns = $( selector );
        const urls = [...new Set( $btns.map( ( i, el ) => $( el ).attr( 'href' ) || $( el ).data( 'url' ) ) )];

        if ( ! urls.length ) return;

        $btns.addClass( 'checking' );

        $.post( mab_ajax.url, {
            action: 'mab_check_links',
            nonce: mab_ajax.nonce,
            urls: urls
        }, function( res ) {
            if ( res.success ) {
                $.each( res.data, function( url, alive ) {
                    $( `${selector}[href="${url}"], ${selector}[data-url="${url}"]` )
                        .removeClass( 'checking alive dead' )
                        .addClass( alive ? 'alive' : 'dead' );
                } );
            }
        } );
    }

    updateLinkStatuses();
    setInterval( updateLinkStatuses, 45000 );
} );
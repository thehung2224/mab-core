jQuery( function( $ ) {
    function updateLinkStatuses() {
        var $btns = $( '.mab-btn' );
        var urls = $btns.map( function() { return $( this ).data( 'url' ); } ).get();

        if ( ! urls.length ) return;

        $.post( mab_ajax.url, {
            action: 'mab_check_links',
            nonce: mab_ajax.nonce,
            urls: urls
        }, function( res ) {
            if ( res.success ) {
                $.each( res.data, function( url, alive ) {
                    $( '.mab-btn[data-url="' + url + '"]' ).removeClass( 'alive dead' ).addClass( alive ? 'alive' : 'dead' );
                } );
            }
        } );
    }

    updateLinkStatuses();
    setInterval( updateLinkStatuses, 45000 ); // Every 45 sec
} );
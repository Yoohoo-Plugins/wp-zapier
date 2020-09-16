jQuery(document).ready(function() {
    jQuery('.wpzap-switch input').on( 'click', function(){
            var id = jQuery(this).data('id');
            var nonce = jQuery(this).data('nonce');
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: 'wpzp_admin_switch_ajax',
                    id: id,
                    nonce: nonce
                },
                success: function( response ) {
                    console.log( response );
                }
            })
        })
});

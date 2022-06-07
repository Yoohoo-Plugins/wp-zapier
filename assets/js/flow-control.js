jQuery(document).ready(function($) {

    $(document.body).on('wpzapier-flow-control-trigger-change', function(){
        const trigger = $('select[name="zapier_action"]').val();
        if(trigger){
            $('select.field-builder-argument-select').each(function(index, select){
                select = $(select);

                const originalValue = select.val();

                select.find('option').hide();
                select.find('optgroup').hide();

                select.find('option[data-trigger="shared"]').show();
                select.find('optgroup[data-trigger="shared"]').show();
                
                select.find('option[data-trigger="' + trigger + '"]').show();
                select.find('optgroup[data-trigger="' + trigger + '"]').show();


                if(select.find('option[data-trigger="' + trigger + '"][value="' + originalValue + '"]').length > 0){
                    select.val(originalValue);
                } else {
                    if(originalValue === 'static_value'){
                        select.val("static_value");
                    } else if(originalValue === 'static_key'){
                        select.val("static_key");
                    } else if(originalValue === 'user_meta'){
                        select.val("user_meta");
                    } else if(originalValue === 'post_meta'){
                        select.val("post_meta");
                    } else {
                        select.val("");
                    }
                }

                select.trigger('change');
            });
        }
    });

    $('select[name="zapier_action"]').on('change', function(){
        $(document.body).trigger('wpzapier-flow-control-trigger-change');
    });

    $('select.field-builder-argument-select').on('change', function(){
        const arg = $(this).val();
        if(arg === 'static_value' || arg === 'static_key' || arg === 'user_meta' || arg === 'post_meta'){
            $(this).next('.field-builder-argument-static-input').show();
        } else {
            $(this).next('.field-builder-argument-static-input').hide();
        }
    });

    $(document.body).trigger('wpzapier-flow-control-trigger-change');

    $(document.body).on('click', '.wp-zapier-conditional-flow-drop:not([disabled])', function(event){
        event.preventDefault();

        $(this).closest('.wp-zapier-conditional-flow').remove();

        let flagged = false;
        $('.wp-zapier-conditional-flow').each(function(index, elem){
            const code = $(elem).find('code');
            if(!flagged){
                code.text('IF');
                flagged = true;
            } else {
                code.text('AND');
            }
        });

    });
});
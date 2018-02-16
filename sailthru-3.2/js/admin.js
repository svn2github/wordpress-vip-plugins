(function($) {
    "use strict";
    $(function() {
        // show/hide the form to add the user's API key/secret
        $("#sailthru-add-api-key").click(function(e) {
            e.preventDefault();
            $("#sailthru-add-api-key-form").toggle(600);
        });

        // validate the form for saving api keys
        $("#sailthru-add-api-key-form").submit(function(e) {

            var isFormValid = true;
            var sailthru_fields = new Array("sailthru_api_key", "sailthru_api_secret", "sailthru_js_type");

            for (var i = 0; i < sailthru_fields.length; i++) {
                var field = '#' + sailthru_fields[i];

                if ($.trim($(field).val()).length == 0) {
                    $(field).addClass("error-highlight");
                    isFormValid = false;
                    e.preventDefault();
                } else {
                    $(field).removeClass("error-highlight");
                    isFormValid = true;
                }
            }

            return isFormValid;

        }); // end validate form submit

        // add a subscriber
        $("#sailthru-add-subscriber-form").submit(function(e) {
            e.preventDefault();
        });
        // set up form. make the email template more prominent
        // datepicker for meta box
        // but since Datepicker causes a jQuery conflict, wrap it
        // and prevent from initializing unless necessary
        if ($('.datepicker').length) {
            $('.datepicker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        }


        // custom form fields

        $('.selection').parent().parent().hide();
        $('#type').closest('table').find("tr").last().hide();
        // hide hidden field
        $("#sailthru_customfield_hidden_value").closest('tr').hide();

        $('.sailthru-del-field').click(function(e) {
            e.preventDefault();
            $('form').submit();
        });

        $('#type').on("change", (function() {

            // toggle fields for hidden field inputs
            if ($(this).attr('value') == 'hidden') {
               $("#sailthru_customfield_hidden_value").closest('tr').show();
               $("#sailthru_customfield_label").closest('tr').hide();
            } else {
                $("#sailthru_customfield_hidden_value").closest('tr').hide();
                $("#sailthru_customfield_label").closest('tr').show();
            }

            if ($(this).attr('value') == 'select' || $(this).attr('value') == 'radio' || $(this).attr('value') == 'checkbox') {
                $(this).closest('table').find("tr").last().show();
                $('#add_value').show();
                $("input[name*=sailthru_customfield_value1]").show();
                $('.selection').parent().parent().show();
                $('#add_value').show();
                $("input[name*=sailthru_customfield_value1]").show();

                if ($(this).attr('value') == 'hidden') {
                    $('#add_value').hide();
                    $("input[name*=sailthru_customfield_value1]").hide();
                    $("input[name*=sailthru_customfield_value1]").parent().parent().find('th').html('Field Value');
                }
            } else {
                var tbl = $(this).closest('table');
                tbl.find("tr").last().hide();
            }
        }));

        $('#add_value').on("click", (function(e) {
            e.preventDefault();
            var new_val = parseInt($('#value_amount').attr('value'), 10);
            new_val = new_val + 1;
            var second_val = new_val + 1;
            $('#sailthru_value_fields_block').append('<div><input class="selection" name="sailthru_forms_options[sailthru_customfield_value][' + new_val + '][value]" type="text"  placeholder="display value"/><input class="selection" name="sailthru_forms_options[sailthru_customfield_value][' + new_val + '][label]" type="text"  placeholder="value"/></div>');
            $('#value_amount').attr('value', parseInt(new_val, 10));
        }));

        $('#add_attr').on("click", (function(e) {
            e.preventDefault();
            var new_val = parseInt($('#attr_amount').attr('value'), 10);
            new_val = new_val + 1;
            var second_val = new_val + 1;
            $('#sailthru_attr_fields_block').append('<div><input class="attribute" name="sailthru_forms_options[sailthru_customfield_attr' + new_val + ']" type="text"  placeholder="attribute"/><input class="attribute" name="sailthru_forms_options[sailthru_customfield_attr' + second_val + ']" type="text"  placeholder="value"/></div>');
            $('#attr_amount').attr('value', parseInt(second_val, 10));
        }));

        $('button').on("click", (function(e) {
            var value = jQuery(this).val();
            $("#delete_value").val(value);
        }));

        // add a subscriber
        $("#sailthru-add-subscriber-form").submit(function(e) {
            e.preventDefault();
        });
        // set up form. make the email template more prominent
        $("#sailthru_setup_email_template").parents('tr').addClass('grayBorder');

        // datepickerfor meta box
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd'
        });

        $("#sortable").disableSelection();
        var sort = $("#sortable").sortable({
            axis: 'y',
            stop: function(event, ui) {
                var data = sort.sortable("serialize");

                // sends GET to current page
                $.ajax({
                    data: data,
                });
                //retrieves the numbered position of the field
                data = data.match(/\d(\d?)*/g);
                $(function() {
                    $("#field_order").val(data);
                })

            }
        });

    });

}(jQuery));
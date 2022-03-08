(function ($) {
    'use strict';

    var $repeater = $('#repeater').repeater({
        initEmpty: true,
        defaultValues: {
            'role': $('select[name="role"]').first().find('option:selected').val()
        },
        show: function () {
            $(this).slideDown(150);
            if ($('#repeater').find('[data-repeater-item]').length) {
                $('.form-table').find('tr').first().find('*').slideUp(200);
                $('#skautis_integration_modules_register_rulesNotSetHelp').hide(400);
                $('#skautis_integration_modules_register_rulesSetHelp').show(400);
            }
            updateAvailableOptions();
        },
        hide: function (deleteElement) {
            $(this).slideUp(150, deleteElement);
            setTimeout(function () {
                if (!$('#repeater').find('[data-repeater-item]').length) {
                    $('.form-table').find('tr').first().find('*').slideDown(200);
                    $('#skautis_integration_modules_register_rulesNotSetHelp').show(400);
                    $('#skautis_integration_modules_register_rulesSetHelp').hide(400);
                }

                updateAvailableOptions();
            }, 250);
        },
        ready: function (setIndexes) {
            $('#repeater').on('skautis_modules_register_SortableDrop', setIndexes);
        },
        isFirstItemUndeletable: true
    });
    $repeater.setList(window.rulesData);

    $('[data-repeater-list]').sortable({
        handle: '.handle',
        update: function () {
            $('#repeater').trigger('skautis_modules_register_SortableDrop');
        }
    });

    function reinitSelect2() {
        jQuery('.form-table').find('select.select2').select2({
            placeholder: 'Vyberte pravidlo...'
        }).on('change.skautis_modules_register_admin', updateAvailableOptions);
    }

    function updateAvailableOptions() {
        var usedOptions = [];

        setTimeout(function () {

            var $selectRules = jQuery('.form-table').find('select.rule');

            $selectRules.each(function () {
                usedOptions.push(jQuery(this).val());
            });

            $selectRules.find('option').removeAttr('disabled');

            for (var key in usedOptions) {
                if (usedOptions.hasOwnProperty(key)) {
                    $selectRules.find('option[value="' + usedOptions[key] + '"]').attr('disabled', 'disabled');
                }
            }

            $selectRules.each(function () {
                jQuery(this).find('option:selected').removeAttr('disabled');
            });

            reinitSelect2();

        }, 0);
    }

})(jQuery);

class Membership {
    memberships: Record<string, string>;
    unitOperators: Record<string, string>;

    constructor(memberships: never) {
        this.memberships = memberships;
        this.unitOperators = {};
        this.unitOperators['equal'] = 'equal';
        this.unitOperators['begins_with'] = 'begins_with';
        this.unitOperators['any'] = 'any';
    }

    input(_: QueryBuilderRule, input_name: string) {
        this.unitOperators['equal'] = jQuery.fn.queryBuilder.regional.cs.operators.equal;
        this.unitOperators['begins_with'] = jQuery.fn.queryBuilder.regional.cs.operators.begins_with;
        this.unitOperators['any'] = jQuery.fn.queryBuilder.regional.cs.operators.any;

        var html = '<select class="form-control select2" name="' + input_name + '_1" multiple="multiple">';

        for (var key in this.memberships) {
            if (this.memberships.hasOwnProperty(key)) {
                html += '<option value="' + key + '">' + this.memberships[key] + '</option>';
            }
        }

        html += '</select><div style="margin-top: 0.6em;">' + jQuery.fn.queryBuilder.regional.cs.custom.units.inUnitWithNumber;
        html += '<select class="multi-rules form-control skautis-rule-unitnumber-select" name="' + input_name + '_2">';

        for (key in this.unitOperators) {
            if (this.unitOperators.hasOwnProperty(key)) {
                html += '<option value="' + key + '">' + this.unitOperators[key] + '</option>';
            }
        }

        html += '</select><div class="multi-rules input-container">';
        html += '<input class="form-control skautis-rule-unitnumber-input" type="text" name="' + input_name + '_3" value="" placeholder="' + jQuery.fn.queryBuilder.regional.cs.custom.units.unitNumber + '" />';
        html += '</div></div>';
        return html;
    }

    validation() {
        return {
            format: /^(?!null)[^~]+~(?!null)[^~]+~(?!null)[^~]+$/
        };
    }

    valueGetter(rule: QueryBuilderRule) {
        return rule.$el.find('.rule-value-container [name$=_1]').val()
            + '~' + rule.$el.find('.rule-value-container [name$=_2]').val()
            + '~' + rule.$el.find('.rule-value-container [name$=_3]').val();
    }

    valueSetter = function (rule: QueryBuilderRule, value: string) {
        if (rule.operator.nb_inputs > 0) {
            var val = value.split('~');

            var val0 = val[0].split(',');

            for (var key in val0) {
                if (val0.hasOwnProperty(key)) {
                    rule.$el.find('.rule-value-container [name$=_1] option[value="' + val0[key] + '"]').prop("selected", true);
                }
            }

            rule.$el.find('.rule-value-container [name$=_2]').val(val[1]).trigger('change');
            rule.$el.find('.rule-value-container [name$=_3]').val(val[2]).trigger('change');
        }
    }
}

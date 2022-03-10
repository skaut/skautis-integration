class Qualification {
    qualifications: Record<string, string>;
    unitOperators: Record<string, string>;

    constructor(qualifications: Record<string, string>) {
        this.qualifications = qualifications;
        this.unitOperators = {};
    }

    input(_: QueryBuilderRule, input_name: string) {
        var html = '<select class="form-control select2" name="' + input_name + '_1" multiple="multiple">';

        for (var key in this.qualifications) {
            if (this.qualifications.hasOwnProperty(key)) {
                html += '<option value="' + key + '">' + this.qualifications[key] + '</option>';
            }
        }

        html += '</select>';
        return html;
    }

    validation() {
        return {
            format: /^(?!null)[^~]+$/
        };
    }

    valueGetter(rule: QueryBuilderRule) {
        return rule.$el.find('.rule-value-container [name$=_1]').val() + '';
    }

    valueSetter(rule: QueryBuilderRule, value: string) {
        if (rule.operator.nb_inputs > 0) {
            var val0 = value.split(',');

            for (var key in val0) {
                if (val0.hasOwnProperty(key)) {
                    rule.$el.find('.rule-value-container [name$=_1] option[value="' + val0[key] + '"]').prop("selected", true);
                }
            }
        }
    };
}

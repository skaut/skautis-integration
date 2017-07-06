function Func(funcs) {
    this.funcs = funcs;
    this.unitOperators = [];
    this.unitOperators['equal'] = 'equal';
    this.unitOperators['begins_with'] = 'begins_with';
}

Func.prototype.input = function (rule, name) {
    var _this = this;

    _this.unitOperators['equal'] = jQuery.fn.queryBuilder.regional.cs.operators.equal;
    _this.unitOperators['begins_with'] = jQuery.fn.queryBuilder.regional.cs.operators.begins_with;

    var html = '<select class="form-control select2" name="' + name + '_1" multiple="multiple">';

    for (var key in _this.funcs) {
        if (_this.funcs.hasOwnProperty(key)) {
            html += '<option value="' + Object.keys(_this.funcs[key])[0] + '">' + _this.funcs[key][Object.keys(_this.funcs[key])[0]] + '</option>';
        }
    }

    html += '</select><div style="margin-top: 0.6em;">' + jQuery.fn.queryBuilder.regional.cs.custom.units.inUnitWithNumber;
    html += '<select class="multi-rules form-control" name="' + name + '_2">';

    for (key in _this.unitOperators) {
        if (_this.unitOperators.hasOwnProperty(key)) {
            html += '<option value="' + key + '">' + _this.unitOperators[key] + '</option>';
        }
    }

    html += '</select><div class="multi-rules input-container">';
    html += '<input class="form-control" type="text" name="' + name + '_3" value="" placeholder="' + jQuery.fn.queryBuilder.regional.cs.custom.units.unitNumber + '" />';
    html += '</div></div>';
    return html;
};

Func.prototype.validation = function () {
    var _this = this;
    return {
        format: /^(?!null)[^~]+~(?!null)[^~]+~(?!null)[^~]+$/
    };
};

Func.prototype.valueGetter = function (rule) {
    var _this = this;
    return rule.$el.find('.rule-value-container [name$=_1]').val()
        + '~' + rule.$el.find('.rule-value-container [name$=_2]').val()
        + '~' + rule.$el.find('.rule-value-container [name$=_3]').val();
};

Func.prototype.valueSetter = function (rule, value) {
    var _this = this;
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
};
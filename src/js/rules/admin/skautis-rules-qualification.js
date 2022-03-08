function Qualification(qualifications) {
    this.qualifications = qualifications;
    this.unitOperators = [];
}

Qualification.prototype.input = function (rule, name) {
    var _this = this;

    var html = '<select class="form-control select2" name="' + name + '_1" multiple="multiple">';

    for (var key in _this.qualifications) {
        if (_this.qualifications.hasOwnProperty(key)) {
            html += '<option value="' + key + '">' + _this.qualifications[key] + '</option>';
        }
    }

    html += '</select>';
    return html;
};

Qualification.prototype.validation = function () {
    var _this = this;
    return {
        format: /^(?!null)[^~]+$/
    };
};

Qualification.prototype.valueGetter = function (rule) {
    var _this = this;
    return rule.$el.find('.rule-value-container [name$=_1]').val() + '';
};

Qualification.prototype.valueSetter = function (rule, value) {
    var _this = this;
    if (rule.operator.nb_inputs > 0) {
        var val0 = value.split(',');

        for (var key in val0) {
            if (val0.hasOwnProperty(key)) {
                rule.$el.find('.rule-value-container [name$=_1] option[value="' + val0[key] + '"]').prop("selected", true);
            }
        }
    }
};
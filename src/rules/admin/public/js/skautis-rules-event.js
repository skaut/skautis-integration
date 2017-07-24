function Events(participantTypes, events) {
    this.participantTypes = participantTypes;
    this.events = events;
}

Events.prototype.input = function (rule, name) {
    var _this = this;

    var html = '<select class="form-control select2" name="' + name + '_1" multiple="multiple">';

    for (var key in _this.participantTypes) {
        if (_this.participantTypes.hasOwnProperty(key)) {
            html += '<option value="' + key + '">' + _this.participantTypes[key] + '</option>';
        }
    }

    html += '</select><div style="margin-top: 0.6em;">' + jQuery.fn.queryBuilder.regional.cs.custom.events.event;
    html += '<div class="multi-rules input-container">';
    html += '<select class="form-control select2" type="text" name="' + name + '_2" value="" placeholder="' + jQuery.fn.queryBuilder.regional.cs.custom.events.placeholder + '">';

    for (key in _this.events) {
        if (_this.events.hasOwnProperty(key)) {
            html += '<option value="' + key + '">' + _this.events[key] + '</option>';
        }
    }

    html += '</select>';
    html += '</div></div>';
    return html;
};

Events.prototype.validation = function () {
    var _this = this;
    return {
        format: /^(?!null)[^~]+~(?!null)[^~]+~(?!null)[^~]+$/
    };
};

Events.prototype.valueGetter = function (rule) {
    var _this = this;
    return rule.$el.find('.rule-value-container [name$=_1]').val()
        + '~equal'
        + '~' + rule.$el.find('.rule-value-container [name$=_2]').val();
};

Events.prototype.valueSetter = function (rule, value) {
    var _this = this;
    if (rule.operator.nb_inputs > 0) {
        var val = value.split('~');

        var val0 = val[0].split(',');

        for (var key in val0) {
            if (val0.hasOwnProperty(key)) {
                rule.$el.find('.rule-value-container [name$=_1] option[value="' + val0[key] + '"]').prop("selected", true);
            }
        }

        if (val[2] && rule.$el.find('.rule-value-container [name$=_2] option[value="' + val[2] + '"]').length === 0) {
            var option = new Option(jQuery.fn.queryBuilder.regional.cs.custom.events.userNotHavePermission, val[2]);
            rule.$el.find('.rule-value-container [name$=_2]').append(jQuery(option));
        }

        rule.$el.find('.rule-value-container [name$=_2] option[value="' + val[2] + '"]').prop("selected", true).trigger('change');
    }
};
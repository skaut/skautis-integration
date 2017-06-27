/*!
 * jQuery QueryBuilder 2.4.3
 * Locale: Čeština (cs)
 * Author: David Odehnal
 * Licensed under MIT (http://opensource.org/licenses/MIT)
 */

(function (root, factory) {
    if (typeof define == 'function' && define.amd) {
        define(['jquery', 'query-builder'], factory);
    }
    else {
        factory(root.jQuery);
    }
}(this, function ($) {
    "use strict";

    var QueryBuilder = $.fn.queryBuilder;

    QueryBuilder.regional['cs'] = {
        "__locale": "Čeština (cs)",
        "__author": "David Odehnal",
        "add_rule": "Přidat pravidlo",
        "add_group": "Přidat skupinu",
        "delete_rule": "Odstranit pravidlo",
        "delete_group": "Odstranit skupinu",
        "conditions": {
            "AND": "A zároveň",
            "OR": "Nebo"
        },
        "operators": {
            "equal": "je rovno",
            "not_equal": "není rovno",
            "in": "je ve výběru",
            "not_in": "není ve výběru",
            "less": "je menší než",
            "less_or_equal": "je menší nebo stejné jako",
            "greater": "je větší než",
            "greater_or_equal": "je větší nebo stejné jako",
            "between": "je mezi",
            "begins_with": "začíná na",
            "not_begins_with": "nezačíná na",
            "contains": "obsahuje",
            "not_contains": "neobsahuje",
            "ends_with": "končí na",
            "not_ends_with": "nekončí na",
            "is_empty": "je prázdné",
            "is_not_empty": "není prázdné",
            "is_null": "je vyplněno",
            "is_not_null": "není vyplněno"
        },
        "errors": {
            "no_filter": "není vybrán žádný filtr",
            "empty_group": "skupina pravidel je prázdná",
            "radio_empty": "Není zadána hodnota",
            "checkbox_empty": "Není zadána hodnota",
            "select_empty": "Není zadána hodnota",
            "string_empty": "Nevyplněno",
            "string_exceed_min_length": "Musí obsahovat více {0} symbolů",
            "string_exceed_max_length": "Musí obsahovat méně {0} symbolů",
            "string_invalid_format": "Nesprávný formát",
            "number_nan": "Žádné číslo",
            "number_not_integer": "Žádné číslo",
            "number_not_double": "Žádné číslo",
            "number_exceed_min": "Musí být více {0}",
            "number_exceed_max": "Musí být méně {0}",
            "number_wrong_step": "Musí být násobkem {0}",
            "datetime_empty": "Nevyplněno",
            "datetime_invalid": "Nesprávný formát datumu ({0})",
            "datetime_exceed_min": "Musí být po {0}",
            "datetime_exceed_max": "Musí být do {0}",
            "boolean_not_valid": "Musí být zadán logický výraz",
            "operator_not_multiple": "Operátor \"{1}\" nepodporuje více hodnot"
        },
        "invert": "invertní",
        "custom": {
            "select_placeholder": "Vyberte...",
            "units": {
                "unitNumber": "číslo jednotky (např. 411.12)",
                "inUnitWithNumber": "v jednotce, jejíž evidenční číslo"
            }
        }
    };

    QueryBuilder.defaults({lang_code: 'cs'});
}));
(function () {
    tinymce.PluginManager.add('skautis_rules', function (editor, url) {
        editor.addButton('skautis_rules', {
            title: 'insert_skautis_rules',
            image: url + '/../img/lilie.png',
            onclick: function () {
                var rules = window.rulesOptions,
                    visibilityOptions = window.visibilityOptions,
                    rulesOptions = [],
                    body = [];

                body.push({
                    type: 'listbox', name: 'content', label: 'visibilityMode', values: visibilityOptions
                });

                for (var key in rules) {
                    if (rules.hasOwnProperty(key)) {
                        rulesOptions.push({text: rules[key].text, value: rules[key].value});
                    }
                }
                body.push({
                    type: 'listbox', name: 'rules1', label: 'rule_1', values: rulesOptions
                });
                rulesOptions.unshift({text: '------', value: null});
                body.push({
                    type: 'listbox', name: 'rules2', label: 'rule_2', values: rulesOptions
                });
                body.push({
                    type: 'listbox', name: 'rules3', label: 'rule_3', values: rulesOptions
                });
                body.push({
                    type: 'listbox', name: 'rules4', label: 'rule_4', values: rulesOptions
                });

                editor.windowManager.open({
                    title: 'shortcode_options',
                    body: body,
                    minWidth: Math.min(viewport().width, 450),
                    minHeight: Math.min(viewport().height, 250),
                    onsubmit: function (e) {
                        var rules = [];

                        if (e.data.rules1) {
                            rules.push(e.data.rules1);
                        }
                        if (e.data.rules2) {
                            rules.push(e.data.rules2);
                        }
                        if (e.data.rules3) {
                            rules.push(e.data.rules3);
                        }
                        if (e.data.rules4) {
                            rules.push(e.data.rules4);
                        }

                        if (editor.selection.getContent()) {
                            editor.insertContent('[skautis rules="' + rules.join(',') + '" content="' + e.data.content + '"]<div>' + editor.selection.getContent() + '</div>[/skautis]');
                        } else {
                            editor.insertContent('[skautis rules="' + rules.join(',') + '" content="' + e.data.content + '"]<div>Skrytý obsah</div>[/skautis]');
                        }
                    }
                });
            }
        });
    });
})();

function viewport() {
    var e = window, a = 'inner';
    if (!('innerWidth' in window )) {
        a = 'client';
        e = document.documentElement || document.body;
    }
    return {width: e[a + 'Width'], height: e[a + 'Height']};
}

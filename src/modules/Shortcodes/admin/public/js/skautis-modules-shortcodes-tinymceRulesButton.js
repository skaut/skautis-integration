(function () {
    tinymce.PluginManager.add('skautis_rules', function (editor, url) {
        editor.addButton('skautis_rules', {
            title: 'insert_skautis_rules',
            image: url + '/../img/lilie.png',
            onclick: function () {
                var rules = window.rules,
                    body = [];
                body.push({
                    type: 'listbox', name: 'content', label: 'visibilityMode', values: [
                        {text: 'hideContent', value: 'hide'},
                        {text: 'showLogin', value: 'showLogin'}
                    ]
                });
                for (var key in rules) {
                    if (rules.hasOwnProperty(key)) {
                        body.push({type: 'checkbox', name: rules[key].value, label: rules[key].text});
                    }
                }
                editor.windowManager.open({
                    title: 'select_rules',
                    body: body,
                    minWidth: Math.min(viewport().width, 450),
                    minHeight: Math.min(viewport().height, 250),
                    onsubmit: function (e) {
                        var rules = [];
                        for (var key in e.data) {
                            if (e.data.hasOwnProperty(key)) {
                                if (e.data[key] && key !== 'content') {
                                    rules.push(key);
                                }
                            }
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
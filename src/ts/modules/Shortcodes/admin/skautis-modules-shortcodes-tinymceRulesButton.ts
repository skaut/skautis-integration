(function () {
	tinymce.addI18n('cs', {
		'shortcode_options': 'Nastavení shortcode',
		'insert_skautis_rules': 'Vložit skautIS pravidlo',
		'select_rules': 'Vyberte pravidla',
		'hidden_content': 'Skrytý obsah',
		'rule_1': 'Pravidlo 1',
		'rule_2': 'Pravidlo 2',
		'rule_3': 'Pravidlo 3',
		'rule_4': 'Pravidlo 4',
		'visibilityMode': 'Při nesplění pravidel:',
		'hideContent': 'skrýt obsah',
		'showLogin': 'zobrazit přihlášení'
	});
	(tinymce as unknown as typeof import('tinymce')).PluginManager.add('skautis_rules', function (editor, url) {
		editor.addButton('skautis_rules', {
			title: 'insert_skautis_rules',
			image: url + '/../../../../src/modules/Shortcodes/admin/public/img/lilie.png',
			onclick: function () {
				var rules = window.rulesOptions ?? [],
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
					onsubmit: function (e: JQuery.SubmitEvent) {
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
				}, {});
			}
		});
	});
})();

function viewport() {
	if ('innerWidth' in window ) {
		return {width: window['innerWidth'], height: window['innerHeight']};
	}
	var e = document.documentElement || document.body;
	return {width: e['clientWidth'], height: e['clientHeight']};
}

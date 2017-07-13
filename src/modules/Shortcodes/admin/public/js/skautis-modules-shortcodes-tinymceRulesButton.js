(function () {
	tinymce.PluginManager.add('skautis_rules', function (editor, url) {
		editor.addButton('skautis_rules', {
			title: 'insert_skautis_rules',
			image: url + '/../img/lilie.png',
			onclick: function () {
				var rules = window.rules,
					body = [];
				for (var key in rules) {
					if (rules.hasOwnProperty(key)) {
						body.push({type: 'checkbox', name: rules[key].value, label: rules[key].text});
					}
				}
				editor.windowManager.open({
					title: 'select_rules',
					body: body,
					onsubmit: function (e) {
						var result = [];
						for (var key in e.data) {
							if (e.data.hasOwnProperty(key)) {
								if (e.data[key]) {
									result.push(key);
								}
							}
						}
						if (editor.selection.getContent()) {
							editor.insertContent('[skautis rules="' + result.join(',') + '"]' + editor.selection.getContent() + '[/skautis]');
						} else {
							editor.insertContent('[skautis rules="' + result.join(',') + '"]Skrytý obsah[/skautis]');
						}
					}
				});
			}
		});
	});
})();
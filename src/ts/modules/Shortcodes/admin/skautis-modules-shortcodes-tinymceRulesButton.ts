import type * as TinyMCE from 'tinymce';

interface ModalData {
	content: string;
	rules1: string | null;
	rules2: string | null;
	rules3: string | null;
	rules4: string | null;
}

function viewport(): { height: number; width: number } {
	if ('innerWidth' in window) {
		return {
			width: window.innerWidth,
			height: window.innerHeight,
		};
	}
	return {
		width: document.documentElement.clientWidth,
		height: document.documentElement.clientHeight,
	};
}

((): void => {
	tinymce.addI18n('cs', {
		shortcode_options: 'Nastavení shortcode',
		insert_skautis_rules: 'Vložit skautIS pravidlo',
		select_rules: 'Vyberte pravidla',
		hidden_content: 'Skrytý obsah',
		rule_1: 'Pravidlo 1',
		rule_2: 'Pravidlo 2',
		rule_3: 'Pravidlo 3',
		rule_4: 'Pravidlo 4',
		visibilityMode: 'Při nesplění pravidel:',
		hideContent: 'skrýt obsah',
		showLogin: 'zobrazit přihlášení',
	});
	(tinymce as unknown as typeof TinyMCE).PluginManager.add(
		'skautis_rules',
		(editor, url) => {
			editor.addButton('skautis_rules', {
				title: 'insert_skautis_rules',
				image:
					url +
					'/../../../../src/modules/Shortcodes/admin/public/img/lilie.png',
				onclick: () => {
					const rules = window.rulesOptions ?? [];
					const visibilityOptions = window.visibilityOptions;
					const rulesOptions = [];
					const body = [];

					body.push({
						type: 'listbox',
						name: 'content',
						label: 'visibilityMode',
						values: visibilityOptions,
					});

					for (const key in rules) {
						if (Object.prototype.hasOwnProperty.call(rules, key)) {
							rulesOptions.push({
								text: rules[key],
								value: key,
							});
						}
					}
					body.push({
						type: 'listbox',
						name: 'rules1',
						label: 'rule_1',
						values: rulesOptions,
					});
					rulesOptions.unshift({ text: '------', value: null });
					body.push({
						type: 'listbox',
						name: 'rules2',
						label: 'rule_2',
						values: rulesOptions,
					});
					body.push({
						type: 'listbox',
						name: 'rules3',
						label: 'rule_3',
						values: rulesOptions,
					});
					body.push({
						type: 'listbox',
						name: 'rules4',
						label: 'rule_4',
						values: rulesOptions,
					});

					editor.windowManager.open(
						{
							title: 'shortcode_options',
							body,
							minWidth: Math.min(viewport().width, 450),
							minHeight: Math.min(viewport().height, 250),
							onsubmit: (e: JQuery.SubmitEvent) => {
								const newRules = [];
								const eventData = e.data as ModalData;

								if (eventData.rules1 !== null) {
									newRules.push(eventData.rules1);
								}
								if (eventData.rules2 !== null) {
									newRules.push(eventData.rules2);
								}
								if (eventData.rules3 !== null) {
									newRules.push(eventData.rules3);
								}
								if (eventData.rules4 !== null) {
									newRules.push(eventData.rules4);
								}

								if (editor.selection.getContent()) {
									editor.insertContent(
										'[skautis rules="' +
											newRules.join(',') +
											'" content="' +
											eventData.content +
											'"]<div>' +
											editor.selection.getContent() +
											'</div>[/skautis]'
									);
								} else {
									editor.insertContent(
										'[skautis rules="' +
											newRules.join(',') +
											'" content="' +
											eventData.content +
											'"]<div>Skrytý obsah</div>[/skautis]'
									);
								}
							},
						},
						{}
					);
				},
			});
		}
	);
})();

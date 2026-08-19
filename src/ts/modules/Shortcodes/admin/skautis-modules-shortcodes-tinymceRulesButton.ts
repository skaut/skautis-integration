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
			height: window.innerHeight,
			width: window.innerWidth,
		};
	}
	return {
		height: document.documentElement.clientHeight,
		width: document.documentElement.clientWidth,
	};
}

((): void => {
	tinymce.addI18n('cs', {
		hidden_content: 'Skrytý obsah',
		hideContent: 'skrýt obsah',
		insert_skautis_rules: 'Vložit skautIS pravidlo',
		rule_1: 'Pravidlo 1',
		rule_2: 'Pravidlo 2',
		rule_3: 'Pravidlo 3',
		rule_4: 'Pravidlo 4',
		select_rules: 'Vyberte pravidla',
		shortcode_options: 'Nastavení shortcode',
		showLogin: 'zobrazit přihlášení',
		visibilityMode: 'Při nesplění pravidel:',
	});
	(tinymce as unknown as typeof TinyMCE).PluginManager.add(
		'skautis_rules',
		(editor, url) => {
			editor.addButton('skautis_rules', {
				image:
					url +
					'/../../../../src/modules/Shortcodes/admin/public/img/lilie.png',
				onclick: () => {
					const rules = window.rulesOptions ?? [];
					const visibilityOptions = window.visibilityOptions;
					const rulesOptions = [];
					const body = [];

					body.push({
						label: 'visibilityMode',
						name: 'content',
						type: 'listbox',
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
						label: 'rule_1',
						name: 'rules1',
						type: 'listbox',
						values: rulesOptions,
					});
					rulesOptions.unshift({ text: '------', value: null });
					body.push({
						label: 'rule_2',
						name: 'rules2',
						type: 'listbox',
						values: rulesOptions,
					});
					body.push({
						label: 'rule_3',
						name: 'rules3',
						type: 'listbox',
						values: rulesOptions,
					});
					body.push({
						label: 'rule_4',
						name: 'rules4',
						type: 'listbox',
						values: rulesOptions,
					});

					editor.windowManager.open(
						{
							body,
							minHeight: Math.min(viewport().height, 250),
							minWidth: Math.min(viewport().width, 450),
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
							title: 'shortcode_options',
						},
						{}
					);
				},
				title: 'insert_skautis_rules',
			});
		}
	);
})();

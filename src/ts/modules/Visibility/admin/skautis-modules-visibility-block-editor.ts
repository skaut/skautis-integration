/**
 * The skautIS visibility settings in the block editor.
 *
 * - Every block gets a "skautIS viditelnost" panel in the block sidebar. A block with rules is shown only to users who pass at least one of them.
 * - Posts of the types selected in the module settings get a "skautIS pravidla" panel in the post sidebar, which edits the same post meta as the metabox in the classic editor.
 */

type PostRulesMeta = Array<Record<string, string>>;

interface SkautisBlockEditorData {
	defaults: { includeChildren: string; visibilityMode: string };
	parentRules: Array<{ rules: Array<string>; title: string }>;
	postTypes: Array<string>;
	rules: Array<{ id: number; title: string }>;
	rulesUrl: string;
}

interface Window {
	skautisIntegrationBlockEditor: SkautisBlockEditorData | undefined;
}

((): void => {
	const data = window.skautisIntegrationBlockEditor;
	if (data === undefined) {
		return;
	}

	const el = wp.element.createElement;
	const { __, sprintf } = wp.i18n;
	const editorStore = 'core/editor';
	const coreStore = 'core';

	const rulesMetaKey = 'skautis-integration_rules';
	const includeChildrenMetaKey = 'skautis-integration_rules_includeChildren';
	const visibilityModeMetaKey = 'skautis-integration_rules_visibilityMode';

	const noRulesNotice = (): WPElement =>
		el(
			wp.components.Notice,
			{ isDismissible: false, status: 'warning' },
			__(
				'Zatím nemáte vytvořená žádná skautIS pravidla.',
				'skautis-integration'
			),
			' ',
			el(
				wp.components.ExternalLink,
				{ href: data.rulesUrl },
				__('Vytvořit pravidlo', 'skautis-integration')
			)
		);

	const ruleCheckboxes = (
		selected: Array<number>,
		onChange: (rules: Array<number>) => void
	): Array<WPElement> => [
		el(
			'div',
			{
				key: 'rules',
				style: { display: 'grid', gap: '8px', marginBottom: '16px' },
			},
			...data.rules.map((rule) =>
				el(wp.components.CheckboxControl, {
					__nextHasNoMarginBottom: true,
					checked: selected.includes(rule.id),
					key: rule.id,
					label: rule.title,
					onChange: (checked: boolean) => {
						onChange(
							checked
								? [...selected, rule.id]
								: selected.filter((id) => id !== rule.id)
						);
					},
				})
			)
		),
	];

	// Block attributes.
	wp.hooks.addFilter<WPBlockSettings>(
		'blocks.registerBlockType',
		'skautis-integration/visibility-attributes',
		(settings) => ({
			...settings,
			attributes: {
				...settings.attributes,
				skautisRules: {
					default: [],
					items: { type: 'integer' },
					type: 'array',
				},
				skautisVisibilityMode: {
					default: 'hide',
					enum: ['hide', 'showLogin'],
					type: 'string',
				},
			},
		})
	);

	// Block sidebar panel.
	wp.hooks.addFilter<WPComponent<WPBlockEditProps>>(
		'editor.BlockEdit',
		'skautis-integration/visibility-controls',
		wp.compose.createHigherOrderComponent<WPBlockEditProps>(
			(BlockEdit) =>
				(props): WPElement => {
					const rules = props.attributes.skautisRules ?? [];
					const mode =
						props.attributes.skautisVisibilityMode ?? 'hide';

					return el(
						wp.element.Fragment,
						null,
						el(BlockEdit, props),
						el(
							wp.blockEditor.InspectorControls,
							null,
							el(
								wp.components.PanelBody,
								{
									initialOpen: rules.length > 0,
									title: __(
										'skautIS viditelnost',
										'skautis-integration'
									),
								},
								data.rules.length === 0
									? noRulesNotice()
									: [
											el(
												'p',
												{ key: 'description' },
												__(
													'Blok uvidí jen uživatelé, kteří splní alespoň jedno z vybraných pravidel. Bez vybraných pravidel je blok viditelný pro všechny.',
													'skautis-integration'
												)
											),
											...ruleCheckboxes(
												rules,
												(newRules) => {
													props.setAttributes({
														skautisRules: newRules,
													});
												}
											),
											rules.length > 0
												? el(
														wp.components
															.RadioControl,
														{
															key: 'mode',
															label: __(
																'Uživatelům, kteří pravidla nesplní',
																'skautis-integration'
															),
															onChange: (
																value: string
															) => {
																props.setAttributes(
																	{
																		skautisVisibilityMode:
																			value,
																	}
																);
															},
															options: [
																{
																	label: __(
																		'blok úplně skrýt',
																		'skautis-integration'
																	),
																	value: 'hide',
																},
																{
																	label: __(
																		'zobrazit výzvu k přihlášení',
																		'skautis-integration'
																	),
																	value: 'showLogin',
																},
															],
															selected: mode,
														}
													)
												: null,
										]
							)
						)
					);
				},
			'withSkautisVisibilityControls'
		)
	);

	// Marking of blocks with rules in the editor canvas.
	wp.hooks.addFilter<WPComponent<WPBlockListBlockProps>>(
		'editor.BlockListBlock',
		'skautis-integration/visibility-class',
		wp.compose.createHigherOrderComponent<WPBlockListBlockProps>(
			(BlockListBlock) =>
				(props): WPElement => {
					const rules = props.attributes.skautisRules ?? [];
					if (rules.length === 0) {
						return el(BlockListBlock, props);
					}
					return el(BlockListBlock, {
						...props,
						className: [props.className, 'skautis-has-rules']
							.filter((className) => className !== undefined)
							.join(' '),
					});
				},
			'withSkautisVisibilityClass'
		)
	);

	// Post sidebar panel.
	const PostRulesPanel: WPComponent = () => {
		const { useDispatch, useSelect } = wp.data;
		const { hierarchical, meta, postType, postTypeName } = useSelect(
			(select) => {
				const type = select(editorStore).getCurrentPostType() as
					string | undefined;
				const typeObject =
					type === undefined
						? undefined
						: (select(coreStore).getPostType(type) as
								WPPostType | undefined);
				return {
					hierarchical: typeObject?.hierarchical ?? false,
					meta: (select(editorStore).getEditedPostAttribute('meta') ??
						{}) as Record<string, unknown>,
					postType: type,
					postTypeName: typeObject?.labels.name ?? '',
				};
			},
			[]
		);
		const { editPost } = useDispatch(editorStore);
		const PluginDocumentSettingPanel =
			wp.editor?.PluginDocumentSettingPanel;

		if (
			PluginDocumentSettingPanel === undefined ||
			postType === undefined ||
			!data.postTypes.includes(postType)
		) {
			return null;
		}

		const rulesMeta = (meta[rulesMetaKey] ?? []) as PostRulesMeta;
		const selected = rulesMeta
			.map((rule) => parseInt(rule[rulesMetaKey] ?? '', 10))
			.filter((id) => !isNaN(id) && id > 0);
		const metaString = (key: string, fallback: string): string => {
			const value = meta[key];
			return typeof value === 'string' && value !== '' ? value : fallback;
		};
		const includeChildren = metaString(
			includeChildrenMetaKey,
			data.defaults.includeChildren
		);
		const visibilityMode = metaString(
			visibilityModeMetaKey,
			data.defaults.visibilityMode
		);

		const updateMeta = (changes: Record<string, unknown>): void => {
			editPost({
				meta: {
					[includeChildrenMetaKey]: includeChildren,
					[visibilityModeMetaKey]: visibilityMode,
					...changes,
				},
			});
		};

		const parentRules =
			data.parentRules.length > 0
				? [
						el(
							'p',
							{ key: 'parent-title' },
							el(
								'strong',
								null,
								__(
									'Pravidla převzatá z nadřazených stránek',
									'skautis-integration'
								)
							)
						),
						el(
							'ul',
							{ key: 'parent-list' },
							...data.parentRules.map((parent) =>
								el(
									'li',
									{ key: parent.title },
									`${parent.title}: ${parent.rules.join(', ')}`
								)
							)
						),
					]
				: [];

		return el(
			PluginDocumentSettingPanel,
			{
				className: 'skautis-integration-post-rules',
				name: 'skautis-integration-post-rules',
				title: __('skautIS pravidla', 'skautis-integration'),
			},
			...parentRules,
			data.rules.length === 0
				? noRulesNotice()
				: [
						el(
							'p',
							{ key: 'description' },
							__(
								'Obsah bude pro uživatele viditelný pouze při splnění alespoň jednoho z vybraných pravidel. Bez vybraných pravidel je obsah viditelný pro všechny.',
								'skautis-integration'
							)
						),
						...ruleCheckboxes(selected, (newRules) => {
							updateMeta({
								[rulesMetaKey]: newRules.map((id) => ({
									[rulesMetaKey]: id.toString(),
								})),
							});
						}),
						selected.length > 0
							? el(
									'div',
									{
										key: 'include-children',
										style: { marginBottom: '16px' },
									},
									el(wp.components.CheckboxControl, {
										__nextHasNoMarginBottom: true,
										checked: includeChildren === '1',
										label: hierarchical
											? sprintf(
													/* Translators: %s: The plural name of the post type */
													__(
														'Použít vybraná pravidla i na podřízené %s',
														'skautis-integration'
													),
													postTypeName.toLowerCase()
												)
											: __(
													'Použít vybraná pravidla i na podřízený obsah (média - obrázky, videa, přílohy,…)',
													'skautis-integration'
												),
										onChange: (checked: boolean) => {
											updateMeta({
												[includeChildrenMetaKey]:
													checked ? '1' : '0',
											});
										},
									})
								)
							: null,
						selected.length > 0
							? el(wp.components.RadioControl, {
									key: 'mode',
									label: __(
										'Uživatelům, kteří pravidla nesplní',
										'skautis-integration'
									),
									onChange: (value: string) => {
										updateMeta({
											[visibilityModeMetaKey]: value,
										});
									},
									options: [
										{
											label: __(
												'Úplně skrýt',
												'skautis-integration'
											),
											value: 'full',
										},
										{
											label: __(
												'Skrýt pouze obsah',
												'skautis-integration'
											),
											value: 'content',
										},
									],
									selected: visibilityMode,
								})
							: null,
					]
		);
	};

	wp.domReady(() => {
		// The panel is only available in the post editor, not in the widgets editor.
		if (wp.editor?.PluginDocumentSettingPanel !== undefined) {
			wp.plugins.registerPlugin('skautis-integration-post-rules', {
				icon: null,
				render: PostRulesPanel,
			});
		}
	});
})();

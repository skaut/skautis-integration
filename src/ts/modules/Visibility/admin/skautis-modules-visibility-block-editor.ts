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
				'You have not created any skautIS rules yet.',
				'skautis-integration'
			),
			' ',
			el(
				wp.components.ExternalLink,
				{ href: data.rulesUrl },
				__('Create a rule', 'skautis-integration')
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
				// No defaults, so that blocks without rules don't get the attributes at all.
				skautisRules: {
					items: { type: 'integer' },
					type: 'array',
				},
				skautisVisibilityMode: {
					enum: ['hide', 'showLogin'],
					type: 'string',
				},
			},
		})
	);

	// Block sidebar panel.
	const noBlockRules: Array<number> = [];

	/**
	 * The panel is memoized, so that it isn't rendered again on every change of the block, e.g. when typing.
	 */
	const BlockVisibilityPanel = wp.element.memo(
		({
			mode,
			rules,
			setAttributes,
		}: {
			mode: string;
			rules: Array<number>;
			setAttributes: WPBlockEditProps['setAttributes'];
		}): WPElement =>
			el(
				wp.blockEditor.InspectorControls,
				null,
				el(
					wp.components.PanelBody,
					{
						initialOpen: rules.length > 0,
						title:
							rules.length > 0
								? __(
										'skautIS visibility (restricted)',
										'skautis-integration'
									)
								: __(
										'skautIS visibility',
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
										'Only users who pass at least one of the selected rules will see the block. Without selected rules, the block is visible to everyone.',
										'skautis-integration'
									)
								),
								...ruleCheckboxes(rules, (newRules) => {
									setAttributes({ skautisRules: newRules });
								}),
								rules.length > 0
									? el(wp.components.RadioControl, {
											key: 'mode',
											label: __(
												'For users who do not pass the rules',
												'skautis-integration'
											),
											onChange: (value: string) => {
												setAttributes({
													skautisVisibilityMode:
														value,
												});
											},
											options: [
												{
													label: __(
														'hide the block completely',
														'skautis-integration'
													),
													value: 'hide',
												},
												{
													label: __(
														'show a login prompt',
														'skautis-integration'
													),
													value: 'showLogin',
												},
											],
											selected: mode,
										})
									: null,
							]
				)
			)
	);

	wp.hooks.addFilter<WPComponent<WPBlockEditProps>>(
		'editor.BlockEdit',
		'skautis-integration/visibility-controls',
		wp.compose.createHigherOrderComponent<WPBlockEditProps>(
			(BlockEdit) =>
				(props): WPElement =>
					// The tree keeps the same shape for selected and unselected blocks, otherwise the block would be re-mounted when it gets selected. The sidebar only shows the selected block, so the panel is not rendered for the other blocks.
					el(
						wp.element.Fragment,
						null,
						el(BlockEdit, props),
						props.isSelected &&
							el(BlockVisibilityPanel, {
								mode:
									props.attributes.skautisVisibilityMode ??
									'hide',
								rules:
									props.attributes.skautisRules ??
									noBlockRules,
								setAttributes: props.setAttributes,
							})
					),
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
									'Rules inherited from parent pages',
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
				title: __('skautIS rules', 'skautis-integration'),
			},
			...parentRules,
			data.rules.length === 0
				? noRulesNotice()
				: [
						el(
							'p',
							{ key: 'description' },
							__(
								'The content will only be visible to users who pass at least one of the selected rules. Without selected rules, the content is visible to everyone.',
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
														'Apply the selected rules to child %s as well',
														'skautis-integration'
													),
													postTypeName.toLowerCase()
												)
											: __(
													'Apply the selected rules to child content (media - images, videos, attachments, …) as well',
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
										'For users who do not pass the rules',
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
												'Hide completely',
												'skautis-integration'
											),
											value: 'full',
										},
										{
											label: __(
												'Hide only the content',
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

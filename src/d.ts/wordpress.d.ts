/**
 * Minimal types of the WordPress editor packages used through the global `wp` object.
 *
 * The scripts are not bundled, so the packages cannot be imported. Only the parts used by the plugin are declared.
 */

interface WPBlockAttributes {
	[key: string]: unknown;
	skautisRules?: Array<number>;
	skautisVisibilityMode?: string;
}

interface WPBlockEditProps {
	[key: string]: unknown;
	attributes: WPBlockAttributes;
	isSelected: boolean;
	setAttributes(this: void, attributes: Partial<WPBlockAttributes>): void;
}

interface WPBlockListBlockProps {
	[key: string]: unknown;
	attributes: WPBlockAttributes;
	className?: string;
}

interface WPBlockSettings {
	[key: string]: unknown;
	attributes?: Record<string, unknown>;
}

type WPComponent<P = WPComponentProps> = (props: P) => WPElement;

type WPComponentProps = Record<string, unknown>;

type WPElement = object | null;

interface WPPostType {
	hierarchical: boolean;
	labels: { name: string };
}

type WPSelect = (store: string) => WPSelectors;

/**
 * The selectors of the `core` and `core/editor` stores used by the plugin.
 */
interface WPSelectors {
	getCurrentPostType(this: void): unknown;
	getEditedPostAttribute(this: void, attribute: string): unknown;
	getPostType(this: void, postType: string): unknown;
}

declare const wp: {
	a11y: {
		speak(
			this: void,
			message: string,
			ariaLive?: 'assertive' | 'polite'
		): void;
	};
	blockEditor: {
		InspectorControls: WPComponent;
	};
	components: {
		CheckboxControl: WPComponent;
		ExternalLink: WPComponent;
		Notice: WPComponent;
		PanelBody: WPComponent;
		RadioControl: WPComponent;
	};
	compose: {
		createHigherOrderComponent<P>(
			this: void,
			mapComponent: (component: WPComponent<P>) => WPComponent<P>,
			name: string
		): (component: WPComponent<P>) => WPComponent<P>;
	};
	data: {
		useDispatch(
			this: void,
			store: string
		): {
			editPost(this: void, edits: Record<string, unknown>): void;
		};
		useSelect<T>(
			this: void,
			mapSelect: (select: WPSelect) => T,
			deps: Array<unknown>
		): T;
	};
	domReady(this: void, callback: () => void): void;
	editor?: {
		PluginDocumentSettingPanel?: WPComponent;
	};
	element: {
		createElement<P>(
			this: void,
			type: WPComponent<P> | string,
			props: P | null,
			...children: Array<unknown>
		): WPElement;
		Fragment: WPComponent;
		memo<P>(this: void, component: WPComponent<P>): WPComponent<P>;
	};
	hooks: {
		addFilter<T>(
			this: void,
			hookName: string,
			namespace: string,
			callback: (value: T, ...args: Array<never>) => T
		): void;
	};
	i18n: {
		__(this: void, text: string, domain: string): string;
		_n(
			this: void,
			single: string,
			plural: string,
			number: number,
			domain: string
		): string;
		sprintf(
			this: void,
			format: string,
			...args: Array<number | string>
		): string;
	};
	plugins: {
		registerPlugin(
			this: void,
			name: string,
			settings: { icon?: string | null; render: WPComponent }
		): void;
	};
};

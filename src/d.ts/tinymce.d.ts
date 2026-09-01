import type * as TinyMCE from 'tinymce';

declare global {
	const tinymce: TinyMCE.EditorManager & typeof TinyMCE;
}

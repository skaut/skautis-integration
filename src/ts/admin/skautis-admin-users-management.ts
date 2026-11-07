/**
 * Filters the table of skautIS users and shows the panel for connecting a user below its row.
 */
wp.domReady(() => {
	const table = document.querySelector<HTMLTableElement>(
		'.skautis-users-table'
	);
	const panelOrNull = document.getElementById('skautis-users-connect-panel');
	if (table === null || panelOrNull === null) {
		return;
	}
	const panel: HTMLElement = panelOrNull;
	const tbody = table.tBodies[0];
	const rows = Array.from(
		tbody.querySelectorAll<HTMLTableRowElement>('tr[data-skautis-user-id]')
	);
	const emptyRow = tbody.querySelector<HTMLTableRowElement>(
		'.skautis-users-empty'
	);
	const count = document.querySelector('.skautis-users-count');
	const search = document.getElementById(
		'skautis-users-search'
	) as HTMLInputElement | null;
	const filter = document.getElementById(
		'skautis-users-filter'
	) as HTMLSelectElement | null;
	const panelTitle = document.getElementById('skautis-users-connect-title');
	let openButton: HTMLButtonElement | null = null;

	function normalize(text: string): string {
		return text.normalize('NFD').replace(/[̀-ͯ]/gu, '').toLowerCase();
	}

	function closePanel(returnFocus: boolean): void {
		if (openButton === null) {
			return;
		}
		openButton.setAttribute('aria-expanded', 'false');
		panel.hidden = true;
		if (returnFocus) {
			openButton.focus();
		}
		openButton = null;
	}

	function openPanel(button: HTMLButtonElement): void {
		const row = button.closest('tr');
		if (row === null) {
			return;
		}
		closePanel(false);
		row.after(panel);
		panel
			.querySelectorAll<HTMLInputElement>('.skautis-users-connect-id')
			.forEach((input) => {
				input.value = row.dataset['skautisUserId'] ?? '';
			});
		const name =
			row.querySelector('.skautis-users-name')?.textContent ?? '';
		if (panelTitle !== null) {
			panelTitle.textContent = wp.i18n.sprintf(
				/* Translators: %s: The name of the skautIS user */
				wp.i18n.__(
					'Connect %s to a WordPress account',
					'skautis-integration'
				),
				name
			);
		}
		panel.hidden = false;
		button.setAttribute('aria-expanded', 'true');
		openButton = button;
		panelTitle?.focus();
	}

	function applyFilter(): void {
		const query = search === null ? '' : normalize(search.value.trim());
		const state = filter === null ? '' : filter.value;
		let visible = 0;
		rows.forEach((row) => {
			const connected = row.dataset['connected'] === '1';
			const matchesState =
				state === '' || (state === 'connected') === connected;
			const matchesQuery =
				query === '' || normalize(row.textContent).includes(query);
			row.hidden = !(matchesState && matchesQuery);
			if (!row.hidden) {
				visible++;
			}
		});
		if (openButton !== null && openButton.closest('tr')?.hidden === true) {
			closePanel(false);
		}
		if (emptyRow !== null) {
			emptyRow.hidden = visible > 0;
		}
		const message =
			visible === rows.length
				? wp.i18n.sprintf(
						/* Translators: %d: The number of users */
						wp.i18n._n(
							'%d user',
							'%d users',
							visible,
							'skautis-integration'
						),
						visible
					)
				: wp.i18n.sprintf(
						/* Translators: 1: The number of shown users 2: The number of all users */
						wp.i18n.__('%1$d of %2$d users', 'skautis-integration'),
						visible,
						rows.length
					);
		if (count !== null) {
			count.textContent = message;
		}
	}

	let speakTimeout = 0;
	function onFilterChange(): void {
		applyFilter();
		window.clearTimeout(speakTimeout);
		speakTimeout = window.setTimeout(() => {
			if (count !== null && count.textContent !== '') {
				wp.a11y.speak(count.textContent);
			}
		}, 500);
	}

	// The count is announced by wp.a11y, so that it is not read on every key press.
	count?.removeAttribute('aria-live');
	if (filter !== null) {
		filter.hidden = false;
		filter.addEventListener('change', onFilterChange);
	}
	search?.addEventListener('input', onFilterChange);

	table.addEventListener('click', (event) => {
		const button = (event.target as Element).closest<HTMLButtonElement>(
			'.skautis-users-connect'
		);
		if (button === null) {
			return;
		}
		if (button === openButton) {
			closePanel(true);
		} else {
			openPanel(button);
		}
	});
	panel
		.querySelector('.skautis-users-connect-close')
		?.addEventListener('click', () => {
			closePanel(true);
		});
	panel.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			closePanel(true);
		}
	});

	// The panel was printed outside of the table, so that it does not need a script to be hidden.
	tbody.append(panel);
	if (search !== null && search.value !== '') {
		applyFilter();
	}
});

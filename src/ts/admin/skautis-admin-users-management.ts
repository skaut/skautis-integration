/// <reference types="datatables.net"/>

function getQueryStringFromUrl(key: string, url: string): string {
	key = key.replace(/[[]/, '\\[').replace(/[\]]/, '\\]');
	const regex = new RegExp('[\\?&]' + key + '=([^&#]*)'),
		results = regex.exec(url);
	return results === null
		? ''
		: decodeURIComponent(results[1].replace(/\+/g, ' '));
}

function updateQueryStringInUrl(
	key: string,
	value: string,
	url: string
): string {
	const re = new RegExp('([?&])' + key + '=.*?(&|#|$)(.*)', 'gi');

	if (re.test(url)) {
		if (typeof value !== 'undefined') {
			return url.replace(re, '$1' + key + '=' + value + '$2$3');
		}
		const hash = url.split('#');
		url = hash[0].replace(re, '$1$3').replace(/(&|\?)$/, '');
		if (typeof hash[1] !== 'undefined') {
			url += '#' + hash[1];
		}
		return url;
	}
	if (typeof value !== 'undefined') {
		const separator = url.includes('?') ? '&' : '?';
		const hash = url.split('#');
		url = hash[0] + separator + key + '=' + value;
		if (typeof hash[1] !== 'undefined') {
			url += '#' + hash[1];
		}
		return url;
	}
	return url;
}

(function ($): void {
	const $dataTable = $('.skautis-user-management-table').DataTable({
		pageLength: 25,
		stateSave: true,
		language: {
			url:
				skautisIntegrationAdminUsersManagementLocalize.datatablesFilesUrl +
				'/cs.json',
			search: 'Hledat',
		},
		initComplete: () => {
			const searchString = getQueryStringFromUrl(
				'skautisSearchUsers',
				window.location.href
			);

			if ($dataTable.data().length >= 500) {
				const $input = $('.dt-search input').off(),
					$searchButton = $('<button>')
						.text($dataTable.i18n('search', 'Search'))
						.addClass('button button-secondary')
						.on('click', () => {
							const withNonce = updateQueryStringInUrl(
								skautisIntegrationAdminUsersManagementLocalize.searchNonceName,
								skautisIntegrationAdminUsersManagementLocalize.searchNonceValue,
								window.location.href
							);
							window.location.href = updateQueryStringInUrl(
								'skautisSearchUsers',
								$input.val() as string,
								withNonce
							);
						});
				$input.on('keyup', (e) => {
					e.preventDefault();
					if (e.key === 'Enter') {
						$searchButton.trigger('click');
					}
				});
				$('.dt-search').append($searchButton);
			}

			if (searchString) {
				const $clearButton = $('<button>')
					.text(skautisIntegrationAdminUsersManagementLocalize.cancel)
					.addClass('button button-secondary')
					.on('click', () => {
						$('.dt-search input').val('');
						window.location.href = updateQueryStringInUrl(
							'skautisSearchUsers',
							'',
							window.location.href
						);
					});
				$('.dt-search').append($clearButton);

				$('.dt-search input').val(searchString);
			}
		},
	});

	$('.thickbox').on('click', function () {
		const $this = $(this);
		let userName =
			$this.parents('tr').find('.firstName').html() +
			' ' +
			$this.parents('tr').find('.lastName').html();
		const nickName = $this.parents('tr').find('.nickName').html();
		if (nickName) {
			userName += ' (' + nickName + ')';
		}

		$('#connectUserToSkautisModal_username').html(userName);

		const $connectUserToSkautisModalConnectLink = $(
			'#connectUserToSkautisModal_connectLink'
		);
		$connectUserToSkautisModalConnectLink.attr(
			'href',
			updateQueryStringInUrl(
				'skautisUserId',
				$this.parents('tr').find('.skautisUserId').html(),
				$connectUserToSkautisModalConnectLink.attr('href')!
			)
		);

		const $connectUserToSkautisModalRegisterLink = $(
			'#connectUserToSkautisModal_registerLink'
		);
		const newHref = updateQueryStringInUrl(
			'skautisUserId',
			$this.parents('tr').find('.skautisUserId').html(),
			$connectUserToSkautisModalRegisterLink.attr('href')!
		);
		$connectUserToSkautisModalRegisterLink.attr('href', newHref);
	});

	$('#connectUserToSkautisModal_select').on('change', function () {
		const $this = $(this);
		const $connectUserToSkautisModalConnectLink = $(
			'#connectUserToSkautisModal_connectLink'
		);
		const wpUserId = isNaN(Number($this.val()))
			? ''
			: ($this.val() as string);
		$connectUserToSkautisModalConnectLink.attr(
			'href',
			updateQueryStringInUrl(
				'wpUserId',
				wpUserId,
				$connectUserToSkautisModalConnectLink.attr('href')!
			)
		);
	});

	$('#connectUserToSkautisModal_defaultRole')
		.on('change', function () {
			const $this = $(this);
			const $connectUserToSkautisModalRegisterLink = $(
				'#connectUserToSkautisModal_registerLink'
			);
			$connectUserToSkautisModalRegisterLink.attr(
				'href',
				updateQueryStringInUrl(
					'wpRole',
					$this.val() as string,
					$connectUserToSkautisModalRegisterLink.attr('href')!
				)
			);
		})
		.trigger('change');
})(jQuery);

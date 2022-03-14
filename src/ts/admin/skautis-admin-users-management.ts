/// <reference types="datatables.net"/>

( function ( $ ): void {
	document.styleSheets[ 0 ].addRule(
		'.skautis-user-management-table th span:after',
		'background-image: url(' +
			skautisIntegrationAdminUsersManagementLocalize.datatablesFilesUrl +
			'/sort_asc.png);'
	);
	document.styleSheets[ 0 ].addRule(
		'.skautis-user-management-table th.sorting_desc span:after',
		'background-image: url(' +
			skautisIntegrationAdminUsersManagementLocalize.datatablesFilesUrl +
			'/sort_desc.png);'
	);

	const $dataTable = $( '.skautis-user-management-table' ).DataTable( {
		responsive: true,
		pageLength: 25,
		stateSave: true,
		language: {
			url:
				skautisIntegrationAdminUsersManagementLocalize.datatablesFilesUrl +
				'/cs.json',
			search: 'Hledat',
		},
		initComplete: function () {
			const searchString = getQueryStringFromUrl(
				'skautisSearchUsers',
				window.location.href
			);

			if ( $dataTable.data().length >= 500 ) {
				const $input = $( '.dataTables_filter input' ).unbind(),
					$searchButton = $( '<button>' )
						.text( $dataTable.i18n( 'search', 'Search' ) )
						.addClass( 'button button-secondary' )
						.click( function () {
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
						} );
				$input.on( 'keyup', function ( e ) {
					e.preventDefault();
					if ( e.keyCode === 13 ) {
						$searchButton.trigger( 'click' );
					}
				} );
				$( '.dataTables_filter' ).append( $searchButton );
			}

			if ( searchString ) {
				const $clearButton = $( '<button>' )
					.text(
						skautisIntegrationAdminUsersManagementLocalize.cancel
					)
					.addClass( 'button button-secondary' )
					.click( function () {
						$( '.dataTables_filter input' ).val( '' );
						window.location.href = updateQueryStringInUrl(
							'skautisSearchUsers',
							'',
							window.location.href
						);
					} );
				$( '.dataTables_filter' ).append( $clearButton );

				$( '.dataTables_filter input' ).val( searchString );
			}
		},
	} );

	$( '.skautis-user-management-table' ).on( 'init.dt', function () {
		$( this )
			.find( 'th' )
			.each( function () {
				$( this ).html( '<span>' + $( this ).html() + '</span>' );
			} );
	} );

	$( '.thickbox' ).on( 'click', function () {
		const $this = $( this );
		const userName =
			$this.parents( 'tr' ).find( '.firstName' ).html() +
			' ' +
			$this.parents( 'tr' ).find( '.lastName' ).html();
		const nickName = $this.parents( 'tr' ).find( '.nickName' ).html();
		if ( nickName ) {
			userName += ' (' + nickName + ')';
		}

		$( '#connectUserToSkautisModal_username' ).html( userName );

		const $connectUserToSkautisModalConnectLink = $(
			'#connectUserToSkautisModal_connectLink'
		);
		$connectUserToSkautisModalConnectLink.attr(
			'href',
			updateQueryStringInUrl(
				'skautisUserId',
				$this.parents( 'tr' ).find( '.skautisUserId' ).html(),
				$connectUserToSkautisModalConnectLink.attr( 'href' )!
			)
		);

		const $connectUserToSkautisModalRegisterLink = $(
			'#connectUserToSkautisModal_registerLink'
		);
		const newHref = updateQueryStringInUrl(
			'skautisUserId',
			$this.parents( 'tr' ).find( '.skautisUserId' ).html(),
			$connectUserToSkautisModalRegisterLink.attr( 'href' )!
		);
		$connectUserToSkautisModalRegisterLink.attr( 'href', newHref );
	} );

	$( '#connectUserToSkautisModal_select' ).on( 'change', function () {
		const $this = $( this );
		const $connectUserToSkautisModalConnectLink = $(
			'#connectUserToSkautisModal_connectLink'
		);
		if ( $.isNumeric( $this.val() ) ) {
			$connectUserToSkautisModalConnectLink.attr(
				'href',
				updateQueryStringInUrl(
					'wpUserId',
					$this.val() as string,
					$connectUserToSkautisModalConnectLink.attr( 'href' )!
				)
			);
		} else {
			$connectUserToSkautisModalConnectLink.attr(
				'href',
				updateQueryStringInUrl(
					'wpUserId',
					'',
					$connectUserToSkautisModalConnectLink.attr( 'href' )!
				)
			);
		}
	} );

	$( '#connectUserToSkautisModal_defaultRole' )
		.on( 'change', function () {
			const $this = $( this );
			const $connectUserToSkautisModalRegisterLink = $(
				'#connectUserToSkautisModal_registerLink'
			);
			$connectUserToSkautisModalRegisterLink.attr(
				'href',
				updateQueryStringInUrl(
					'wpRole',
					$this.val() as string,
					$connectUserToSkautisModalRegisterLink.attr( 'href' )!
				)
			);
		} )
		.trigger( 'change' );
} )( jQuery );

function updateQueryStringInUrl(
	key: string,
	value: string,
	url: string
): string {
	const re = new RegExp( '([?&])' + key + '=.*?(&|#|$)(.*)', 'gi' );

	if ( re.test( url ) ) {
		if ( typeof value !== 'undefined' && value !== null ) {
			return url.replace( re, '$1' + key + '=' + value + '$2$3' );
		}
		const hash = url.split( '#' );
		url = hash[ 0 ].replace( re, '$1$3' ).replace( /(&|\?)$/, '' );
		if ( typeof hash[ 1 ] !== 'undefined' && hash[ 1 ] !== null )
			url += '#' + hash[ 1 ];
		return url;
	}
	if ( typeof value !== 'undefined' && value !== null ) {
		const separator = url.includes( '?' ) ? '&' : '?';
		const hash = url.split( '#' );
		url = hash[ 0 ] + separator + key + '=' + value;
		if ( typeof hash[ 1 ] !== 'undefined' && hash[ 1 ] !== null )
			url += '#' + hash[ 1 ];
		return url;
	}
	return url;
}

function getQueryStringFromUrl( key: string, url: string ): string {
	key = key.replace( /[[]/, '\\[' ).replace( /[\]]/, '\\]' );
	const regex = new RegExp( '[\\?&]' + key + '=([^&#]*)' ),
		results = regex.exec( url );
	return results === null
		? ''
		: decodeURIComponent( results[ 1 ].replace( /\+/g, ' ' ) );
}

/* exported Role */

class Role {
	private readonly roles: Record< string, string >;
	private unitOperators: Record< string, string >;

	public constructor( roles: Record< string, string > ) {
		this.roles = roles;
		this.unitOperators = {};
		this.unitOperators.equal = 'equal';
		this.unitOperators.begins_with = 'begins_with';
		this.unitOperators.any = 'any';
	}

	public input( _: QueryBuilderRule, inputName: string ): string {
		this.unitOperators.equal =
			jQuery.fn.queryBuilder.regional.cs.operators.equal;
		this.unitOperators.begins_with =
			jQuery.fn.queryBuilder.regional.cs.operators.begins_with;
		this.unitOperators.any =
			jQuery.fn.queryBuilder.regional.cs.operators.any;

		let html =
			'<select class="form-control select2" name="' +
			inputName +
			'_1" multiple="multiple">';

		for ( const key in this.roles ) {
			if ( Object.prototype.hasOwnProperty.call( this.roles, key ) ) {
				html +=
					'<option value="' +
					key +
					'">' +
					this.roles[ key ] +
					'</option>';
			}
		}

		html +=
			'</select><div style="margin-top: 0.6em;">' +
			skautisIntegrationRulesLocalize.inUnitWithNumber;
		html +=
			'<select class="multi-rules form-control skautis-rule-unitnumber-select" name="' +
			inputName +
			'_2">';

		for ( const key in this.unitOperators ) {
			if (
				Object.prototype.hasOwnProperty.call( this.unitOperators, key )
			) {
				html +=
					'<option value="' +
					key +
					'">' +
					this.unitOperators[ key ] +
					'</option>';
			}
		}

		html += '</select><div class="multi-rules input-container">';
		html +=
			'<input class="form-control skautis-rule-unitnumber-input" type="text" name="' +
			inputName +
			'_3" value="" placeholder="' +
			skautisIntegrationRulesLocalize.unitNumber +
			'" />';
		html += '</div></div>';
		return html;
	}

	public validation(): QueryBuilderValidation {
		return {
			format: /^(?!null)[^~]+~(?!null)[^~]+~(?!null)[^~]+$/,
		};
	}

	public valueGetter( rule: QueryBuilderRule ): string {
		return (
			rule.$el.find( '.rule-value-container [name$=_1]' ).val() +
			'~' +
			rule.$el.find( '.rule-value-container [name$=_2]' ).val() +
			'~' +
			rule.$el.find( '.rule-value-container [name$=_3]' ).val()
		);
	}

	public valueSetter( rule: QueryBuilderRule, value: string ): void {
		if ( rule.operator.nb_inputs > 0 ) {
			const val = value.split( '~' );

			const val0 = val[ 0 ].split( ',' );

			for ( const item of val0 ) {
				rule.$el
					.find(
						'.rule-value-container [name$=_1] option[value="' +
							item +
							'"]'
					)
					.prop( 'selected', true );
			}

			rule.$el
				.find( '.rule-value-container [name$=_2]' )
				.val( val[ 1 ] )
				.trigger( 'change' );
			rule.$el
				.find( '.rule-value-container [name$=_3]' )
				.val( val[ 2 ] )
				.trigger( 'change' );
		}
	}
}

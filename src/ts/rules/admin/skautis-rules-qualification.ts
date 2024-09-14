/* exported Qualification */

class Qualification {
	private readonly qualifications: Record<string, string>;

	public constructor(qualifications: Record<string, string>) {
		this.qualifications = qualifications;
	}

	public input(_: QueryBuilderRule, inputName: string): string {
		let html =
			'<select class="form-control select2" name="' +
			inputName +
			'_1" multiple="multiple">';

		for (const key in this.qualifications) {
			if (
				Object.prototype.hasOwnProperty.call(this.qualifications, key)
			) {
				html +=
					'<option value="' +
					key +
					'">' +
					this.qualifications[key] +
					'</option>';
			}
		}

		html += '</select>';
		return html;
	}

	public validation(): QueryBuilderValidation {
		return {
			format: /^(?!null)[^~]+$/,
		};
	}

	public valueGetter(rule: QueryBuilderRule): string {
		return (
			rule.$el
				.find('.rule-value-container [name$=_1]')
				.val()
				?.toString() ?? ''
		);
	}

	public valueSetter(rule: QueryBuilderRule, value: string): void {
		if (rule.operator.nb_inputs > 0) {
			const val0 = value.split(',');

			for (const item of val0) {
				rule.$el
					.find(
						'.rule-value-container [name$=_1] option[value="' +
							item +
							'"]'
					)
					.prop('selected', true);
			}
		}
	}
}

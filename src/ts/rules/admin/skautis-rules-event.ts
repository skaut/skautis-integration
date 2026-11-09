/* exported SkautisEvent */

/**
 * The input of the rule for the SkautIS events. The value is `event IDs~relation`.
 *
 * Named SkautisEvent, because Event is a global of the browser.
 */
class SkautisEvent {
	private readonly events: Record<string, string>;
	private readonly order: Array<string>;
	private readonly relations: Record<string, string>;

	/**
	 * @param events The events in the form `ID => name`.
	 * @param order  The event IDs in the order to show, because the browser orders numeric keys by the number.
	 */
	public constructor(events: Record<string, string>, order: Array<string>) {
		this.events = events;
		this.order = order.filter((key) =>
			Object.prototype.hasOwnProperty.call(events, key)
		);
		this.relations = {
			any: skautisIntegrationRulesLocalize.eventRelationAny,
			participant: skautisIntegrationRulesLocalize.eventRelationOther,
			team: skautisIntegrationRulesLocalize.eventRelationTeam,
		};
	}

	public input(_: QueryBuilderRule, inputName: string): string {
		let html = `<select class="form-control select2" name="${
			inputName
		}_1" multiple="multiple">`;

		for (const key of this.order) {
			html += `<option value="${key}">${this.events[key]}</option>`;
		}

		html += `</select><div style="margin-top: 0.6em;">${
			skautisIntegrationRulesLocalize.eventRelation
		} <select class="multi-rules form-control skautis-rule-event-relation" name="${
			inputName
		}_2" aria-label="${skautisIntegrationRulesLocalize.eventRelation}">`;

		for (const key in this.relations) {
			if (Object.prototype.hasOwnProperty.call(this.relations, key)) {
				html += `<option value="${key}">${this.relations[key]}</option>`;
			}
		}

		html += '</select></div>';
		return html;
	}

	public validation(): QueryBuilderValidation {
		return {
			format: /^(?!null)[^~]+~(?:any|participant|team)$/u,
		};
	}

	public valueGetter(rule: QueryBuilderRule): string {
		const events = rule.$el
			.find('.rule-value-container [name$=_1]')
			.val() as Array<string> | null;
		return `${(events ?? []).toString()}~${
			rule.$el.find('.rule-value-container [name$=_2]').val() as string
		}`;
	}

	public valueSetter(rule: QueryBuilderRule, value: string): void {
		if (rule.operator.nb_inputs > 0) {
			const val = value.split('~');

			for (const item of val[0].split(',')) {
				rule.$el
					.find(
						`.rule-value-container [name$=_1] option[value="${item}"]`
					)
					.prop('selected', true);
			}

			rule.$el
				.find('.rule-value-container [name$=_2]')
				.val(val[1] ?? 'any')
				.trigger('change');
		}
	}
}

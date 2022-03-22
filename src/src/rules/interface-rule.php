<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

interface Rule {
	public function get_id(): string;

	public function get_label(): string;

	public function get_type(): string;

	public function get_input(): string;

	public function get_multiple(): bool;

	public function get_operators(): array;

	public function get_placeholder(): string;

	public function get_description(): string;

	public function get_values(): array;

	public function is_rule_passed( string $operator, $data ): bool;
}

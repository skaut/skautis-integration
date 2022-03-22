<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

interface Rule {
	public function get_id(): string;

	public function getLabel(): string;

	public function getType(): string;

	public function getInput(): string;

	public function getMultiple(): bool;

	public function getOperators(): array;

	public function getPlaceholder(): string;

	public function getDescription(): string;

	public function getValues(): array;

	public function isRulePassed( string $operator, $data ): bool;
}

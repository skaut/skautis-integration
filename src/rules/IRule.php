<?php

namespace SkautisIntegration\Rules;

interface IRule {
	public function getId();

	public function getLabel();

	public function getType();

	public function getInput();

	public function getMultiple();

	public function getValues();

	public function getOperators();

	public function getPlaceholder();

	public function getDescription();

	public function isRulePassed( $operator, $value );
}
<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules;

interface Module {
	public static function get_label(): string;

	public static function get_id(): string;

	public static function get_path(): string;

	public static function get_url(): string;
}

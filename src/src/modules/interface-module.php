<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules;

interface Module {
	public static function getLabel(): string;

	public static function get_id(): string;

	public static function getPath(): string;

	public static function getUrl(): string;
}

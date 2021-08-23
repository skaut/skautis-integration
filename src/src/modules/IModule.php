<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules;

interface IModule {
	public static function getLabel(): string;

	public static function getId(): string;

	public static function getPath(): string;

	public static function getUrl(): string;
}

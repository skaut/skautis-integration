<?php

namespace SkautisIntegration\Modules;

interface IModule {
	public static function getLabel();

	public static function getId();

	public static function getPath();

	public static function getUrl();
}
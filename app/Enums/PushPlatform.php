<?php

namespace App\Enums;

enum PushPlatform: string
{
    case Web = 'web';
    case Android = 'android';
    case Ios = 'ios';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web browser',
            self::Android => 'Android app',
            self::Ios => 'iOS app',
        };
    }
}

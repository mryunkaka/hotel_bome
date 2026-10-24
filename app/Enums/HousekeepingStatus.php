<?php

namespace App\Enums;

enum HousekeepingStatus: string
{
    case Dirty = 'dirty';
    case Clean = 'clean';
    case Inspected = 'inspected';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Dirty => $next === self::Clean,
            self::Clean => in_array($next, [self::Dirty, self::Inspected], true),
            self::Inspected => $next === self::Dirty,
        };
    }
}

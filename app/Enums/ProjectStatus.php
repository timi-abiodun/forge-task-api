<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case PLANNING = 'planning';
    case ACTIVE = 'active';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Optional: Helper to get a human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::PLANNING => 'Planning Phase',
            self::ACTIVE => 'Currently Active',
            self::ON_HOLD => 'On Hold',
            self::COMPLETED => 'Finished',
            self::CANCELLED => 'Abandoned',
        };
    }
}
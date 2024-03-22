<?php

namespace Modules\DisposableSpecial\Models\Enums;

use App\Contracts\Enum;

class DS_ItemCategory extends Enum
{
    public const GENERIC = 1;
    public const AIRCRAFT = 2;
    public const LIVERY = 3;
    public const SCENERY = 4;
    public const TOOL = 5;
    public const TRAINING = 6;
    public const TYPERATING = 7;
    public const TOUR = 8;

    public static array $labels = [
        self::GENERIC => 'Generic Item',
        self::AIRCRAFT => 'Aircraft | Addon',
        self::LIVERY => 'Aircraft | Livery',
        self::SCENERY => 'Scenery',
        self::TOOL => 'Tool | Addon',
        self::TYPERATING => 'Type Rating',
        self::TRAINING => 'Training',
        self::TOUR => 'Tour Token',
    ];
}

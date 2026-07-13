<?php

namespace App\Enums;

enum FlashSaleStatusEnum: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SCHEDULED => 'Scheduled',
            self::ACTIVE => 'Active',
            self::ENDED => 'Ended',
            self::DISABLED => 'Disabled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'badge bg-secondary text-white',
            self::SCHEDULED => 'badge bg-info text-white',
            self::ACTIVE => 'badge bg-success text-white',
            self::ENDED => 'badge bg-dark text-white',
            self::DISABLED => 'badge bg-danger text-white',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}

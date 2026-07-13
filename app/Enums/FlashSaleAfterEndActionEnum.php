<?php

namespace App\Enums;

enum FlashSaleAfterEndActionEnum: string
{
    case REVERT_PRICE = 'revert_price';
    case HIDE = 'hide';
    case SOLD_OUT = 'sold_out';
    case KEEP_SHOWING = 'keep_showing';

    public function label(): string
    {
        return match ($this) {
            self::REVERT_PRICE => 'Kembali ke Harga Normal',
            self::HIDE => 'Sembunyikan Flash Sale',
            self::SOLD_OUT => 'Tampilkan Sold Out',
            self::KEEP_SHOWING => 'Tetap Tampil',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}

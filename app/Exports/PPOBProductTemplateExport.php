<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PPOBProductTemplateExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * Example rows to guide the admin filling out the template.
     */
    public function array(): array
    {
        return [
            [
                'Games',
                'Mobile Legends',
                'Diamonds',
                '86 Diamonds',
                'ML86',
                'digiflazz',
                15000,
                17000,
                'Top up 86 Diamond Mobile Legends',
                0,
                1,
                'https://example.com/images/ml86.png',
            ],
            [
                'Games',
                'PUBG Mobile',
                '',
                '660 UC',
                'PUBG660',
                'manual_topup',
                150000,
                165000,
                '',
                1,
                1,
                '',
            ],
        ];
    }

    /**
     * Column headings. `category`, `brand`, `sku`, etc. below refer to how
     * Maatwebsite\Excel's WithHeadingRow slugifies these on import.
     */
    public function headings(): array
    {
        return [
            'Category',
            'Brand',
            'Product Category',
            'Name',
            'SKU',
            'Provider',
            'Buy Price',
            'Sell Price',
            'Description',
            'Delay',
            'Status',
            'Image URL',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

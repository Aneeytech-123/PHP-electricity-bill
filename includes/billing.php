<?php
/**
 * Billing logic for the Electricity Bill Calculator.
 *
 * Slab-wise tariff:
 *   First 50 units          Rs. 3.50 / unit
 *   Next 100 units (51-150) Rs. 4.00 / unit
 *   Next 100 units (151-250) Rs. 5.20 / unit
 *   Above 250 units         Rs. 6.50 / unit
 */

declare(strict_types=1);

const MAX_UNITS = 100000;

/** Slab definitions: label, range text, units in slab (null = no limit), rate, colour. */
const SLABS = [
    ['label' => 'First 50 units',  'range' => '0 – 50',    'size' => 50,   'rate' => 3.50, 'color' => '#f5a524'],
    ['label' => 'Next 100 units',  'range' => '51 – 150',  'size' => 100,  'rate' => 4.00, 'color' => '#2dd4bf'],
    ['label' => 'Next 100 units',  'range' => '151 – 250', 'size' => 100,  'rate' => 5.20, 'color' => '#818cf8'],
    ['label' => 'Above 250 units', 'range' => '250+',      'size' => null, 'rate' => 6.50, 'color' => '#f87171'],
];

/**
 * Total bill for the given units, using if-else on the unit range.
 */
function calculate_bill(int $units): float
{
    if ($units <= 50) {
        $bill = $units * 3.50;
    } elseif ($units <= 150) {
        $bill = 50 * 3.50 + ($units - 50) * 4.00;
    } elseif ($units <= 250) {
        $bill = 50 * 3.50 + 100 * 4.00 + ($units - 150) * 5.20;
    } else {
        $bill = 50 * 3.50 + 100 * 4.00 + 100 * 5.20 + ($units - 250) * 6.50;
    }
    return round($bill, 2);
}

/**
 * Units and cost charged in each slab, for the breakdown table.
 *
 * @return array<int, array{label:string, range:string, rate:float, color:string, units:int, cost:float, size:int|null}>
 */
function slab_breakdown(int $units): array
{
    $rows = [];
    $remaining = $units;
    foreach (SLABS as $slab) {
        $inSlab = $slab['size'] === null ? max(0, $remaining) : max(0, min($remaining, $slab['size']));
        $rows[] = $slab + ['units' => $inSlab, 'cost' => round($inSlab * $slab['rate'], 2)];
        $remaining -= $inSlab;
    }
    return $rows;
}

/** Formats a rupee amount as "Rs. 1,234.50". */
function money(float $amount): string
{
    return 'Rs. ' . number_format($amount, 2);
}

/** Escapes text for safe output in HTML. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validates the submitted form.
 *
 * @param array<string, mixed> $input
 * @return array{data: array<string, mixed>, errors: array<string, string>}
 */
function validate_input(array $input): array
{
    $name     = trim((string)($input['name'] ?? ''));
    $consumer = trim((string)($input['consumer'] ?? ''));
    $month    = trim((string)($input['month'] ?? ''));
    $unitsRaw = trim((string)($input['units'] ?? ''));
    $errors   = [];

    if ($name === '' || mb_strlen($name) > 60) {
        $errors['name'] = 'Enter the customer name (up to 60 characters).';
    } elseif (!preg_match("/^[\\p{L} .'-]+$/u", $name)) {
        $errors['name'] = 'Name can contain letters, spaces, dots, hyphens and apostrophes only.';
    }

    if (!preg_match('/^\d{5,15}$/', $consumer)) {
        $errors['consumer'] = 'Consumer number must be 5 to 15 digits.';
    }

    if ($month !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
        $errors['month'] = 'Choose a valid billing month.';
    }

    if ($unitsRaw === '' || !preg_match('/^\d+$/', $unitsRaw)) {
        $errors['units'] = 'Units must be a whole number (0 or more).';
    } elseif ((int)$unitsRaw > MAX_UNITS) {
        $errors['units'] = 'Units cannot be more than ' . number_format(MAX_UNITS) . '.';
    }

    return [
        'data' => [
            'name'     => $name,
            'consumer' => $consumer,
            'month'    => $month,
            'units'    => $unitsRaw,
        ],
        'errors' => $errors,
    ];
}

/** "2026-10" -> "October 2026" */
function month_label(string $month): string
{
    if ($month === '') {
        return date('F Y');
    }
    $date = DateTime::createFromFormat('!Y-m', $month);
    return $date ? $date->format('F Y') : $month;
}

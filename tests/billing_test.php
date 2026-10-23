<?php
// Run with: php tests/billing_test.php
declare(strict_types=1);

require __DIR__ . '/../includes/billing.php';

$cases = [
    0 => 0.00, 1 => 3.50, 50 => 175.00, 51 => 179.00, 100 => 375.00, 150 => 575.00,
    151 => 580.20, 200 => 835.00, 250 => 1095.00, 251 => 1101.50, 320 => 1550.00, 1250 => 7595.00,
];

$failed = 0;
foreach ($cases as $units => $expected) {
    $bill = calculate_bill($units);
    $sum = array_sum(array_column(slab_breakdown($units), 'cost'));
    $unitSum = array_sum(array_column(slab_breakdown($units), 'units'));
    $ok = abs($bill - $expected) < 0.001 && abs($sum - $expected) < 0.001 && $unitSum === $units;
    if (!$ok) $failed++;
    printf("%s %5d units -> %s (expected %s, breakdown %s)\n", $ok ? 'PASS' : 'FAIL', $units, money($bill), money($expected), money($sum));
}

$v = validate_input(['name' => '', 'consumer' => '12', 'month' => '2026-13', 'units' => '-5']);
$ok = array_keys($v['errors']) === ['name', 'consumer', 'month', 'units'];
if (!$ok) $failed++;
echo ($ok ? 'PASS' : 'FAIL') . " invalid input rejected: " . implode(', ', array_keys($v['errors'])) . "\n";

$v = validate_input(['name' => "Aneey O'Brien", 'consumer' => '170234519870', 'month' => '2026-10', 'units' => '320']);
$ok = $v['errors'] === [];
if (!$ok) $failed++;
echo ($ok ? 'PASS' : 'FAIL') . " valid input accepted\n";

echo $failed ? "\n$failed test(s) failed\n" : "\nAll tests passed\n";
exit($failed ? 1 : 0);

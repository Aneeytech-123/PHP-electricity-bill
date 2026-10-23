<?php
declare(strict_types=1);

require __DIR__ . '/includes/billing.php';

$pageTitle = 'Electricity Bill Calculator';
$form      = ['name' => '', 'consumer' => '', 'month' => date('Y-m'), 'units' => ''];
$errors    = [];
$result    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checked = validate_input($_POST);
    $form    = $checked['data'];
    $errors  = $checked['errors'];

    if (!$errors) {
        $units  = (int)$form['units'];
        $result = [
            'units'     => $units,
            'total'     => calculate_bill($units),
            'slabs'     => slab_breakdown($units),
            'month'     => month_label($form['month']),
            'billNo'    => 'WW-' . substr($form['consumer'], -4) . '-' . date('ymd'),
            'issuedOn'  => date('d M Y'),
        ];
        $result['average'] = $units > 0 ? $result['total'] / $units : 0;
    }
}

/** Error message for a field, or empty string. */
function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="field-error" id="' . $key . '-error">' . e($errors[$key]) . '</small>' : '';
}

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <span class="eyebrow">Electricity bill calculator</span>
        <h1>Know your bill <span class="accent">before it arrives.</span></h1>
        <p class="lead">Enter your meter reading and WattWise calculates your electricity bill slab by slab, exactly the way the tariff is applied.</p>
    </div>
</section>

<section class="container calc-grid" id="calculator">
    <!-- Input form -->
    <div class="card form-card">
        <h2 class="card-title"><span class="dot"></span> Customer &amp; reading</h2>

        <?php if ($errors): ?>
            <div class="alert" role="alert">Please fix the highlighted fields and try again.</div>
        <?php endif; ?>

        <form method="post" action="index.php#result" novalidate id="bill-form">
            <label class="field <?= isset($errors['name']) ? 'invalid' : '' ?>">
                <span>Customer name</span>
                <input type="text" name="name" value="<?= e($form['name']) ?>" maxlength="60"
                       placeholder="e.g. Aneey Sharma" autocomplete="name" required
                       <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                <?= field_error($errors, 'name') ?>
            </label>

            <div class="field-row">
                <label class="field <?= isset($errors['consumer']) ? 'invalid' : '' ?>">
                    <span>Consumer number</span>
                    <input type="text" name="consumer" value="<?= e($form['consumer']) ?>" inputmode="numeric"
                           pattern="\d{5,15}" maxlength="15" placeholder="e.g. 170234519870" required
                           <?= isset($errors['consumer']) ? 'aria-invalid="true" aria-describedby="consumer-error"' : '' ?>>
                    <?= field_error($errors, 'consumer') ?>
                </label>

                <label class="field <?= isset($errors['month']) ? 'invalid' : '' ?>">
                    <span>Billing month</span>
                    <input type="month" name="month" value="<?= e($form['month']) ?>">
                    <?= field_error($errors, 'month') ?>
                </label>
            </div>

            <label class="field <?= isset($errors['units']) ? 'invalid' : '' ?>">
                <span>Units consumed (kWh)</span>
                <input type="number" name="units" id="units" value="<?= e($form['units']) ?>" min="0" max="<?= MAX_UNITS ?>"
                       step="1" placeholder="e.g. 320" class="units-input" required
                       <?= isset($errors['units']) ? 'aria-invalid="true" aria-describedby="units-error"' : '' ?>>
                <?= field_error($errors, 'units') ?>
            </label>

            <div class="live" id="live-estimate" aria-live="polite" hidden>
                <span>Live estimate</span>
                <strong id="live-amount">Rs. 0.00</strong>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Calculate bill</button>
                <a href="index.php" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <!-- Result or tariff summary -->
    <div class="card result-card" id="result">
        <?php if ($result): ?>
            <div class="bill-head">
                <div>
                    <h2 class="card-title"><span class="dot"></span> Bill breakdown</h2>
                    <p class="muted mono small">Bill no. <?= e($result['billNo']) ?> · Issued <?= e($result['issuedOn']) ?></p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">Print bill</button>
            </div>

            <div class="total-box">
                <span class="mono label">Total payable</span>
                <div class="total-amount" data-amount="<?= e(number_format($result['total'], 2, '.', '')) ?>">
                    <?= e(money($result['total'])) ?>
                </div>
                <span class="muted mono small"><?= number_format($result['units']) ?> units · <?= e($result['month']) ?></span>
            </div>

            <dl class="customer">
                <div><dt>Customer</dt><dd><?= e($form['name']) ?></dd></div>
                <div><dt>Consumer no.</dt><dd class="mono"><?= e($form['consumer']) ?></dd></div>
                <div><dt>Billing month</dt><dd><?= e($result['month']) ?></dd></div>
                <div><dt>Avg. per unit</dt><dd class="mono"><?= $result['units'] > 0 ? e(money($result['average'])) : '—' ?></dd></div>
            </dl>

            <div class="table-wrap">
                <table class="slab-table">
                    <thead>
                    <tr><th>Slab</th><th class="num">Units</th><th class="num">Rate</th><th class="num">Cost</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($result['slabs'] as $row):
                        $share = $result['units'] > 0 ? $row['units'] / $result['units'] * 100 : 0; ?>
                        <tr class="<?= $row['units'] === 0 ? 'unused' : '' ?>">
                            <td>
                                <span class="slab-name"><?= e($row['label']) ?> <small class="muted">(<?= e($row['range']) ?>)</small></span>
                                <span class="bar"><span style="width: <?= round($share, 1) ?>%; background: <?= e($row['color']) ?>"></span></span>
                            </td>
                            <td class="num mono"><?= number_format($row['units']) ?></td>
                            <td class="num mono"><?= e(number_format($row['rate'], 2)) ?></td>
                            <td class="num mono"><?= e(money($row['cost'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr><td>Total</td><td class="num mono"><?= number_format($result['units']) ?></td><td></td><td class="num mono"><?= e(money($result['total'])) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <h2 class="card-title"><span class="dot"></span> Your bill will appear here</h2>
            <p class="muted">Fill in the form and press <strong>Calculate bill</strong> to see the total and a slab-by-slab breakdown.</p>
            <div class="example">
                <span class="mono label">Example: 320 units</span>
                <ul class="example-list mono">
                    <li><span>50 × 3.50</span><span>175.00</span></li>
                    <li><span>100 × 4.00</span><span>400.00</span></li>
                    <li><span>100 × 5.20</span><span>520.00</span></li>
                    <li><span>70 × 6.50</span><span>455.00</span></li>
                    <li class="sum"><span>Total</span><span>Rs. 1,550.00</span></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="container section" id="tariff">
    <div class="section-head">
        <span class="eyebrow">Tariff</span>
        <h2>Slab-wise rates</h2>
        <p class="muted">Each slab is charged at its own rate. Only the units inside a slab use that slab's price.</p>
    </div>
    <div class="slab-cards">
        <?php foreach (SLABS as $slab): ?>
            <div class="slab-card" style="--slab: <?= e($slab['color']) ?>">
                <span class="mono label"><?= e($slab['range']) ?> units</span>
                <strong class="mono">Rs. <?= e(number_format($slab['rate'], 2)) ?></strong>
                <span class="muted mono small">per unit</span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section" id="how">
    <div class="section-head">
        <span class="eyebrow">How it works</span>
        <h2>From reading to bill in three steps</h2>
    </div>
    <div class="steps">
        <div class="step"><span class="step-no mono">01</span><h3>Enter details</h3><p>Customer name, consumer number, billing month and units consumed.</p></div>
        <div class="step"><span class="step-no mono">02</span><h3>PHP calculates</h3><p>The server checks your input and applies the slab rates with if-else conditions.</p></div>
        <div class="step"><span class="step-no mono">03</span><h3>View &amp; print</h3><p>See the total, how much each slab costs, and print or save the bill.</p></div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

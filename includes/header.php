<?php /** @var string $pageTitle */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Calculate your electricity bill slab by slab.">
    <title><?= e($pageTitle ?? 'Electricity Bill Calculator') ?> | WattWise</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23f5a524'/><path d='M18 5L9 18h6l-2 9 10-14h-6z' fill='%23111827'/></svg>">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="brand" aria-label="WattWise home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M13.5 2L5 13.5h6L9.5 22 19 9.5h-6z"/></svg>
            </span>
            WattWise
        </a>
        <nav class="nav" aria-label="Main">
            <a href="index.php#calculator">Calculator</a>
            <a href="index.php#tariff">Tariff</a>
            <a href="index.php#how">How it works</a>
        </nav>
    </div>
</header>
<main>

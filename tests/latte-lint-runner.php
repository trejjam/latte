<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$linter = new Latte\Tools\Linter(debug: false, strict: true);
$latte = $linter->getEngine();
$latte->setStrictParsing();
$latte->addExtension(new Trejjam\Latte\TrejjamLatteExtension());

exit($linter->scanDirectory(__DIR__ . '/fixtures') ? 0 : 1);

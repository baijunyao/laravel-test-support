<?php

declare(strict_types=1);

use Baijunyao\PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in(__DIR__.'/src');

return (new Config())->setFinder($finder);

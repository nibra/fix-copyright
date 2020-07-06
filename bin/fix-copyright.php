<?php

use NX\GitHistory;

require dirname(__DIR__) . '/vendor/autoload.php';

if ($argc < 2) {
    throw new RuntimeException('Specify a file');
}
if ($argc > 2) {
    throw new RuntimeException('Specify just one file');
}

echo (new GitHistory())->findCreationDate($argv[1]);

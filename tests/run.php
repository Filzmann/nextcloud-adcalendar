<?php

declare(strict_types=1);

$tests = array_merge(
    glob(__DIR__ . '/Model/*Test.php') ?: [],
    glob(__DIR__ . '/Controller/*Test.php') ?: [],
    glob(__DIR__ . '/Service/*Test.php') ?: [],
    glob(__DIR__ . '/Command/*Test.php') ?: [],
);
foreach ($tests as $test) {
    require $test;
}

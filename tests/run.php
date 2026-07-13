<?php

declare(strict_types=1);

require_once __DIR__ . '/../../localbase/lib/Organization/AdOrganizationHierarchy.php';
require_once __DIR__ . '/../../localbase/lib/Organization/AdOrganizationPermissionPolicy.php';

$tests = array_merge(
    glob(__DIR__ . '/Model/*Test.php') ?: [],
    glob(__DIR__ . '/Controller/*Test.php') ?: [],
    glob(__DIR__ . '/Service/*Test.php') ?: [],
    glob(__DIR__ . '/Command/*Test.php') ?: [],
    glob(__DIR__ . '/Ui/*Test.php') ?: [],
);
foreach ($tests as $test) {
    require $test;
}

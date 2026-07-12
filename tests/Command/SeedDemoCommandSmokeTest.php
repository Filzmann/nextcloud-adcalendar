<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../../lib/Command/SeedDemoCommand.php');
if ($source === false) throw new RuntimeException('SeedDemoCommand konnte nicht gelesen werden.');
foreach (['ROLE_OFFICE', 'ROLE_EB', 'ROLE_PFK', 'ROLE_STAFF_HR', 'ROLE_STAFF_QMB', 'existsCreatedByForEmployee', "'parentEntryId' => \$shiftId"] as $contract) {
    if (!str_contains($source, $contract)) throw new RuntimeException("Demo-Vertrag fehlt: {$contract}");
}
echo "SeedDemoCommandSmokeTest: OK\n";

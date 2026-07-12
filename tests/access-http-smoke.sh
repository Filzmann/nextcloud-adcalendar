#!/usr/bin/env bash
set -euo pipefail

: "${ADC_BASE_URL:?ADC_BASE_URL fehlt}"
: "${ADC_USER:?ADC_USER fehlt}"
: "${ADC_PASSWORD:?ADC_PASSWORD fehlt}"
: "${ADC_EXPECTED:?ADC_EXPECTED fehlt}"

response="$(mktemp)"
trap 'rm -f "$response"' EXIT
week_start="$(date -d 'monday this week' +%F)"
curl --fail --silent --show-error --insecure --user "$ADC_USER:$ADC_PASSWORD" \
    "$ADC_BASE_URL/index.php/apps/adcalendar/api/week?start=$week_start" --output "$response"

ADC_RESPONSE="$response" php -r '
$state = json_decode(file_get_contents(getenv("ADC_RESPONSE")), true, flags: JSON_THROW_ON_ERROR);
$actual = [];
foreach ($state["employees"] ?? [] as $employee) $actual[$employee["uid"]] = (bool)$employee["canManage"];
foreach (explode(",", getenv("ADC_EXPECTED")) as $expectation) {
    [$uid, $raw] = explode("=", $expectation, 2);
    $expected = $raw === "true";
    if (!array_key_exists($uid, $actual) || $actual[$uid] !== $expected) {
        fwrite(STDERR, "Rechtevertrag verletzt fuer {$uid}: erwartet " . ($expected ? "true" : "false") . ", erhalten " . json_encode($actual[$uid] ?? null) . PHP_EOL);
        exit(1);
    }
}
'

echo "AD Calendar access HTTP smoke: OK ($ADC_USER)"

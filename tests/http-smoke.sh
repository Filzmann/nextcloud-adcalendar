#!/usr/bin/env bash
set -euo pipefail

: "${ADC_BASE_URL:?ADC_BASE_URL fehlt}"
: "${ADC_USER:?ADC_USER fehlt}"
: "${ADC_PASSWORD:?ADC_PASSWORD fehlt}"

app_page="$(mktemp)"
api_response="$(mktemp)"
trap 'rm -f "$app_page" "$api_response"' EXIT

curl --fail --silent --show-error --insecure --user "$ADC_USER:$ADC_PASSWORD" \
    "$ADC_BASE_URL/index.php/apps/adcalendar/" --output "$app_page"

for contract in 'id="adcalendar-app"' 'id="adc-week-number"' 'id="adc-person-search"' 'id="adc-toggle-view"'; do
    if ! grep -q "$contract" "$app_page"; then
        echo "App-DOM-Vertrag fehlt: $contract" >&2
        exit 1
    fi
done

week_start="$(date -d 'monday this week' +%F)"
curl --fail --silent --show-error --insecure --user "$ADC_USER:$ADC_PASSWORD" \
    "$ADC_BASE_URL/index.php/apps/adcalendar/api/week?start=$week_start" --output "$api_response"
for contract in '"employees"' '"entries"' '"organization"' '"currentUserProfile"'; do
    if ! grep -q "$contract" "$api_response"; then
        echo "API-Vertrag fehlt: $contract" >&2
        exit 1
    fi
done

echo "AD Calendar HTTP smoke: OK ($ADC_USER)"

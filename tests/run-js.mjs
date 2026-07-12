import { execFileSync } from 'node:child_process';
import { readdirSync } from 'node:fs';
import { join } from 'node:path';

for (const file of readdirSync(new URL('../js', import.meta.url))) {
    if (file.endsWith('.js')) execFileSync(process.execPath, ['--check', join(new URL('../js', import.meta.url).pathname, file)], { stdio: 'inherit' });
}
execFileSync(process.execPath, [new URL('./js/calendar-workflow-smoke.mjs', import.meta.url).pathname], { stdio: 'inherit' });
console.log('JavaScript syntax: OK');

import { execFileSync } from 'node:child_process';
import { readdirSync } from 'node:fs';
import { join } from 'node:path';

for (const file of readdirSync(new URL('../js', import.meta.url))) {
    if (file.endsWith('.js')) execFileSync(process.execPath, ['--check', join(new URL('../js', import.meta.url).pathname, file)], { stdio: 'inherit' });
}
console.log('JavaScript syntax: OK');

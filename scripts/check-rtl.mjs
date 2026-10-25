// Fails CI when the frontend uses physical-direction Tailwind classes (CLAUDE.md "قواعد الـFrontend").
// Use logical utilities instead: ms-/me-, ps-/pe-, start-/end-, text-start/text-end, border-s/e, rounded-s/e/ss/se/es/ee.
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const ROOT = new URL('..', import.meta.url).pathname;
// The merchant app (ADR-002). The old Inertia app under resources/js is gone.
const SOURCE_DIRS = ['web/src', 'web/e2e'];
const EXTENSIONS = /\.(tsx?|jsx?)$/;

const IGNORED = [];

const PHYSICAL = new RegExp(
    String.raw`(?<![\w-])-?(` +
        [
            String.raw`m[lr]-[\w\[\.\/]+`,
            String.raw`p[lr]-[\w\[\.\/]+`,
            String.raw`(left|right)-[\w\[\.\/(]+`,
            String.raw`text-(left|right)\b`,
            String.raw`border-[lr](\b|-[\w\[]+)`,
            String.raw`rounded-([lr]|tl|tr|bl|br)(\b|-[\w\[]+)`,
            String.raw`scroll-[mp][lr]-[\w\[]+`,
        ].join('|') +
        ')',
    'g',
);

function* walk(dir) {
    for (const entry of readdirSync(dir)) {
        const path = join(dir, entry);
        const rel = relative(ROOT, path);

        if (IGNORED.some((ignored) => rel.startsWith(ignored))) {
            continue;
        }

        if (statSync(path).isDirectory()) {
            yield* walk(path);
        } else if (EXTENSIONS.test(entry)) {
            yield path;
        }
    }
}

const violations = [];

for (const dir of SOURCE_DIRS) {
    for (const file of walk(join(ROOT, dir))) {
        readFileSync(file, 'utf8')
            .split('\n')
            .forEach((line, index) => {
                // SVG path data and URLs are not class names.
                const scrubbed = line
                    .replace(/\sd="[^"]*"/g, '')
                    .replace(/https?:\/\/\S+/g, '');

                for (const match of scrubbed.matchAll(PHYSICAL)) {
                    violations.push(
                        `${relative(ROOT, file)}:${index + 1}  ${match[0]}`,
                    );
                }
            });
    }
}

if (violations.length > 0) {
    console.error(
        'Physical-direction classes found (use logical properties for RTL):\n',
    );
    console.error(violations.join('\n'));
    process.exit(1);
}

console.log('RTL check passed: no physical-direction classes.');

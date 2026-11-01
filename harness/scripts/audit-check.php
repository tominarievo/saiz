<?php

$auditPath = $argv[1] ?? null;
$allowlistPath = $argv[2] ?? null;

if (!$auditPath || !$allowlistPath || !is_file($auditPath) || !is_file($allowlistPath)) {
    fwrite(STDERR, "usage: audit-check.php <composer-audit.json> <allowlist.json>\n");
    exit(2);
}

$audit = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($auditPath)), true);
$allowlist = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($allowlistPath)), true);

if (!is_array($audit) || !is_array($allowlist)) {
    fwrite(STDERR, "failed to parse audit or allowlist JSON\n");
    exit(2);
}

$allowKeys = [];
foreach ($allowlist as $entry) {
    if (isset($entry['package'], $entry['advisoryId'])) {
        $allowKeys[$entry['package'] . '|' . $entry['advisoryId']] = true;
    }
}

$failures = [];

foreach (($audit['advisories'] ?? []) as $package => $items) {
    if (!is_array($items)) {
        continue;
    }

    foreach ($items as $item) {
        $severity = strtolower($item['severity'] ?? '');
        if (!in_array($severity, ['critical', 'high'], true)) {
            continue;
        }

        $advisoryId = $item['advisoryId'] ?? '';
        $key = $package . '|' . $advisoryId;

        if (!isset($allowKeys[$key])) {
            $failures[] = [
                'package' => $package,
                'advisoryId' => $advisoryId,
                'severity' => $severity,
                'title' => $item['title'] ?? '',
            ];
        }
    }
}

if ($failures) {
    fwrite(STDERR, "Unallowlisted critical/high advisories:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, "  - {$failure['package']} {$failure['advisoryId']} ({$failure['severity']}) {$failure['title']}\n");
    }
    exit(1);
}

echo "critical/high advisories: allowlisted or none\n";
exit(0);

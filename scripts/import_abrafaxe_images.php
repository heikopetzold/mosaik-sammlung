<?php

declare(strict_types=1);

// CLI script: backfill mosaics.image_path from local image files.
// Usage examples:
//   lando php scripts/import_abrafaxe_images.php --dry-run
//   lando php scripts/import_abrafaxe_images.php --limit=50
//   lando php scripts/import_abrafaxe_images.php --overwrite

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run via CLI.\n");
    exit(1);
}

$opts = getopt('', ['dry-run', 'overwrite', 'allow-jpg', 'limit:', 'help']);
if (isset($opts['help'])) {
    $msg = <<<TXT
Backfill Abrafaxe cover images.

Reads mosaics where category=Abrafaxe and image_path is NULL/empty.
Tries to find images in images/abrafaxe/<issue_number>.(png|jpg|jpeg).
Copies the file to public/uploads/ and sets image_path to uploads/<file>.

Options:
  --dry-run     Do not copy or update DB, only print what would happen.
  --overwrite   Overwrite existing target file in public/uploads/.
  --allow-jpg   Also accept .jpg/.jpeg in images/abrafaxe (default: only .png).
  --limit=N     Process at most N rows.
  --help        Show this help.

Examples:
  lando php scripts/import_abrafaxe_images.php --dry-run
  lando php scripts/import_abrafaxe_images.php --limit=100
TXT;
    fwrite(STDOUT, $msg . "\n");
    exit(0);
}

$dryRun = array_key_exists('dry-run', $opts);
$overwrite = array_key_exists('overwrite', $opts);
$allowJpg = array_key_exists('allow-jpg', $opts);
$limit = isset($opts['limit']) ? max(1, (int) $opts['limit']) : null;

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Could not determine project root.\n");
    exit(1);
}

$sourceDir = $root . '/images/abrafaxe';
$targetDir = $root . '/public/uploads';

if (!is_dir($sourceDir)) {
    fwrite(STDERR, "Source directory not found: {$sourceDir}\n");
    exit(1);
}

if (!is_dir($targetDir)) {
    if ($dryRun) {
        fwrite(STDOUT, "[dry-run] Would create target directory: {$targetDir}\n");
    } else {
        if (!mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            fwrite(STDERR, "Failed to create target directory: {$targetDir}\n");
            exit(1);
        }
    }
}

require_once $root . '/vendor/autoload.php';

use App\Facades\DB;

$sql = "SELECT id, issue_number
        FROM mosaics
        WHERE category = :category
          AND (image_path IS NULL OR image_path = '')
          AND issue_number IS NOT NULL
        ORDER BY issue_number ASC";

if ($limit !== null) {
    $sql .= " LIMIT " . (int) $limit;
}

$rows = DB::query($sql, ['category' => 'Abrafaxe'])->fetchAll();

$counts = [
    'total' => count($rows),
    'missing_file' => 0,
    'copied' => 0,
    'skipped_exists' => 0,
    'updated' => 0,
    'update_failed' => 0,
];

// Requested pattern: <issue_number>.png
$extensions = ['png'];
if ($allowJpg) {
    $extensions[] = 'jpg';
    $extensions[] = 'jpeg';
}

foreach ($rows as $row) {
    $id = (int) ($row['id'] ?? 0);
    $issue = (int) ($row['issue_number'] ?? 0);
    if ($id <= 0 || $issue <= 0) {
        continue;
    }

    $sourcePath = null;
    $sourceExt = null;
    foreach ($extensions as $ext) {
        $candidate = $sourceDir . '/' . $issue . '.' . $ext;
        if (is_file($candidate)) {
            $sourcePath = $candidate;
            $sourceExt = $ext;
            break;
        }
    }

    if ($sourcePath === null || $sourceExt === null) {
        $counts['missing_file']++;
        $extNote = $allowJpg ? '.png/.jpg/.jpeg' : '.png';
        fwrite(STDOUT, "[missing] id={$id} issue={$issue} (no file found in images/abrafaxe for {$extNote})\n");
        continue;
    }

    $targetFileName = $issue . '.' . $sourceExt;
    $targetPath = $targetDir . '/' . $targetFileName;
    $dbPath = 'uploads/' . $targetFileName;

    if (is_file($targetPath) && !$overwrite) {
        $counts['skipped_exists']++;
        fwrite(STDOUT, "[skip] id={$id} issue={$issue} target exists ({$dbPath})\n");
    } else {
        if ($dryRun) {
            fwrite(STDOUT, "[dry-run] Would copy {$sourcePath} -> {$targetPath}\n");
        } else {
            if (!copy($sourcePath, $targetPath)) {
                fwrite(STDERR, "[error] Failed to copy {$sourcePath} -> {$targetPath}\n");
                continue;
            }
        }
        $counts['copied']++;
    }

    if ($dryRun) {
        fwrite(STDOUT, "[dry-run] Would update mosaics.id={$id} image_path='{$dbPath}'\n");
        $counts['updated']++;
        continue;
    }

    // Only set image_path if still empty to avoid clobbering concurrent changes.
    $update = DB::query(
        "UPDATE mosaics
         SET image_path = :image_path
         WHERE id = :id
           AND (image_path IS NULL OR image_path = '')",
        [
            'image_path' => $dbPath,
            'id' => $id,
        ]
    );

    $affected = $update->rowCount();
    if ($affected === 1) {
        $counts['updated']++;
        fwrite(STDOUT, "[ok] id={$id} issue={$issue} image_path={$dbPath}\n");
    } else {
        $counts['update_failed']++;
        fwrite(STDERR, "[warn] id={$id} issue={$issue} DB update affected {$affected} rows (skipped)\n");
    }
}

fwrite(STDOUT, "\nSummary:\n");
foreach ($counts as $k => $v) {
    fwrite(STDOUT, str_pad($k, 14, ' ') . ": {$v}\n");
}

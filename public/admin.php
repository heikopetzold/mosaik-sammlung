<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
use App\Classes\MosaicRepository;
use App\Enums\Month;
use App\Enums\PublicationType;
use App\Enums\Series;
use App\Enums\Availability;
use App\Enums\Condition;

$repository = new MosaicRepository();
$message = '';
$error = '';

// Handle Form Submission (Create Mosaic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = $_POST['title'] ?? '';
    $type = PublicationType::tryFrom($_POST['type'] ?? '')?->value ?? PublicationType::Heft->value;
    $series = Series::tryFrom($_POST['series'] ?? '')?->value ?? Series::Abrafaxe->value;
    $availability = Availability::tryFrom($_POST['availability'] ?? '')?->value ?? Availability::Vorhanden->value;
    $condition = Condition::tryFrom($_POST['item_condition'] ?? '')?->value ?? Condition::SehrGut->value;
    $issueNumber = $_POST['issue_number'] ?? '';
    $year = $_POST['release_year'] ?? '';
    $month = $_POST['release_month'] ?? '';
    $description = $_POST['description'] ?? '';

    // Datei-Upload Handling
    $targetDir = __DIR__ . '/uploads/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . '_' . basename($_FILES["image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $dbImagePath = 'uploads/' . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
        $success = $repository->save([
            'title' => $title,
            'type' => $type,
            'series' => $series,
            'issue_number' => $issueNumber,
            'availability' => $availability,
            'item_condition' => $condition,
            'release_year' => $year,
            'release_month' => $month,
            'description' => $description,
            'image_path' => $dbImagePath
        ]);

        if ($success) {
            $message = "Mosaik erfolgreich hinzugefügt!";
        } else {
            $error = "Fehler beim Speichern in der Datenbank.";
            @unlink($targetFilePath);
        }
    } else {
        $error = "Fehler beim Bildupload.";
    }
}

// Handle Form Submission (Update Mosaic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $existing = $id ? $repository->find($id) : null;

    if (!$existing) {
        $error = 'Mosaik nicht gefunden.';
    } else {
        $title = $_POST['title'] ?? '';
        $type = PublicationType::tryFrom($_POST['type'] ?? '')?->value ?? PublicationType::Heft->value;
        $series = Series::tryFrom($_POST['series'] ?? '')?->value ?? Series::Abrafaxe->value;
        $availability = Availability::tryFrom($_POST['availability'] ?? '')?->value ?? Availability::Vorhanden->value;
        $condition = Condition::tryFrom($_POST['item_condition'] ?? '')?->value ?? Condition::SehrGut->value;
        $issueNumber = $_POST['issue_number'] ?? '';
        $year = $_POST['release_year'] ?? '';
        $month = $_POST['release_month'] ?? '';
        $description = $_POST['description'] ?? '';

        // Bild nur ersetzen, wenn ein neues hochgeladen wurde – sonst bestehendes behalten
        $dbImagePath = $existing['image_path'];
        $oldImagePath = null;

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = __DIR__ . '/uploads/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . '_' . basename($_FILES["image"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $oldImagePath = $existing['image_path'];
                $dbImagePath = 'uploads/' . $fileName;
            } else {
                $error = "Fehler beim Bildupload.";
            }
        }

        if (empty($error)) {
            $success = $repository->update($id, [
                'title' => $title,
                'type' => $type,
                'series' => $series,
                'issue_number' => $issueNumber,
                'availability' => $availability,
                'item_condition' => $condition,
                'release_year' => $year,
                'release_month' => $month,
                'description' => $description,
                'image_path' => $dbImagePath
            ]);

            if ($success) {
                // Altes Bild erst nach erfolgreichem Update entfernen
                if ($oldImagePath) {
                    $oldFullPath = __DIR__ . '/' . $oldImagePath;
                    if (file_exists($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }
                $message = "Mosaik erfolgreich aktualisiert!";
            } else {
                $error = "Fehler beim Aktualisieren in der Datenbank.";
            }
        }
    }
}

// Handle Delete Mosaic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
    if ($id) {
        $mosaic = $repository->find($id);
        if ($mosaic) {
            // Delete image file
            $imageFullPath = __DIR__ . '/' . $mosaic['image_path'];
            if (file_exists($imageFullPath)) {
                @unlink($imageFullPath);
            }

            if ($repository->delete($id)) {
                $message = 'Mosaik erfolgreich gelöscht.';
            } else {
                $error = 'Fehler beim Löschen des Mosaiks.';
            }
        } else {
            $error = 'Mosaik nicht gefunden.';
        }
    }
}

// Datensatz für Bearbeiten-Modus laden (?edit=ID)
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editMosaic = $editId ? $repository->find($editId) : null;
$isEdit = $editMosaic !== null;

// Formularwerte (im Bearbeiten-Modus vorbelegt, sonst Standardwerte)
$formTitle = $editMosaic['title'] ?? '';
$formType = $editMosaic['type'] ?? PublicationType::Heft->value;
$formSeries = $editMosaic['series'] ?? Series::Abrafaxe->value;
$formAvailability = $editMosaic['availability'] ?? Availability::Vorhanden->value;
$formCondition = $editMosaic['item_condition'] ?? Condition::SehrGut->value;
$formIssue = $editMosaic['issue_number'] ?? '';
$formYear = $editMosaic['release_year'] ?? date('Y');
$formMonth = $editMosaic['release_month'] ?? null;
$formDescription = $editMosaic['description'] ?? '';

$mosaics = $repository->getAllSorted('DESC');
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Bereich - Mosaik-Sammlung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --accent-color: #3b82f6;
            --accent-gradient: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            --font-main: 'Outfit', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: var(--font-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Background Glows */
        .glow {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            top: -100px;
            right: -100px;
            z-index: -1;
            pointer-events: none;
        }

        .glow-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, rgba(0, 0, 0, 0) 70%);
            bottom: -100px;
            left: -100px;
            z-index: -1;
            pointer-events: none;
        }

        header {
            padding: 2rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--card-border);
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--card-border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--text-secondary);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        main {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            padding: 2rem 1rem;
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }

        @media (min-width: 900px) {
            main {
                grid-template-columns: 380px 1fr;
            }
        }

        /* Notifications */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            grid-column: 1 / -1;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        /* Admin Form Panel */
        .form-panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(12px);
            padding: 2rem;
            border-radius: 16px;
            height: fit-content;
        }

        .form-panel h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            background: #161b26;
            border: 1px solid var(--card-border);
            color: var(--text-primary);
            padding: 0.75rem;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--accent-color);
        }

        .form-group input[type="file"] {
            display: none;
        }

        .file-upload-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            background: #161b26;
            border: 1px dashed var(--card-border);
            color: var(--text-secondary);
            padding: 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-btn:hover {
            border-color: var(--accent-color);
            color: var(--text-primary);
        }

        /* List Panel */
        .list-panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(12px);
            padding: 2rem;
            border-radius: 16px;
        }

        .list-panel h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            color: var(--text-secondary);
            font-weight: 500;
            padding: 1rem;
            border-bottom: 1px solid var(--card-border);
            font-size: 0.875rem;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--card-border);
            vertical-align: middle;
        }

        .table-image {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
            background: #161b26;
        }

        .table-title {
            font-weight: 500;
        }

        .tag {
            display: inline-block;
            margin-top: 0.35rem;
            padding: 0.1rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .tag-missing {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .table-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .empty-table {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
    </style>
</head>

<body>
    <div class="glow"></div>
    <div class="glow-2"></div>

    <header>
        <h1>Admin-Bereich</h1>
        <div class="header-actions">
            <a href="index.php" class="btn btn-secondary">Zur Hauptseite</a>
            <a href="logout.php" class="btn btn-danger">Abmelden</a>
        </div>
    </header>

    <main>
        <!-- Alerts -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Add / Edit Mosaic Form -->
        <section class="form-panel">
            <h2><?= $isEdit ? 'Mosaik bearbeiten' : 'Mosaik hinzufügen' ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $editMosaic['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Titel *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($formTitle) ?>" required>
                </div>

                <div class="form-group">
                    <label for="type">Typ *</label>
                    <select id="type" name="type" required>
                        <?php foreach (PublicationType::cases() as $typeCase): ?>
                            <option value="<?= $typeCase->value ?>" <?= $typeCase->value === $formType ? 'selected' : '' ?>>
                                <?= $typeCase->label() ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="series">Hauptserie *</label>
                    <select id="series" name="series" required>
                        <?php foreach (Series::cases() as $seriesCase): ?>
                            <option value="<?= $seriesCase->value ?>" <?= $seriesCase->value === $formSeries ? 'selected' : '' ?>>
                                <?= $seriesCase->label() ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="issue_number">Nummer</label>
                    <input type="number" id="issue_number" name="issue_number" min="1" step="1"
                        value="<?= htmlspecialchars((string) $formIssue) ?>">
                </div>

                <div class="form-group">
                    <label for="availability">Verfügbarkeit *</label>
                    <select id="availability" name="availability" required>
                        <?php foreach (Availability::cases() as $availCase): ?>
                            <option value="<?= $availCase->value ?>" <?= $availCase->value === $formAvailability ? 'selected' : '' ?>>
                                <?= $availCase->label() ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="item_condition">Zustand *</label>
                    <select id="item_condition" name="item_condition" required>
                        <?php foreach (Condition::cases() as $condCase): ?>
                            <option value="<?= $condCase->value ?>" <?= $condCase->value === $formCondition ? 'selected' : '' ?>>
                                <?= $condCase->label() ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="release_year">Erscheinungsjahr (z.B. 1975) *</label>
                    <input type="number" id="release_year" name="release_year" min="1900" max="2100"
                        value="<?= htmlspecialchars((string) $formYear) ?>" required>
                </div>

                <div class="form-group">
                    <label for="release_month">Erscheinungsmonat *</label>
                    <select id="release_month" name="release_month" required>
                        <?php foreach (Month::cases() as $monthCase): ?>
                            <option value="<?= $monthCase->value ?>" <?= (int) $formMonth === $monthCase->value ? 'selected' : '' ?>>
                                <?= $monthCase->label() ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Bild <?= $isEdit ? '(leer lassen, um das aktuelle Bild zu behalten)' : 'auswählen *' ?></label>
                    <?php if ($isEdit && !empty($editMosaic['image_path'])): ?>
                        <img src="<?= htmlspecialchars($editMosaic['image_path']) ?>" alt="Aktuelles Bild"
                            style="width: 100%; max-height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 0.75rem;">
                    <?php endif; ?>
                    <label for="image" class="file-upload-btn" id="upload-label">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                        </svg>
                        <span id="upload-text"><?= $isEdit ? 'Neues Bild auswählen' : 'Bild auswählen' ?></span>
                    </label>
                    <input type="file" id="image" name="image" accept="image/*" <?= $isEdit ? '' : 'required' ?>>
                </div>

                <div class="form-group">
                    <label for="description">Beschreibung</label>
                    <textarea id="description" name="description" rows="5"><?= htmlspecialchars($formDescription) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <?= $isEdit ? 'Aktualisieren' : 'Speichern' ?>
                </button>
                <?php if ($isEdit): ?>
                    <a href="admin.php" class="btn btn-secondary" style="width: 100%; margin-top: 0.75rem;">Abbrechen</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Mosaics List -->
        <section class="list-panel">
            <h2>Bestehende Mosaike</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Bild</th>
                            <th>Titel</th>
                            <th>Typ / Nr.</th>
                            <th>Serie / Zustand</th>
                            <th>Datum</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mosaics)): ?>
                            <tr>
                                <td colspan="6" class="empty-table">Keine Mosaike vorhanden.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mosaics as $mosaic): ?>
                                <?php
                                $monthEnum = Month::tryFrom($mosaic['release_month']);
                                $monthLabel = $monthEnum ? $monthEnum->label() : $mosaic['release_month'];
                                $typeEnum = PublicationType::tryFrom($mosaic['type'] ?? '');
                                $typeLabel = $typeEnum ? $typeEnum->label() : ($mosaic['type'] ?? '');
                                $seriesEnum = Series::tryFrom($mosaic['series'] ?? '');
                                $seriesLabel = $seriesEnum ? $seriesEnum->label() : ($mosaic['series'] ?? '');
                                $condEnum = Condition::tryFrom($mosaic['item_condition'] ?? '');
                                $condLabel = $condEnum ? $condEnum->label() : ($mosaic['item_condition'] ?? '');
                                $isMissing = ($mosaic['availability'] ?? '') === Availability::Fehlt->value;
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($mosaic['image_path'])): ?>
                                            <img src="<?= htmlspecialchars($mosaic['image_path']) ?>" alt="" class="table-image">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-title"><?= htmlspecialchars($mosaic['title']) ?></div>
                                        <?php if ($isMissing): ?>
                                            <span class="tag tag-missing">Fehlt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-date">
                                            <?= htmlspecialchars($typeLabel) ?><?php if (!empty($mosaic['issue_number'])): ?>
                                                Nr.&nbsp;<?= htmlspecialchars($mosaic['issue_number']) ?><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-date"><?= htmlspecialchars($seriesLabel) ?><br>
                                            <?= htmlspecialchars($condLabel) ?></div>
                                    </td>
                                    <td>
                                        <div class="table-date"><?= htmlspecialchars($monthLabel) ?>
                                            <?= htmlspecialchars($mosaic['release_year']) ?></div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?edit=<?= $mosaic['id'] ?>" class="btn btn-secondary"
                                                style="padding: 0.5rem 1rem; font-size: 0.875rem;">Bearbeiten</a>
                                            <form method="POST"
                                                onsubmit="return confirm('Möchten Sie dieses Mosaik wirklich löschen?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $mosaic['id'] ?>">
                                                <button type="submit" class="btn btn-danger"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;">Löschen</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        // Update file upload label when file is selected
        const fileInput = document.getElementById('image');
        const uploadText = document.getElementById('upload-text');
        const uploadLabel = document.getElementById('upload-label');

        fileInput.addEventListener('change', function (e) {
            if (e.target.files && e.target.files.length > 0) {
                uploadText.textContent = e.target.files[0].name;
                uploadLabel.style.borderColor = 'var(--accent-color)';
                uploadLabel.style.color = 'var(--text-primary)';
            } else {
                uploadText.textContent = 'Bild auswählen';
                uploadLabel.style.borderColor = 'var(--card-border)';
                uploadLabel.style.color = 'var(--text-secondary)';
            }
        });
    </script>
</body>

</html>
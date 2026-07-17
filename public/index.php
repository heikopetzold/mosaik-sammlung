<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Classes\MosaicRepository;
use App\Enums\Month;
use App\Enums\PublicationType;
use App\Enums\Series;
use App\Enums\Availability;
use App\Enums\Condition;

$repository = new MosaicRepository();
$sort = $_GET['sort'] ?? 'ASC';

// Filter aus der URL (leere/ungültige Werte werden ignoriert)
$filterYear = isset($_GET['year']) && $_GET['year'] !== '' ? (int) $_GET['year'] : null;
$filterSeries = Series::tryFrom($_GET['series'] ?? '')?->value;
$filterCondition = Condition::tryFrom($_GET['condition'] ?? '')?->value;

$mosaics = $repository->getFiltered([
    'year' => $filterYear,
    'series' => $filterSeries,
    'condition' => $filterCondition,
], $sort);

$years = $repository->getDistinctYears();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mosaik-Sammlung</title>
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

        .admin-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            backdrop-filter: blur(10px);
        }

        .admin-link:hover {
            color: var(--text-primary);
            border-color: var(--accent-color);
        }

        main {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            padding: 2rem 1rem;
        }

        /* Controls Section */
        .controls {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(12px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            font-size: 0.95rem;
        }

        .controls a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }

        .controls a:hover,
        .controls a.active {
            color: var(--accent-color);
        }

        .controls .separator {
            color: var(--card-border);
        }

        /* Filter Bar */
        .filter-bar {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(12px);
            padding: 1.25rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .filter-group label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .filter-group select {
            background: #161b26;
            border: 1px solid var(--card-border);
            color: var(--text-primary);
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 0.95rem;
            outline: none;
            min-width: 150px;
        }

        .filter-group select:focus {
            border-color: var(--accent-color);
        }

        .filter-btn {
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: var(--accent-gradient);
            color: white;
            transition: opacity 0.3s ease;
        }

        .filter-btn:hover {
            opacity: 0.9;
        }

        .filter-reset {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.6rem 0.5rem;
            align-self: center;
        }

        .filter-reset:hover {
            color: var(--text-primary);
        }

        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(12px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-6px);
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .card-image-wrapper {
            position: relative;
            padding-top: 75%;
            /* 4:3 Aspect Ratio */
            overflow: hidden;
            background: #161b26;
        }

        .card-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .card:hover .card-image {
            transform: scale(1.05);
        }

        .card-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(11, 15, 25, 0.8);
            backdrop-filter: blur(8px);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid var(--card-border);
            color: var(--accent-color);
        }

        .card-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-type {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent-color);
            margin-bottom: 0.4rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .card-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            flex: 1;
        }

        .card-condition {
            margin-top: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .card-badge-missing {
            right: auto;
            left: 1rem;
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.4);
        }

        .card-missing {
            opacity: 0.75;
        }

        .card-missing:hover {
            opacity: 1;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            backdrop-filter: blur(12px);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-secondary);
        }

        footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            border-top: 1px solid var(--card-border);
            margin-top: auto;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(11, 15, 25, 0.85);
            backdrop-filter: blur(12px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 1.5rem;
        }

        .modal.active {
            opacity: 1;
            pointer-events: all;
        }

        .modal-content {
            background: #111827;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: scale(0.95);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
        }

        .modal.active .modal-content {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: #ef4444;
            border-color: #ef4444;
            color: white;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.8fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .modal-grid {
                grid-template-columns: 1fr;
            }
        }

        .modal-image-col {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .modal-img-wrapper {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            background: #161b26;
        }

        .modal-img-wrapper img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
        }

        .modal-description {
            font-size: 1.05rem;
            color: var(--text-primary);
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .modal-section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 0.25rem;
        }
    </style>
</head>

<body>
    <div class="glow"></div>
    <div class="glow-2"></div>

    <header>
        <h1>Meine Mosaik-Sammlung</h1>
        <a href="login.php" class="admin-link">Admin-Bereich</a>
    </header>

    <main>
        <?php
        // Aktive Filter für das Erhalten beim Umsortieren
        $activeFilters = array_filter([
            'year' => $filterYear,
            'series' => $filterSeries,
            'condition' => $filterCondition,
        ], fn($v) => $v !== null && $v !== '');
        $sortAscUrl = '?' . http_build_query(array_merge($activeFilters, ['sort' => 'ASC']));
        $sortDescUrl = '?' . http_build_query(array_merge($activeFilters, ['sort' => 'DESC']));
        $hasFilters = !empty($activeFilters);
        ?>
        <div class="controls">
            <a href="<?= $sortAscUrl ?>" class="<?= $sort === 'ASC' ? 'active' : '' ?>">Älteste zuerst</a>
            <span class="separator">|</span>
            <a href="<?= $sortDescUrl ?>" class="<?= $sort === 'DESC' ? 'active' : '' ?>">Neueste zuerst</a>
        </div>

        <form method="GET" class="filter-bar">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

            <div class="filter-group">
                <label for="filter-series">Hauptserie</label>
                <select id="filter-series" name="series">
                    <option value="">Alle</option>
                    <?php foreach (Series::cases() as $seriesCase): ?>
                        <option value="<?= $seriesCase->value ?>" <?= $filterSeries === $seriesCase->value ? 'selected' : '' ?>>
                            <?= $seriesCase->label() ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter-condition">Zustand</label>
                <select id="filter-condition" name="condition">
                    <option value="">Alle</option>
                    <?php foreach (Condition::cases() as $condCase): ?>
                        <option value="<?= $condCase->value ?>" <?= $filterCondition === $condCase->value ? 'selected' : '' ?>>
                            <?= $condCase->label() ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter-year">Jahr</label>
                <select id="filter-year" name="year">
                    <option value="">Alle</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>" <?= $filterYear === $year ? 'selected' : '' ?>>
                            <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="filter-btn">Filtern</button>
            <?php if ($hasFilters): ?>
                <a href="?sort=<?= htmlspecialchars($sort) ?>" class="filter-reset">Zurücksetzen</a>
            <?php endif; ?>
        </form>

        <div class="grid">
            <?php if (empty($mosaics)): ?>
                <div class="empty-state">
                    <?php if ($hasFilters): ?>
                        <h3>Keine Treffer</h3>
                        <p>Für die gewählten Filter wurden keine Mosaike gefunden.</p>
                    <?php else: ?>
                        <h3>Keine Mosaike vorhanden</h3>
                        <p>Es wurden noch keine Mosaike in die Sammlung eingetragen.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($mosaics as $mosaik):
                    $monthEnum = Month::tryFrom($mosaik['release_month']);
                    $monthLabel = $monthEnum ? $monthEnum->label() : $mosaik['release_month'];
                    $typeEnum = PublicationType::tryFrom($mosaik['type'] ?? '');
                    $typeLabel = $typeEnum ? $typeEnum->label() : ($mosaik['type'] ?? '');
                    $seriesEnum = Series::tryFrom($mosaik['series'] ?? '');
                    $seriesLabel = $seriesEnum ? $seriesEnum->label() : ($mosaik['series'] ?? '');
                    $condEnum = Condition::tryFrom($mosaik['item_condition'] ?? '');
                    $condLabel = $condEnum ? $condEnum->label() : ($mosaik['item_condition'] ?? '');
                    $isMissing = ($mosaik['availability'] ?? '') === Availability::Fehlt->value;
                    $subtitleParts = array_filter([
                        $seriesLabel,
                        trim($typeLabel . (!empty($mosaik['issue_number']) ? ' Nr. ' . $mosaik['issue_number'] : '')),
                    ]);
                    $subtitle = implode(' · ', $subtitleParts);
                    ?>
                    <article class="card<?= $isMissing ? ' card-missing' : '' ?>"
                             style="cursor: pointer;"
                             data-title="<?= htmlspecialchars($mosaik['title']) ?>"
                             data-description="<?= htmlspecialchars($mosaik['description'] ?? '') ?>"
                             data-image="<?= htmlspecialchars($mosaik['image_path'] ?? '') ?>"
                             data-condition-image="<?= htmlspecialchars($mosaik['image_path_current_condition'] ?? '') ?>">
                        <div class="card-image-wrapper">
                            <?php if (!empty($mosaik['image_path'])): ?>
                                <img src="<?= htmlspecialchars($mosaik['image_path']) ?>" alt="Mosaik Cover" class="card-image"
                                    loading="lazy">
                            <?php endif; ?>
                            <span class="card-badge"><?= htmlspecialchars($monthLabel) ?>
                                <?= htmlspecialchars($mosaik['release_year']) ?></span>
                            <?php if ($isMissing): ?>
                                <span class="card-badge card-badge-missing">Fehlt</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <?php if ($subtitle !== ''): ?>
                                <span class="card-type"><?= htmlspecialchars($subtitle) ?></span>
                            <?php endif; ?>
                            <h2 class="card-title"><?= htmlspecialchars($mosaik['title']) ?></h2>
                            <p class="card-description"><?= nl2br(htmlspecialchars($mosaik['description'] ?? '')) ?></p>
                            <span class="card-condition">Zustand: <?= htmlspecialchars($condLabel) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Detail Modal -->
    <div class="modal" id="detail-modal">
        <div class="modal-content">
            <button class="modal-close" id="modal-close">&times;</button>
            <h2 id="m-title" style="font-size: 1.75rem; margin-bottom: 1rem; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Titel</h2>
            <div class="modal-grid">
                <!-- Images Column -->
                <div class="modal-image-col">
                    <div class="modal-section-title">Cover (Gesamtbild)</div>
                    <div class="modal-img-wrapper" id="m-cover-wrapper">
                        <img src="" alt="Cover" id="m-cover-img">
                    </div>
                    
                    <div id="m-cond-section" style="display: none;">
                        <div class="modal-section-title" style="margin-top: 1rem;">Aktueller Zustand</div>
                        <div class="modal-img-wrapper">
                            <img src="" alt="Zustand" id="m-cond-img">
                        </div>
                    </div>
                </div>

                <!-- Description Column -->
                <div>
                    <div class="modal-section-title">Beschreibung</div>
                    <div class="modal-description" id="m-description">Beschreibung...</div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Mosaik-Sammlung. Alle Rechte vorbehalten.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('detail-modal');
            const modalClose = document.getElementById('modal-close');
            const cards = document.querySelectorAll('.card');

            cards.forEach(card => {
                card.addEventListener('click', () => {
                    const title = card.getAttribute('data-title');
                    const description = card.getAttribute('data-description') || 'Keine Beschreibung vorhanden.';
                    const image = card.getAttribute('data-image');
                    const condImage = card.getAttribute('data-condition-image');

                    document.getElementById('m-title').textContent = title;
                    document.getElementById('m-description').textContent = description;

                    const coverWrapper = document.getElementById('m-cover-wrapper');
                    const coverImg = document.getElementById('m-cover-img');
                    if (image) {
                        coverImg.src = image;
                        coverWrapper.style.display = 'block';
                    } else {
                        coverImg.src = '';
                        coverWrapper.style.display = 'none';
                    }

                    const condSection = document.getElementById('m-cond-section');
                    const condImg = document.getElementById('m-cond-img');
                    if (condImage) {
                        condImg.src = condImage;
                        condSection.style.display = 'block';
                    } else {
                        condImg.src = '';
                        condSection.style.display = 'none';
                    }

                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });

            const closeModal = () => {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            };

            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
            });
        });
    </script>
</body>

</html>
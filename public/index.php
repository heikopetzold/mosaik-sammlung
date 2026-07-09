<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Classes\MosaicRepository;
use App\Enums\Month;
use App\Enums\PublicationType;

$repository = new MosaicRepository();
$sort = $_GET['sort'] ?? 'ASC';
$mosaics = $repository->getAllSorted($sort);
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
        <div class="controls">
            <a href="?sort=ASC" class="<?= $sort === 'ASC' ? 'active' : '' ?>">Älteste zuerst</a>
            <span class="separator">|</span>
            <a href="?sort=DESC" class="<?= $sort === 'DESC' ? 'active' : '' ?>">Neueste zuerst</a>
        </div>

        <div class="grid">
            <?php if (empty($mosaics)): ?>
                <div class="empty-state">
                    <h3>Keine Mosaike vorhanden</h3>
                    <p>Es wurden noch keine Mosaike in die Sammlung eingetragen.</p>
                </div>
            <?php else: ?>
                <?php foreach ($mosaics as $mosaik):
                    $monthEnum = Month::tryFrom($mosaik['release_month']);
                    $monthLabel = $monthEnum ? $monthEnum->label() : $mosaik['release_month'];
                    $typeEnum = PublicationType::tryFrom($mosaik['type'] ?? '');
                    $typeLabel = $typeEnum ? $typeEnum->label() : ($mosaik['type'] ?? '');
                    $subtitle = trim($typeLabel . (!empty($mosaik['issue_number']) ? ' Nr. ' . $mosaik['issue_number'] : ''));
                    ?>
                    <article class="card">
                        <div class="card-image-wrapper">
                            <?php if (!empty($mosaik['image_path'])): ?>
                                <img src="<?= htmlspecialchars($mosaik['image_path']) ?>" alt="Mosaik Cover" class="card-image"
                                    loading="lazy">
                            <?php endif; ?>
                            <span class="card-badge"><?= htmlspecialchars($monthLabel) ?>
                                <?= htmlspecialchars($mosaik['release_year']) ?></span>
                        </div>
                        <div class="card-content">
                            <?php if ($subtitle !== ''): ?>
                                <span class="card-type"><?= htmlspecialchars($subtitle) ?></span>
                            <?php endif; ?>
                            <h2 class="card-title"><?= htmlspecialchars($mosaik['title']) ?></h2>
                            <p class="card-description"><?= nl2br(htmlspecialchars($mosaik['description'] ?? '')) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Mosaik-Sammlung. Alle Rechte vorbehalten.</p>
    </footer>
</body>

</html>
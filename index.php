<?php
session_start();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$dorks = [
    'xss' => [
        "site:{domain} inurl:\"q=\"",
        "site:{domain} inurl:\"search.php?q=\"",
        "site:{domain} inurl:\"?redirect=\"",
        "site:{domain} inurl:\"script=\""
    ],
    'sqli' => [
        "site:{domain} inurl:\"id=\"",
        "site:{domain} inurl:\"product.php?id=\"",
        "site:{domain} inurl:\"view.php?id=\"",
        "site:{domain} inurl:\"category.php?id=\""
    ],
    'lfi' => [
        "site:{domain} inurl:\"file=\"",
        "site:{domain} inurl:\"page=../../../../etc/passwd\"",
        "site:{domain} inurl:\"include=\"",
        "site:{domain} inurl:\"config=\""
    ],
    'open_redirect' => [
        "site:{domain} inurl:\"redirect=\"",
        "site:{domain} inurl:\"url=\"",
        "site:{domain} inurl:\"next=\"",
        "site:{domain} inurl:\"out=\""
    ],
    'admin_pages' => [
        "site:{domain} inurl:\"admin\"",
        "site:{domain} inurl:\"login\"",
        "site:{domain} inurl:\"dashboard\"",
        "site:{domain} inurl:\"cpanel\""
    ],
    'sensitive_files' => [
        "site:{domain} ext:log",
        "site:{domain} ext:sql",
        "site:{domain} ext:txt",
        "site:{domain} ext:env"
    ],
    'subdomains' => [
        "site:*.{domain} -www.{domain}",
        "site:sub.{domain}",
        "site:{domain} -inurl:www"
    ],
    'publicly_indexed_docs' => [
        "site:{domain} filetype:pdf",
        "site:{domain} filetype:doc",
        "site:{domain} filetype:xls",
        "site:{domain} filetype:ppt"
    ],
    'rce' => [
        "site:{domain} inurl:\"cmd=\"",
        "site:{domain} inurl:\"exec=\"",
        "site:{domain} inurl:\"command=\"",
        "site:{domain} inurl:\"run=\""
    ],
    'ssrf' => [
        "site:{domain} inurl:\"url=\"",
        "site:{domain} inurl:\"proxy=\"",
        "site:{domain} inurl:\"request=\"",
        "site:{domain} inurl:\"fetch=\""
    ],
    'xxe' => [
        "site:{domain} inurl:\"xml=\"",
        "site:{domain} inurl:\"feed=\"",
        "site:{domain} inurl:\"rss=\"",
        "site:{domain} inurl:\"atom=\""
    ],
    'directory_traversal' => [
        "site:{domain} inurl:\"path=\"",
        "site:{domain} inurl:\"dir=\"",
        "site:{domain} inurl:\"directory=\"",
        "site:{domain} inurl:\"folder=\""
    ],
    'api_endpoints' => [
        "site:{domain} inurl:\"api\"",
        "site:{domain} inurl:\"v1\"",
        "site:{domain} inurl:\"v2\"",
        "site:{domain} inurl:\"graphql\""
    ]
];

if (isset($_SESSION['favorites']) && is_array($_SESSION['favorites'])) {
    foreach ($_SESSION['favorites'] as &$fav) {
        if (isset($fav['domain']) && !isset($fav['domains'])) {
            $fav['domains'] = $fav['domain'];
            unset($fav['domain']);
        }
    }
    unset($fav);

$domain = '';
$dork_type = '';
$custom_dork = '';
$generated_dorks = [];
$validation_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $domain_input = filter_var($_POST['domain'], FILTER_SANITIZE_STRING);
    $domains = array_filter(array_map('trim', explode(',', $domain_input)));
    $dork_type = filter_var($_POST['dork_type'], FILTER_SANITIZE_STRING);
    $custom_dork = filter_var($_POST['custom_dork'] ?? '', FILTER_SANITIZE_STRING);

    if (!empty($custom_dork)) {
        if (!preg_match('/\b(site:|inurl:|ext:|filetype:|-)\b/', $custom_dork)) {
            $validation_error = "Custom dork should include basic Google dork syntax (e.g., site:, inurl:, etc.).";
        } else {
            $dorks['custom'] = [$custom_dork];
            $dork_type = 'custom';
        }
    }

    if (empty($validation_error) && !empty($dork_type) && isset($dorks[$dork_type])) {
        $generated_dorks = [];
        foreach ($domains as $domain) {
            $domain = filter_var($domain, FILTER_SANITIZE_URL);
            if (!empty($domain)) {
                $dorks_for_domain = array_map(function($dork) use ($domain) {
                    return str_replace('{domain}', $domain, $dork);
                }, $dorks[$dork_type]);
                $generated_dorks = array_merge($generated_dorks, $dorks_for_domain);
            }
        }
    }

    if (!empty($domains) && !empty($dork_type)) {
        $recent_searches = json_decode(file_get_contents('recent_searches.json'), true) ?? [];
        $recent_searches[] = ['domains' => implode(', ', $domains), 'type' => $dork_type, 'time' => date('Y-m-d H:i:s')];
        if (count($recent_searches) > 10) array_shift($recent_searches);
        file_put_contents('recent_searches.json', json_encode($recent_searches));
    }

    if (isset($_POST['save_favorite']) && !empty($generated_dorks)) {
        $_SESSION['favorites'] = $_SESSION['favorites'] ?? [];
        $_SESSION['favorites'][] = [
            'domains' => implode(', ', $domains),
            'type' => $dork_type,
            'dorks' => $generated_dorks
        ];
    }
}

$api_key = "Google_Custom_Search_API_key"; // Replace with your Google Custom Search API key
$cx = "Custom_Search_Engine_ID"; // Replace with your Custom Search Engine ID
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Silkscreen:wght@400;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    <title>Dorker - Advanced Google Dorks Generator</title>
    <style>
        :root {
            --bg-color: #0D1117;
            --text-color: #C9D1D9;
            --card-bg: #161B22;
            --border-color: #30363D;
            --accent-color: #58A6FF;
            --success-color: #238636;
            --warning-color: #D29922;
            --error-color: #d73a49;
        }

        body {
            font-family: 'Roboto Mono', monospace;
            background: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .title {
            font-family: 'Silkscreen', cursive;
            font-size: 48px;
            margin: 0;
        }

        .version {
            font-size: 14px;
            color: #8B949E;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 10px;
            align-items: center;
        }

        input, select {
            padding: 12px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            border-radius: 4px;
            font-family: 'Roboto Mono', monospace;
        }

        button {
            padding: 12px 24px;
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: #1f9a44;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        th {
            background: #21262D;
            font-weight: 700;
            color: var(--th-color);
        }

        .dork-link {
            color: var(--accent-color);
            text-decoration: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .copy-btn, .action-btn {
            padding: 4px 12px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            font-size: 12px;
            margin-left: 5px;
        }

        .copy-btn {
            background: #0366d6;
        }

        .copy-btn.copied {
            background: var(--success-color);
        }

        .preview-btn {
            background: var(--warning-color);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .scrollable {
            max-height: 300px;
            overflow-y: auto;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
        }

        .modal-content {
            background: var(--card-bg);
            color: var(--text-color);
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: #8B949E;
        }

        .floating-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--success-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .export-btn, .favorite-btn {
            margin-top: 20px;
            background: #0366d6;
            margin-right: 10px;
        }

        .theme-toggle {
            position: absolute;
            right: 0;
            top: 0;
            background: #0366d6;
        }

        .light-theme {
            --bg-color: #ffffff;
            --text-color: #24292e;
            --card-bg: #f6f8fa;
            --border-color: #d1d5da;
            --accent-color: #0366d6;
            --th-color: #f6f8fa
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #8B949E;
            font-size: 14px;
        }

        .preview-results {
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }

        .result-count {
            font-size: 12px;
            color: #8B949E;
            margin-left: 10px;
        }

        .error-message {
            color: var(--error-color);
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Dorker<span class="version">v1.0.0</span></h1>
            <button class="theme-toggle" onclick="toggleTheme()">Toggle Theme</button>
        </div>

        <div class="card">
            <form method="POST" class="form-grid">
                <input type="text" name="domain" placeholder="Enter domains (e.g., example.com, test.com)" value="<?php echo htmlspecialchars($domain_input ?? ''); ?>" required>
                <select name="dork_type">
                    <option value="">Select Dork Type</option>
                    <?php foreach (array_keys($dorks) as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $dork_type === $type ? 'selected' : ''; ?>>
                            <?php echo strtoupper(str_replace('_', ' ', $type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="custom_dork" placeholder="Custom Dork (optional)" value="<?php echo htmlspecialchars($custom_dork ?? ''); ?>">
                <button type="submit">Generate Dorks</button>
            </form>
            <?php if (!empty($validation_error)): ?>
                <p class="error-message"><?php echo $validation_error; ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($generated_dorks)): ?>
            <div class="card">
                <h3>Generated Dorks for <?php echo strtoupper(str_replace('_', ' ', $dork_type)); ?>:</h3>
                <table class="results-table">
                    <tr>
                        <th>Dork Query</th>
                    </tr>
                    <?php foreach ($generated_dorks as $index => $dork): ?>
                        <tr>
                            <td>
                                <div class="dork-link">
                                    <div>
                                        <a href="https://www.google.com/search?q=<?php echo urlencode($dork); ?>" target="_blank">
                                            <?php echo htmlspecialchars($dork); ?>
                                        </a>
                                        <span class="result-count" id="count-<?php echo $index; ?>">
                                        </span>
                                    </div>
                                    <div>
                                        <button class="copy-btn" data-clipboard-text="<?php echo htmlspecialchars($dork); ?>">Copy</button>
                                        <button class="preview-btn action-btn" onclick="previewDork(<?php echo $index; ?>)">Preview</button>
                                    </div>
                                </div>
                                <div id="preview-<?php echo $index; ?>" class="preview-results" style="display: none;"></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <button class="export-btn" onclick="exportToTxt()">Export to TXT</button>
                <button class="export-btn" onclick="exportToCsv()">Export to CSV</button>
                <button class="favorite-btn" onclick="document.getElementById('saveFavorite').submit()">Save as Favorite</button>
                <form id="saveFavorite" method="POST" style="display: none;">
                    <input type="hidden" name="domain" value="<?php echo htmlspecialchars($domain_input); ?>">
                    <input type="hidden" name="dork_type" value="<?php echo $dork_type; ?>">
                    <input type="hidden" name="save_favorite" value="1">
                </form>
            </div>
        <?php endif; ?>

        <div class="features-grid">
            <div class="card scrollable">
                <h3>Recent Searches</h3>
                <?php
                $recent_searches = json_decode(file_get_contents('recent_searches.json'), true) ?? [];
                if (!empty($recent_searches)):
                ?>
                    <table class="results-table">
                        <tr><th>Domains</th><th>Type</th><th>Time</th></tr>
                        <?php foreach (array_reverse($recent_searches) as $search): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($search['domains'] ?? $search['domain'] ?? 'N/A'); ?></td>
                                <td><?php echo strtoupper(str_replace('_', ' ', $search['type'])); ?></td>
                                <td><?php echo $search['time']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No recent searches yet.</p>
                <?php endif; ?>
            </div>

            <div class="card scrollable">
                <h3>Favorite Dorks</h3>
                <?php if (!empty($_SESSION['favorites'] ?? [])): ?>
                    <table class="results-table">
                        <tr><th>Domains</th><th>Type</th><th>Action</th></tr>
                        <?php foreach ($_SESSION['favorites'] as $index => $fav): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fav['domains'] ?? $fav['domain'] ?? 'N/A'); ?></td>
                                <td><?php echo strtoupper(str_replace('_', ' ', $fav['type'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="domain" value="<?php echo htmlspecialchars($fav['domains'] ?? $fav['domain'] ?? ''); ?>">
                                        <input type="hidden" name="dork_type" value="<?php echo $fav['type']; ?>">
                                        <button type="submit" class="action-btn" style="background: #0366d6;">Load</button>
                                    </form>
                                    <button class="action-btn" style="background: #d73a49;" onclick="removeFavorite(<?php echo $index; ?>)">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No favorites saved yet.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Quick Stats</h3>
                <p>Total Dork Types: <?php echo count($dorks); ?></p>
                <p>Total Dorks Available: <?php echo array_sum(array_map('count', $dorks)); ?></p>
                <p>Favorites Saved: <?php echo count($_SESSION['favorites'] ?? []); ?></p>
                <p>Last Updated: February 21, 2025</p>
            </div>
        </div>

        <button class="floating-btn" onclick="openHelpModal()">?</button>

        <div id="helpModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeHelpModal()">×</span>
                <h2>Dork Types Explained</h2>
                <p><strong>XSS:</strong> Finds potential Cross-Site Scripting vulnerabilities</p>
                <p><strong>SQLi:</strong> Identifies possible SQL Injection points</p>
                <p><strong>LFI:</strong> Looks for Local File Inclusion vulnerabilities</p>
                <p><strong>RCE:</strong> Searches for Remote Code Execution possibilities</p>
            </div>
        </div>

        <footer>
            Dorker v1.0.0 | © <?php echo date("Y"); ?> All Rights Reserved
        </footer>
    </div>

    <script>
        new ClipboardJS('.copy-btn').on('success', function(e) {
            e.trigger.classList.add('copied');
            setTimeout(() => e.trigger.classList.remove('copied'), 2000);
        });

        function openHelpModal() {
            document.getElementById('helpModal').style.display = 'block';
        }

        function closeHelpModal() {
            document.getElementById('helpModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('helpModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function exportToTxt() {
            const dorks = <?php echo json_encode($generated_dorks ?? []); ?>;
            const text = dorks.join('\n');
            const blob = new Blob([text], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `dorks_${<?php echo json_encode($dork_type); ?>}_${Date.now()}.txt`;
            a.click();
        }

        function exportToCsv() {
            const dorks = <?php echo json_encode($generated_dorks ?? []); ?>;
            const csv = ['Dork Query'].concat(dorks).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `dorks_${<?php echo json_encode($dork_type); ?>}_${Date.now()}.csv`;
            a.click();
        }

        function toggleTheme() {
            document.body.classList.toggle('light-theme');
            localStorage.setItem('theme', document.body.classList.contains('light-theme') ? 'light' : 'dark');
        }

        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-theme');
        }

        async function previewDork(index) {
            const dork = <?php echo json_encode($generated_dorks ?? []); ?>[index];
            const previewDiv = document.getElementById(`preview-${index}`);
            previewDiv.style.display = previewDiv.style.display === 'none' ? 'block' : 'none';
            
            if (previewDiv.innerHTML === '') {
                previewDiv.innerHTML = 'Loading...';
                try {
                    const response = await fetch(`https://www.googleapis.com/customsearch/v1?key=<?php echo $api_key; ?>&cx=<?php echo $cx; ?>&q=${encodeURIComponent(dork)}&num=5`);
                    const data = await response.json();
                    previewDiv.innerHTML = data.items ? 
                        data.items.map(item => `<p><a href="${item.link}" target="_blank">${item.title}</a></p>`).join('') : 
                        'No results found or API limit reached.';
                } catch (error) {
                    previewDiv.innerHTML = 'Error fetching preview: ' + error.message;
                }
            }
        }

        function removeFavorite(index) {
            if (confirm('Remove this favorite?')) {
                fetch('remove_favorite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ index })
                }).then(() => location.reload());
            }
        }

        window.onload = async function() {
            const dorks = <?php echo json_encode($generated_dorks ?? []); ?>;
            for (let i = 0; i < dorks.length; i++) {
                try {
                    const response = await fetch(`https://www.googleapis.com/customsearch/v1?key=<?php echo $api_key; ?>&cx=<?php echo $cx; ?>&q=${encodeURIComponent(dorks[i])}&num=1`);
                    const data = await response.json();
                    const count = data.searchInformation?.totalResults || 'N/A';
                    document.getElementById(`count-${i}`).textContent = `(${count} results)`;
                } catch (error) {
                    document.getElementById(`count-${i}`).textContent = '(Error)';
                }
            }
        };
    </script>
</body>
</html>
<?php
/**
 * Server Diagnostic Page - Connection & Performance Analysis
 * 
 * Access: /server-diag.php?token=YOUR_SECRET_TOKEN
 * Set the token below or via DIAG_TOKEN environment variable.
 */

// ============================================================
// SECURITY: Require DIAG_TOKEN env variable
// ============================================================
$expectedToken = getenv('DIAG_TOKEN');

if ($expectedToken === false || $expectedToken === '') {
    $envFile = __DIR__ . '/../.env';
    if (is_readable($envFile)) {
        $envContent = file_get_contents($envFile);
        if ($envContent !== false && preg_match('/^DIAG_TOKEN=(.*)$/m', $envContent, $m)) {
            $expectedToken = trim($m[1] ?? '');
            $expectedToken = trim($expectedToken, "\"'");
        }
    }
}

if ($expectedToken === false || $expectedToken === '') {
    http_response_code(500);
    echo 'Configuration error: DIAG_TOKEN is not set. Add DIAG_TOKEN to your environment (or .env) and retry.';
    exit;
}

if (!isset($_GET['token']) || !hash_equals($expectedToken, $_GET['token'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$startTime = microtime(true);

// ============================================================
// Helper functions
// ============================================================
function formatBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function statusBadge(string $status, string $text): string {
    $colors = [
        'ok' => '#27ae60',
        'warn' => '#f39c12',
        'error' => '#e74c3c',
        'info' => '#3498db',
    ];
    $color = $colors[$status] ?? $colors['info'];
    return "<span style='display:inline-block;padding:2px 10px;border-radius:12px;background:{$color};color:#fff;font-size:0.85em;font-weight:600;'>{$text}</span>";
}

// ============================================================
// 1. PHP & Server Info
// ============================================================
$phpVersion = PHP_VERSION;
$sapi = php_sapi_name();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$maxExecutionTime = ini_get('max_execution_time');
$memoryLimit = ini_get('memory_limit');
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxInputVars = ini_get('max_input_vars');
$disabledFunctions = explode(',', str_replace(' ', '', ini_get('disable_functions')));
$opcacheStatusCheckable = !in_array('opcache_get_status', $disabledFunctions);
$opcacheEnabled = false;
$opcacheDetectionMethod = 'function';
if ($opcacheStatusCheckable && function_exists('opcache_get_status')) {
    $opcacheEnabled = opcache_get_status() !== false;
} else {
    // Fallback: parse phpinfo() output
    ob_start();
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
    $phpinfoText = ob_get_clean();
    $opcacheDetectionMethod = 'phpinfo';
    $enabled = null;
    // Try to find the Zend OPcache section and check Opcode Caching
    // phpinfo() outputs HTML: values are in <td> cells, not separated by =>
    if (preg_match('/Opcode Caching\s*<\/td><td[^>]*>\s*Up and Running/i', $phpinfoText)) {
        $enabled = true;
    } elseif (preg_match('/opcache\.enable\s*<\/td><td[^>]*>\s*On/i', $phpinfoText)) {
        $enabled = true;
    } elseif (preg_match('/opcache\.enable\s*<\/td><td[^>]*>\s*Off/i', $phpinfoText)) {
        $enabled = false;
    }
    if ($enabled !== null) {
        $opcacheEnabled = $enabled;
    } else {
        $opcacheEnabled = false;
        $opcacheDetectionMethod = 'unknown';
    }
}

// ============================================================
// 2. Current Memory Usage
// ============================================================
$memUsage = memory_get_usage(true);
$memPeak = memory_get_peak_usage(true);

// ============================================================
// 3. Database Connection Test
// ============================================================
$dbStatus = 'Not tested';
$dbLatency = null;
$dbError = null;
$dbActiveConnections = null;

// Try to use Laravel's .env for DB config
$envFile = __DIR__ . '/../.env';
$dbConfig = [];
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    preg_match('/^DB_HOST=(.+)$/m', $envContent, $m); $dbConfig['host'] = trim($m[1] ?? '');
    preg_match('/^DB_PORT=(.+)$/m', $envContent, $m); $dbConfig['port'] = trim($m[1] ?? '3306');
    preg_match('/^DB_DATABASE=(.+)$/m', $envContent, $m); $dbConfig['database'] = trim($m[1] ?? '');
    preg_match('/^DB_USERNAME=(.+)$/m', $envContent, $m); $dbConfig['username'] = trim($m[1] ?? '');
    preg_match('/^DB_PASSWORD=(.+)$/m', $envContent, $m); $dbConfig['password'] = trim($m[1] ?? '');
}

if (!empty($dbConfig['host'])) {
    try {
        $dbStart = microtime(true);
        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
            $dbConfig['username'],
            $dbConfig['password'],
            [PDO::ATTR_TIMEOUT => 5]
        );
        $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
        $dbStatus = 'Connected';

        // Get active connections count
        $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $dbActiveConnections = $row['Value'] ?? null;

        // Get max connections
        $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $dbMaxConnections = $row['Value'] ?? null;

        $pdo = null; // close
    } catch (Exception $e) {
        $dbStatus = 'Failed';
        $dbError = $e->getMessage();
        $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
    }
}

// ============================================================
// 4. Server Load (Linux)
// ============================================================
$loadAvg = null;
if (function_exists('sys_getloadavg')) {
    $loadAvg = sys_getloadavg();
}

$cpuCount = null;
if (is_readable('/proc/cpuinfo')) {
    $cpuCount = substr_count(file_get_contents('/proc/cpuinfo'), 'processor');
}

// ============================================================
// 5. PHP-FPM Status (if available)
// ============================================================
$fpmStatus = null;
$fpmStatusUrl = 'http://127.0.0.1/fpm-status';
// Try to get FPM status via local request (often not available on shared hosting)
$fpmContext = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
$fpmResponse = @file_get_contents($fpmStatusUrl . '?json', false, $fpmContext);
if ($fpmResponse) {
    $fpmStatus = json_decode($fpmResponse, true);
}

// ============================================================
// 6. Network Connections Analysis (if available)
// ============================================================
$netstatOutput = null;
$connectionsByState = [];
if (function_exists('exec')) {
    @exec('ss -s 2>/dev/null || netstat -s 2>/dev/null', $netOutput, $retCode);
    if ($retCode === 0 && !empty($netOutput)) {
        $netstatOutput = implode("\n", $netOutput);
    }

    // Count connections to this server
    @exec("ss -tan 2>/dev/null | awk '{print \$1}' | sort | uniq -c | sort -rn 2>/dev/null", $connOutput);
    if (!empty($connOutput)) {
        foreach ($connOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 2) {
                $connectionsByState[$parts[1]] = (int)$parts[0];
            }
        }
    }
}

// ============================================================
// 7. Recent Access Analysis (bot detection from Laravel logs)
// ============================================================
$recentIPs = [];
$botHits = [];
$logFile = __DIR__ . '/../storage/logs/laravel.log';
$accessLogPaths = [
    '/var/log/nginx/access.log',
    '/var/log/apache2/access.log',
    '/var/log/httpd/access_log',
];

// Try to detect bots from server access log
$accessLogContent = '';
foreach ($accessLogPaths as $logPath) {
    if (is_readable($logPath)) {
        // Read last 500 lines
        $lines = [];
        $fp = @fopen($logPath, 'r');
        if ($fp) {
            // Seek near end
            fseek($fp, -100000, SEEK_END);
            fgets($fp); // skip partial line
            while (!feof($fp)) {
                $lines[] = fgets($fp);
            }
            fclose($fp);
            $lines = array_slice($lines, -500);
            $accessLogContent = implode('', $lines);
        }
        break;
    }
}

// Known bot user-agents
$botSignatures = [
    'Googlebot', 'bingbot', 'Baiduspider', 'YandexBot', 'DotBot', 'AhrefsBot',
    'SemrushBot', 'MJ12bot', 'PetalBot', 'GPTBot', 'ClaudeBot', 'Applebot',
    'facebookexternalhit', 'Twitterbot', 'LinkedInBot', 'Slurp', 'Sogou',
    'ia_archiver', 'Bytespider', 'crawler', 'spider', 'bot/', 'python-requests',
    'curl/', 'wget/', 'Go-http-client', 'Scrapy', 'Java/', 'libwww-perl',
];

// ============================================================
// 8. Session files count (indicator of active/stale sessions)
// ============================================================
$sessionPath = __DIR__ . '/../storage/framework/sessions';
$sessionCount = 0;
$staleSessionCount = 0;
if (is_dir($sessionPath)) {
    $files = glob($sessionPath . '/*');
    $sessionCount = count($files);
    $oneHourAgo = time() - 3600;
    foreach ($files as $file) {
        if (filemtime($file) < $oneHourAgo) {
            $staleSessionCount++;
        }
    }
}

// ============================================================
// 9. Cache/Queue tables check
// ============================================================
$queueJobCount = null;
$failedJobCount = null;
$cacheEntryCount = null;
if (isset($pdo) || (!empty($dbConfig['host']) && $dbStatus === 'Connected')) {
    try {
        if (!isset($pdo)) {
            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_TIMEOUT => 5]
            );
        }

        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM jobs");
        $queueJobCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? null;

        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM failed_jobs");
        $failedJobCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? null;

        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cache");
        $cacheEntryCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? null;

        $pdo = null;
    } catch (Exception $e) {
        // Tables may not exist
    }
}

// ============================================================
// 10. Disk usage
// ============================================================
$diskFree = @disk_free_space(__DIR__);
$diskTotal = @disk_total_space(__DIR__);

$totalTime = round((microtime(true) - $startTime) * 1000, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Diagnostics - Sapin Solidaire</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #1a1a2e; color: #e0e0e0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #00d2ff; margin-bottom: 10px; font-size: 1.6em; }
        .subtitle { color: #888; margin-bottom: 30px; font-size: 0.9em; }
        .card { background: #16213e; border-radius: 10px; padding: 20px; margin-bottom: 20px; border: 1px solid #0f3460; }
        .card h2 { color: #00d2ff; font-size: 1.1em; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 1px solid #0f3460; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .metric { background: #0f3460; border-radius: 8px; padding: 12px; }
        .metric .label { font-size: 0.75em; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .metric .value { font-size: 1.3em; font-weight: 700; color: #fff; margin-top: 4px; }
        .metric .value.ok { color: #27ae60; }
        .metric .value.warn { color: #f39c12; }
        .metric .value.error { color: #e74c3c; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #0f3460; font-size: 0.9em; }
        th { color: #00d2ff; font-weight: 600; }
        .footer { text-align: center; color: #555; font-size: 0.8em; margin-top: 30px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9em; }
        .alert-warn { background: #f39c1233; border: 1px solid #f39c12; color: #f39c12; }
        .alert-error { background: #e74c3c33; border: 1px solid #e74c3c; color: #e74c3c; }
        .alert-ok { background: #27ae6033; border: 1px solid #27ae60; color: #27ae60; }
        .alert-info { background: #3498db33; border: 1px solid #3498db; color: #3498db; }
        pre { background: #0f3460; padding: 10px; border-radius: 6px; overflow-x: auto; font-size: 0.8em; color: #ccc; white-space: pre-wrap; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Server Diagnostics</h1>
    <p class="subtitle">Sapin Solidaire — Generated at <?= date('Y-m-d H:i:s') ?> (page built in <?= $totalTime ?>ms)</p>

    <?php
    // ============================================================
    // DIAGNOSIS SUMMARY
    // ============================================================
    $issues = [];

    if ($dbLatency !== null && $dbLatency > 200) {
        $issues[] = ['error', "⚠️ Database latency is very high ({$dbLatency}ms). This causes PHP processes to wait, piling up nginx connections."];
    } elseif ($dbLatency !== null && $dbLatency > 50) {
        $issues[] = ['warn', "⚡ Database latency is elevated ({$dbLatency}ms). Consider using a local DB or connection pooling."];
    }

    if ($dbActiveConnections !== null && $dbMaxConnections !== null) {
        $ratio = $dbActiveConnections / $dbMaxConnections;
        if ($ratio > 0.8) {
            $issues[] = ['error', "🔴 Database connection usage is critical: {$dbActiveConnections}/{$dbMaxConnections}"];
        } elseif ($ratio > 0.5) {
            $issues[] = ['warn', "🟡 Database connections are elevated: {$dbActiveConnections}/{$dbMaxConnections}"];
        }
    }

    if ($loadAvg !== null && $cpuCount !== null && $loadAvg[0] > $cpuCount * 2) {
        $issues[] = ['error', "🔴 Server load is very high: " . round($loadAvg[0], 2) . " (with {$cpuCount} CPUs)"];
    } elseif ($loadAvg !== null && $cpuCount !== null && $loadAvg[0] > $cpuCount) {
        $issues[] = ['warn', "🟡 Server load is elevated: " . round($loadAvg[0], 2) . " (with {$cpuCount} CPUs)"];
    }

    if ($sessionCount > 100) {
        $issues[] = ['warn', "📁 High session file count: {$sessionCount} ({$staleSessionCount} stale). Bots may be generating sessions."];
    }

    if ($queueJobCount !== null && $queueJobCount > 50) {
        $issues[] = ['warn', "📬 Queue has {$queueJobCount} pending jobs. This may indicate the queue worker is stopped or too slow."];
    }

    if ($failedJobCount !== null && $failedJobCount > 0) {
        $issues[] = ['warn', "❌ There are {$failedJobCount} failed jobs in the queue."];
    }

    if (!$opcacheEnabled) {
        $issues[] = ['warn', "⚡ OPcache is not enabled. This significantly slows PHP response time on every request."];
    }

    if ($diskFree !== false && $diskTotal !== false) {
        $diskUsagePercent = round((1 - $diskFree / $diskTotal) * 100, 1);
        if ($diskUsagePercent > 90) {
            $issues[] = ['error', "💾 Disk usage is critical: {$diskUsagePercent}%"];
        }
    }

    if (isset($connectionsByState['CLOSE-WAIT']) && $connectionsByState['CLOSE-WAIT'] > 50) {
        $issues[] = ['error', "🔴 High CLOSE-WAIT connections ({$connectionsByState['CLOSE-WAIT']}): indicates connection leak!"];
    }
    if (isset($connectionsByState['TIME-WAIT']) && $connectionsByState['TIME-WAIT'] > 200) {
        $issues[] = ['warn', "🟡 Many TIME-WAIT connections ({$connectionsByState['TIME-WAIT']}): connections not being reused."];
    }

    if (empty($issues)) {
        echo '<div class="alert alert-ok">✅ No obvious issues detected from this diagnostic. The problem may be external (bots, shared hosting contention, or nginx config).</div>';
    } else {
        foreach ($issues as [$severity, $message]) {
            echo "<div class='alert alert-{$severity}'>{$message}</div>";
        }
    }
    ?>

    <!-- PHP & Server -->
    <div class="card">
        <h2>🖥️ PHP & Server</h2>
        <div class="grid">
            <div class="metric">
                <div class="label">PHP Version</div>
                <div class="value"><?= $phpVersion ?></div>
            </div>
            <div class="metric">
                <div class="label">SAPI</div>
                <div class="value"><?= $sapi ?></div>
            </div>
            <div class="metric">
                <div class="label">Server</div>
                <div class="value" style="font-size:0.85em"><?= htmlspecialchars($serverSoftware) ?></div>
            </div>
            <div class="metric">
                <div class="label">OPcache</div>
                <div class="value <?= $opcacheEnabled ? 'ok' : 'warn' ?>"><?= $opcacheEnabled ? 'Enabled' : 'Disabled' ?></div>
            </div>
            <div class="metric">
                <div class="label">Memory Limit</div>
                <div class="value"><?= $memoryLimit ?></div>
            </div>
            <div class="metric">
                <div class="label">Max Execution</div>
                <div class="value"><?= $maxExecutionTime ?>s</div>
            </div>
            <div class="metric">
                <div class="label">Upload Max</div>
                <div class="value"><?= $uploadMaxFilesize ?></div>
            </div>
            <div class="metric">
                <div class="label">Post Max</div>
                <div class="value"><?= $postMaxSize ?></div>
            </div>
        </div>
    </div>

    <!-- Memory -->
    <div class="card">
        <h2>🧠 Memory Usage (this request)</h2>
        <div class="grid">
            <div class="metric">
                <div class="label">Current</div>
                <div class="value"><?= formatBytes($memUsage) ?></div>
            </div>
            <div class="metric">
                <div class="label">Peak</div>
                <div class="value"><?= formatBytes($memPeak) ?></div>
            </div>
        </div>
    </div>

    <!-- Database -->
    <div class="card">
        <h2>🗄️ Database</h2>
        <?php if ($dbError): ?>
            <div class="alert alert-error">Connection failed: <?= htmlspecialchars($dbError) ?></div>
        <?php endif; ?>
        <div class="grid">
            <div class="metric">
                <div class="label">Status</div>
                <div class="value <?= $dbStatus === 'Connected' ? 'ok' : 'error' ?>"><?= $dbStatus ?></div>
            </div>
            <div class="metric">
                <div class="label">Latency</div>
                <div class="value <?= ($dbLatency ?? 0) > 200 ? 'error' : (($dbLatency ?? 0) > 50 ? 'warn' : 'ok') ?>">
                    <?= $dbLatency !== null ? $dbLatency . 'ms' : 'N/A' ?>
                </div>
            </div>
            <div class="metric">
                <div class="label">Host</div>
                <div class="value" style="font-size:0.85em"><?= htmlspecialchars($dbConfig['host'] ?? 'N/A') ?></div>
            </div>
            <?php if ($dbActiveConnections !== null): ?>
            <div class="metric">
                <div class="label">Active Connections</div>
                <div class="value"><?= $dbActiveConnections ?><?= isset($dbMaxConnections) ? " / {$dbMaxConnections}" : '' ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Server Load -->
    <?php if ($loadAvg !== null): ?>
    <div class="card">
        <h2>📊 Server Load</h2>
        <div class="grid">
            <div class="metric">
                <div class="label">1 min avg</div>
                <div class="value <?= ($cpuCount && $loadAvg[0] > $cpuCount) ? 'warn' : 'ok' ?>"><?= round($loadAvg[0], 2) ?></div>
            </div>
            <div class="metric">
                <div class="label">5 min avg</div>
                <div class="value"><?= round($loadAvg[1], 2) ?></div>
            </div>
            <div class="metric">
                <div class="label">15 min avg</div>
                <div class="value"><?= round($loadAvg[2], 2) ?></div>
            </div>
            <?php if ($cpuCount): ?>
            <div class="metric">
                <div class="label">CPU Cores</div>
                <div class="value"><?= $cpuCount ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Network Connections -->
    <?php if (!empty($connectionsByState)): ?>
    <div class="card">
        <h2>🌐 Network Connections</h2>
        <div class="grid">
            <?php foreach ($connectionsByState as $state => $count): ?>
            <div class="metric">
                <div class="label"><?= htmlspecialchars($state) ?></div>
                <div class="value <?= ($state === 'CLOSE-WAIT' && $count > 50) ? 'error' : (($state === 'TIME-WAIT' && $count > 200) ? 'warn' : '') ?>">
                    <?= $count ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sessions -->
    <div class="card">
        <h2>📁 Sessions (file-based)</h2>
        <div class="grid">
            <div class="metric">
                <div class="label">Total Files</div>
                <div class="value <?= $sessionCount > 100 ? 'warn' : 'ok' ?>"><?= $sessionCount ?></div>
            </div>
            <div class="metric">
                <div class="label">Stale (&gt;1h)</div>
                <div class="value <?= $staleSessionCount > 50 ? 'warn' : '' ?>"><?= $staleSessionCount ?></div>
            </div>
        </div>
        <?php if ($sessionCount > 100): ?>
            <div class="alert alert-warn" style="margin-top:12px;">
                High session count may indicate bots creating sessions. Consider adding bot protection (e.g., robots.txt, rate limiting).
            </div>
        <?php endif; ?>
    </div>

    <!-- Queue & Cache -->
    <div class="card">
        <h2>📬 Queue & Cache (database)</h2>
        <div class="grid">
            <div class="metric">
                <div class="label">Pending Jobs</div>
                <div class="value <?= ($queueJobCount ?? 0) > 50 ? 'warn' : 'ok' ?>">
                    <?= $queueJobCount !== null ? $queueJobCount : 'N/A' ?>
                </div>
            </div>
            <div class="metric">
                <div class="label">Failed Jobs</div>
                <div class="value <?= ($failedJobCount ?? 0) > 0 ? 'warn' : 'ok' ?>">
                    <?= $failedJobCount !== null ? $failedJobCount : 'N/A' ?>
                </div>
            </div>
            <div class="metric">
                <div class="label">Cache Entries</div>
                <div class="value"><?= $cacheEntryCount !== null ? $cacheEntryCount : 'N/A' ?></div>
            </div>
        </div>
    </div>

    <!-- Disk -->
    <?php if ($diskFree !== false && $diskTotal !== false): ?>
    <div class="card">
        <h2>💾 Disk</h2>
        <div class="grid">
            <div class="metric">
                <div class="label">Free</div>
                <div class="value"><?= formatBytes($diskFree) ?></div>
            </div>
            <div class="metric">
                <div class="label">Total</div>
                <div class="value"><?= formatBytes($diskTotal) ?></div>
            </div>
            <div class="metric">
                <div class="label">Usage</div>
                <?php $diskPct = round((1 - $diskFree / $diskTotal) * 100, 1); ?>
                <div class="value <?= $diskPct > 90 ? 'error' : ($diskPct > 75 ? 'warn' : 'ok') ?>"><?= $diskPct ?>%</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PHP-FPM Status -->
    <?php if ($fpmStatus): ?>
    <div class="card">
        <h2>⚙️ PHP-FPM</h2>
        <div class="grid">
            <div class="metric">
                <div class="label">Active Processes</div>
                <div class="value"><?= $fpmStatus['active processes'] ?? 'N/A' ?></div>
            </div>
            <div class="metric">
                <div class="label">Idle Processes</div>
                <div class="value"><?= $fpmStatus['idle processes'] ?? 'N/A' ?></div>
            </div>
            <div class="metric">
                <div class="label">Total Processes</div>
                <div class="value"><?= $fpmStatus['total processes'] ?? 'N/A' ?></div>
            </div>
            <div class="metric">
                <div class="label">Max Active</div>
                <div class="value"><?= $fpmStatus['max active processes'] ?? 'N/A' ?></div>
            </div>
            <div class="metric">
                <div class="label">Listen Queue</div>
                <div class="value <?= ($fpmStatus['listen queue'] ?? 0) > 0 ? 'warn' : 'ok' ?>">
                    <?= $fpmStatus['listen queue'] ?? 'N/A' ?>
                </div>
            </div>
            <div class="metric">
                <div class="label">Max Listen Queue</div>
                <div class="value"><?= $fpmStatus['max listen queue'] ?? 'N/A' ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recommendations -->
    <div class="card">
        <h2>💡 Recommendations for "worker_connections are not enough"</h2>
        <div style="font-size: 0.9em; line-height: 1.7;">
            <p><strong>1. Check if bots are flooding your site:</strong></p>
            <ul style="margin: 8px 0 16px 20px;">
                <li>Look at the session count above — if it's high, bots are likely creating sessions</li>
                <li>Add a <code>robots.txt</code> to block aggressive crawlers</li>
                <li>Ask Kreativmedia if they can show you nginx access logs</li>
            </ul>

            <p><strong>2. Database latency:</strong></p>
            <ul style="margin: 8px 0 16px 20px;">
                <li>Your DB is on <code><?= htmlspecialchars($dbConfig['host'] ?? '?') ?></code> — if latency is &gt;50ms, each request holds an nginx connection longer</li>
                <li>Livewire makes AJAX calls for every interaction, each holding a connection while waiting for DB</li>
                <li>Consider moving DB to localhost or same datacenter</li>
            </ul>

            <p><strong>3. Ask Kreativmedia to:</strong></p>
            <ul style="margin: 8px 0 16px 20px;">
                <li>Increase <code>worker_connections</code> in nginx (e.g., 4096)</li>
                <li>Enable <code>keepalive</code> to upstream PHP-FPM</li>
                <li>Check if other hosted sites are consuming all connections</li>
                <li>Provide nginx access logs so you can identify bot traffic</li>
            </ul>

            <p><strong>4. Application optimizations:</strong></p>
            <ul style="margin: 8px 0 16px 20px;">
                <li>Enable OPcache (<?= $opcacheEnabled ? '✅ already enabled' : '❌ currently disabled' ?>)</li>
                <li>Run <code>php artisan config:cache</code> and <code>php artisan route:cache</code> in production</li>
                <li>Consider switching session/cache drivers from <code>database</code>/<code>file</code> to <code>redis</code> if available</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        <p>🔒 This page is protected by token. Change the token in the source file or set DIAG_TOKEN env variable.</p>
        <p>Diagnostic generated in <?= $totalTime ?>ms</p>
    </div>

    <!-- PHPINFO Section -->
    <div class="card" style="margin-top:30px;">
        <h2 style="cursor:pointer;" onclick="const s=document.getElementById('phpinfo-section');s.style.display=s.style.display==='none'?'block':'none';">🛠️ PHPINFO <span style='font-size:0.8em;color:#888;'>(click to toggle)</span></h2>
        <div id="phpinfo-section" style="display:none;">
            <?php
            ob_start();
            phpinfo();
            $phpinfo = ob_get_clean();
            // Remove DOCTYPE, html, head, and body tags for embedding
            $phpinfo = preg_replace('/<!DOCTYPE html.*?<body>/is', '', $phpinfo);
            $phpinfo = preg_replace('/<\/body>.*?<\/html>/is', '', $phpinfo);
            // Style override for dark background
            $phpinfo = str_replace('body {', 'body { background: #16213e !important; color: #e0e0e0 !important;', $phpinfo);
            echo '<div style="overflow-x:auto; max-height:600px; background:#0f3460; border-radius:8px; padding:10px;">' . $phpinfo . '</div>';
            ?>
        </div>
    </div>
</div>
</body>
</html>

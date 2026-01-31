<?php
// Configuration
$iterations = 1000000;
$fileIterations = 10000;
$tempFile = sys_get_temp_dir() . '/benchmark_test_' . uniqid() . '.tmp';

$start = microtime(true);

// CPU Test - Math
$mathStart = microtime(true);
for($i=0; $i<$iterations; $i++) {
    $a = sqrt($i);
    $b = sin($i);
    $c = cos($i);
}
$mathEnd = microtime(true);
$mathTime = $mathEnd - $mathStart;

// CPU Test - String
$stringStart = microtime(true);
$string = "benchmark_test_string";
for($i=0; $i<$iterations; $i++) {
    $x = str_shuffle($string);
    $y = md5($x);
}
$stringEnd = microtime(true);
$stringTime = $stringEnd - $stringStart;

// File Test - Write
$fileWriteStart = microtime(true);
for($i=0; $i<$fileIterations; $i++) {
    file_put_contents($tempFile, "Benchmark test data line $i\n", FILE_APPEND);
}
$fileWriteEnd = microtime(true);
$fileWriteTime = $fileWriteEnd - $fileWriteStart;

// File Test - Read
$fileReadStart = microtime(true);
for($i=0; $i<$fileIterations; $i++) {
    $content = file_get_contents($tempFile);
}
$fileReadEnd = microtime(true);
$fileReadTime = $fileReadEnd - $fileReadStart;

// Cleanup temp file
if(file_exists($tempFile)) {
    unlink($tempFile);
}

$totalTime = microtime(true) - $start;

// Calculate Score (lower time = higher score)
// Base reference times (in seconds) for normalization
$refMath = 0.5;
$refString = 1.0;
$refFileWrite = 0.3;
$refFileRead = 0.2;

$mathScore = ($refMath / $mathTime) * 1000;
$stringScore = ($refString / $stringTime) * 1000;
$fileWriteScore = ($refFileWrite / $fileWriteTime) * 1000;
$fileReadScore = ($refFileRead / $fileReadTime) * 1000;

$totalScore = round(($mathScore + $stringScore + $fileWriteScore + $fileReadScore) / 4);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Benchmark</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; padding: 2em; max-width: 800px; margin: 0 auto; line-height: 1.6; }
        h1 { border-bottom: 2px solid #eee; padding-bottom: 0.5em; }
        .card { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 1.5em; margin-bottom: 1em; }
        .score { font-size: 1.5em; font-weight: bold; color: #2c3e50; }
        .metric { display: flex; justify-content: space-between; margin-bottom: 0.5em; border-bottom: 1px dashed #ccc; padding-bottom: 0.25em; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 0.5em 1em; text-decoration: none; border-radius: 4px; cursor: pointer; border: none; font-size: 1em; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Server Performance Benchmark</h1>
    
    <div class="card">
        <h2>Server Info</h2>
        <div class="metric">
            <span>Software:</span>
            <strong><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></strong>
        </div>
        <div class="metric">
            <span>PHP Version:</span>
            <strong><?php echo phpversion(); ?></strong>
        </div>
        <div class="metric">
            <span>OS:</span>
            <strong><?php echo PHP_OS; ?></strong>
        </div>
    </div>

    <div class="card">
        <h2>Results (<?php echo number_format($iterations); ?> iterations)</h2>
        
        <div class="metric">
            <span>Math (Float/Trig) Time:</span>
            <span><?php echo number_format($mathTime, 4); ?> sec</span>
        </div>
        
        <div class="metric">
            <span>String (Shuffle/MD5) Time:</span>
            <span><?php echo number_format($stringTime, 4); ?> sec</span>
        </div>
    </div>

    <div class="card">
        <h2>File I/O (<?php echo number_format($fileIterations); ?> iterations)</h2>
        
        <div class="metric">
            <span>File Write Time:</span>
            <span><?php echo number_format($fileWriteTime, 4); ?> sec</span>
        </div>
        
        <div class="metric">
            <span>File Read Time:</span>
            <span><?php echo number_format($fileReadTime, 4); ?> sec</span>
        </div>
    </div>

    <div class="card">
        <h2>Final Score</h2>
        <div class="metric">
            <span>Math Score:</span>
            <span><?php echo number_format($mathScore, 0); ?></span>
        </div>
        <div class="metric">
            <span>String Score:</span>
            <span><?php echo number_format($stringScore, 0); ?></span>
        </div>
        <div class="metric">
            <span>File Write Score:</span>
            <span><?php echo number_format($fileWriteScore, 0); ?></span>
        </div>
        <div class="metric">
            <span>File Read Score:</span>
            <span><?php echo number_format($fileReadScore, 0); ?></span>
        </div>
        
        <div style="margin-top: 1em; text-align: center; padding: 1em; background: #2c3e50; color: white; border-radius: 4px;">
            <span>Overall Score:</span><br>
            <span style="font-size: 2em; font-weight: bold;"><?php echo number_format($totalScore); ?></span>
            <br><small>(Higher is better)</small>
        </div>
        
        <div style="margin-top: 1em; text-align: right;">
            <span>Total Execution Time:</span><br>
            <span class="score"><?php echo number_format($totalTime, 4); ?> seconds</span>
        </div>
    </div>
    
    <p>Lower time methods faster performance.</p>
    
    <button class="btn" onclick="window.location.reload();">Run Benchmark Again</button>
</body>
</html>

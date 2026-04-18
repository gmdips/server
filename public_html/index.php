<?php
/**
 * GDPS Dashboard - Root Entry Point
 * Lokasi: /public_html/index.php
 * ✅ Minimal, safe untuk shared hosting
 */

// Redirect semua request ke dashboard/
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$query = $_SERVER['QUERY_STRING'] ?? '';

// Hindari redirect loop jika sudah di /dashboard/
if (strpos($uri, '/dashboard') !== 0 && strpos($uri, '/install.php') !== 0) {
    $target = '/dashboard' . (strpos($uri, '?') === 0 ? '' : '/' . ltrim($uri, '/'));
    if (!empty($query) && strpos($target, '?') === false) {
        $target .= '?' . $query;
    }

    if (!headers_sent()) {
        header('Location: ' . $target, true, 302);
        exit();
    }
}

// Fallback: load dashboard index jika redirect gagal
$dashboardIndex = __DIR__ . '/dashboard/index.php';
if (file_exists($dashboardIndex)) {
    require $dashboardIndex;
} else {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:50px;">';
    echo '<h1>⚠️ Dashboard Not Found</h1>';
    echo '<p>Silakan pastikan folder <code>dashboard/</code> ada di dalam <code>public_html/</code>.</p>';
    echo '</body></html>';
}
?>

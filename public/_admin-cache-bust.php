<?php
// Outil ponctuel : purge opcache + caches Laravel.
// À supprimer dès qu'un déploiement propre est en place.

$token = $_GET['token'] ?? '';
$expected = 'salti-' . substr(md5(__FILE__), 0, 16); // jeton dérivé du fichier

if ($token !== $expected) {
    http_response_code(403);
    echo "Token attendu : ?token={$expected}";
    exit;
}

$results = [];

if (function_exists('opcache_reset')) {
    $results[] = opcache_reset() ? '✓ opcache_reset() OK' : '✗ opcache_reset() refusé';
} else {
    $results[] = '— opcache non disponible (déjà désactivé ?)';
}

if (function_exists('opcache_get_status')) {
    $status = @opcache_get_status(false);
    if ($status) {
        $results[] = '✓ opcache enabled = ' . ($status['opcache_enabled'] ? 'true' : 'false');
        $results[] = '  cached scripts : ' . ($status['opcache_statistics']['num_cached_scripts'] ?? 'n/a');
    }
}

// Bonus : touch les fichiers de vue pour forcer la recompilation
$compiled = __DIR__ . '/../storage/framework/views';
if (is_dir($compiled)) {
    $count = 0;
    foreach (glob($compiled . '/*.php') as $f) { @unlink($f); $count++; }
    $results[] = "✓ Supprimé {$count} vues compilées dans storage/framework/views";
}

header('Content-Type: text/plain; charset=utf-8');
echo "Cache bust :\n\n" . implode("\n", $results) . "\n\nMaintenant recharge ton dashboard avec Cmd+Shift+R.\n\n⚠ Pense à supprimer ce fichier après usage : public/_admin-cache-bust.php";

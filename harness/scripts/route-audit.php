<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$outputPath = $argv[1] ?? 'php://stdout';

$publicNamePatterns = [
    'login',
    'logout',
    'refresh-csrf',
    'new_passwords.*',
    'password_regenerations.*',
    'signup.*',
    'terms',
    'disasters.show',
    'disasters.school_districts',
    'disasters.historical_boundaries',
];

$publicUriPrefixes = [
    '_ignition/',
    '_debugbar/',
    'sanctum/',
];

$publicUris = [
    '/',
    'up',
    'login',
    'logout',
    'refresh-csrf',
];

$failures = [];
$routes = [];

foreach (app('router')->getRoutes() as $route) {
    $name = $route->getName() ?? '';
    $uri = $route->uri();
    $middleware = $route->gatherMiddleware();

    $hasAuth = false;
    foreach ($middleware as $item) {
        if (!is_string($item)) {
            continue;
        }

        if ($item === 'auth' || strpos($item, 'auth:') === 0) {
            $hasAuth = true;
            break;
        }
    }

    $isPublic = false;
    foreach ($publicNamePatterns as $pattern) {
        if ($name !== '' && fnmatch($pattern, $name)) {
            $isPublic = true;
            break;
        }
    }
    if (!$isPublic) {
        foreach ($publicUriPrefixes as $prefix) {
            if (strpos($uri, $prefix) === 0) {
                $isPublic = true;
                break;
            }
        }
    }
    if (!$isPublic && in_array($uri, $publicUris, true)) {
        $isPublic = true;
    }

    $record = [
        'name' => $name,
        'uri' => $uri,
        'methods' => $route->methods(),
        'middleware' => array_values(array_filter($middleware, 'is_string')),
        'has_auth' => $hasAuth,
        'is_public' => $isPublic,
    ];
    $routes[] = $record;

    if (!$isPublic && !$hasAuth) {
        $failures[] = $record;
    }
}

$result = [
    'generated_at' => date('c'),
    'route_count' => count($routes),
    'failures' => $failures,
    'routes' => $routes,
];

file_put_contents(
    $outputPath,
    json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
);

if ($failures) {
    fwrite(STDERR, "Unauthenticated routes detected: " . count($failures) . PHP_EOL);
    foreach ($failures as $failure) {
        fwrite(STDERR, '  ' . ($failure['name'] ?: '(no name)') . ' ' . $failure['uri'] . PHP_EOL);
    }
    exit(1);
}

exit(0);

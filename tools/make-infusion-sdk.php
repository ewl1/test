<?php

require_once dirname(__DIR__) . '/maincore.php';

$autoloadCandidates = [
    BASEDIR . 'includes/vendor/autoload.php',
    BASEDIR . 'vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoloadPath) {
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

$folder = trim((string)($argv[1] ?? ''));
$name = trim((string)($argv[2] ?? ''));
$description = trim((string)($argv[3] ?? ''));

if ($folder === '') {
    fwrite(STDERR, "Naudojimas: php tools/make-infusion-sdk.php <folder> [Pavadinimas] [Aprasymas]\n");
    exit(1);
}

try {
    $files = \App\MiniCMS\Infusions\ModuleScaffolder::scaffold(
        BASEDIR,
        $folder,
        $name !== '' ? $name : null,
        $description !== '' ? $description : null
    );

    echo "Sukurtas SDK modulis:\n";
    foreach ($files as $file) {
        echo '- ' . str_replace('\\', '/', $file) . PHP_EOL;
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Klaida: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

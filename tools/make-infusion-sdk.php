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
    fwrite(STDERR, "Scaffold sukuria plonus entrypoint failus, support/, classes/, migrations/, locale/ ir assets/ struktura.\n");
    fwrite(STDERR, "Jei modulis tures nustatymu puslapi, rekomenduojama veliau igyvendinti ModuleSettingsContract.\n");
    fwrite(STDERR, "Jei modulis tures diagnostikos puslapi ar health check logika, rekomenduojama veliau igyvendinti ModuleDiagnosticsContract.\n");
    fwrite(STDERR, "Jei modulis skelbs notifications ar activity feed ivykius, rekomenduojama veliau igyvendinti ModuleEventContract.\n");
    fwrite(STDERR, "Jei modulis teiks paieskos saltinius, rekomenduojama veliau igyvendinti ModuleSearchContract.\n");
    fwrite(STDERR, "Jei modulis nores vienodai deklaruoti korteles badge ir detales sekcijas, rekomenduojama veliau igyvendinti ModulePresentationContract.\n");
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

<?php
/**
 * Downloads Module - Lithuanian (Lietuvių) Language Pack
 */

return [
    // Admin Panel - Tabs
    'downloads.admin.tabs.categories' => 'Kategorijų kurimas',
    'downloads.admin.tabs.downloads' => 'Siuntinių įkėlimas',
    'downloads.admin.tabs.settings' => 'Nustatymai',

    // Categories Section
    'downloads.categories.title' => 'Esamos kategorijos',
    'downloads.categories.empty' => 'Kategorijų nėra.',
    'downloads.categories.form.title' => 'Pavadinimas',
    'downloads.categories.form.description' => 'Aprašymas',
    'downloads.categories.form.add' => 'Pridėti kategoriją',
    'downloads.categories.form.edit' => 'Redaguoti kategoriją',
    'downloads.categories.form.save' => 'Išsaugoti',
    'downloads.categories.form.cancel' => 'Atšaukti',
    'downloads.categories.actions.edit' => 'Redaguoti',
    'downloads.categories.actions.delete' => 'Trinti',
    'downloads.categories.confirm.delete' => 'Ištrinti kategoriją? Atsisiuntimai liks be kategorijos.',
    'downloads.categories.error.empty_name' => 'Kategorijos pavadinimas negali būti tuščias.',
    'downloads.categories.success.created' => 'Kategorija pridėta.',
    'downloads.categories.success.updated' => 'Kategorija atnaujinta.',
    'downloads.categories.success.deleted' => 'Kategorija ištrinta.',

    // Downloads Section
    'downloads.downloads.title' => 'Esami atsisiuntimai',
    'downloads.downloads.empty' => 'Atsisiuntimų nėra.',
    'downloads.downloads.form.title' => 'Pavadinimas',
    'downloads.downloads.form.category' => 'Kategorija',
    'downloads.downloads.form.description' => 'Aprašymas',
    'downloads.downloads.form.no_category' => '— Be kategorijos —',
    'downloads.downloads.form.source' => 'Šaltinis',
    'downloads.downloads.form.source.file' => 'Failų įkėlimas',
    'downloads.downloads.form.source.url' => 'Išorinė nuoroda',
    'downloads.downloads.form.file' => 'Failas',
    'downloads.downloads.form.file.existing' => 'Esamas: :file (:size)',
    'downloads.downloads.form.file.keep_empty' => 'palikite tuščią jei nekeičiate',
    'downloads.downloads.form.file.allowed' => 'Leidžiami tipai: :types',
    'downloads.downloads.form.url' => 'Nuoroda į failą',
    'downloads.downloads.form.url.placeholder' => 'https://example.com/file.zip',
    'downloads.downloads.form.url.info' => 'http:// arba https:// nuoroda į išorinį failą.',
    'downloads.downloads.form.thumbnail' => 'Thumbnail (nebūtinas)',
    'downloads.downloads.form.thumbnail.size' => 'Rekomenduojamas dydis: 200×150 px.',
    'downloads.downloads.form.thumbnail.delete' => 'Ištrinti thumbnail',
    'downloads.downloads.form.add' => 'Pridėti atsisiuntimą',
    'downloads.downloads.form.edit' => 'Redaguoti atsisiuntimą',
    'downloads.downloads.form.save' => 'Išsaugoti',
    'downloads.downloads.form.cancel' => 'Atšaukti',
    'downloads.downloads.table.type' => 'Tipas',
    'downloads.downloads.table.category' => 'Kategorija',
    'downloads.downloads.table.size' => 'Dydis',
    'downloads.downloads.table.downloads' => '↓',
    'downloads.downloads.table.type.file' => 'Failas',
    'downloads.downloads.table.type.url' => 'Nuoroda',
    'downloads.downloads.actions.edit' => 'Redaguoti',
    'downloads.downloads.actions.delete' => 'Trinti',
    'downloads.downloads.confirm.delete.file' => 'Ištrinti atsisiuntimą ir failą?',
    'downloads.downloads.confirm.delete.url' => 'Ištrinti šią nuorodą?',
    'downloads.downloads.error.empty_title' => 'Pavadinimas negali būti tuščias.',
    'downloads.downloads.error.invalid_url' => 'Nurodykite teisingą http:// arba https:// nuorodą.',
    'downloads.downloads.error.no_file' => 'Naujai įrašui reikia pasirinkti failą.',
    'downloads.downloads.error.invalid_type' => 'Neleidžiamas failo tipas. Leidžiami: :types',
    'downloads.downloads.error.upload_failed' => 'Failo įkelti nepavyko.',
    'downloads.downloads.error.thumbnail.invalid_type' => 'Thumbnail: leidžiami tik vaizdai (jpg, png, gif, webp).',
    'downloads.downloads.error.thumbnail.upload_failed' => 'Thumbnail įkelti nepavyko.',
    'downloads.downloads.success.created' => 'Atsisiuntimas pridėtas.',
    'downloads.downloads.success.updated' => 'Atsisiuntimas atnaujintas.',
    'downloads.downloads.success.deleted' => 'Atsisiuntimas ištrintas.',

    // Settings Section
    'downloads.settings.title' => 'Modulio nustatymai',
    'downloads.settings.save' => 'Išsaugoti nustatymus',
    'downloads.settings.max_file_size' => 'Maksimalus failo dydis (baitais)',
    'downloads.settings.max_file_size.info' => 'Dabar: :size (Minimum 1 MB = 1048576 baitai)',
    'downloads.settings.max_file_size.min_error' => 'Maksimalus failo dydis turi būti bent 1 MB.',
    'downloads.settings.show_thumbnails' => 'Rodyti nuotraukų peržiūras sąraše',
    'downloads.settings.show_thumbnails.info' => 'Jei įjungta, siuntinių sąraše bus rodomos thumbnail nuotraukos.',
    'downloads.settings.info.title' => 'Informacija',
    'downloads.settings.info.max_file_size' => 'Maksimalus failo dydis: Riboja failų dydį, kuriuos naudotojai gali įkelti. (Jei jūsų serveris riboja dydį, naudojamas mažesnis iš jų.)',
    'downloads.settings.info.thumbnails' => 'Thumbnail nuotraukos: Jautrūs mažos peržiūros vaizdeliai siuntinių sąraše. Padidina vizualinį patrauklumą.',
    'downloads.settings.success' => 'Nustatymai išsaugoti.',

    // Frontend - Page Title & Tabs
    'downloads.frontend.page.title' => 'Atsisiuntimai',
    'downloads.frontend.all_downloads' => 'Visi atsisiuntimai',
    'downloads.frontend.my_downloads' => 'Mano įkelti',
    'downloads.frontend.no_files_uploaded' => 'Dar neįkėlėte jokių failų.',
    'downloads.frontend.no_categories' => 'Atsisiuntimų kategorijų nerasta.',

    // Frontend - Filtering
    'downloads.frontend.filter.label' => 'Filtruoti pagal kategoriją:',
    'downloads.frontend.filter.all' => 'Visos',
    'downloads.frontend.filter.uncategorized' => 'Be kategorijos',
    'downloads.frontend.filter.empty' => 'Šioje kategorijoje atsisiuntimų nėra.',

    // Frontend - Table Headers
    'downloads.frontend.table.title' => 'Pavadinimas',
    'downloads.frontend.table.size' => 'Dydis',
    'downloads.frontend.table.downloads' => '↓',
    'downloads.frontend.table.uploader' => 'Įkėlė',
    'downloads.frontend.table.date' => 'Data',

    // General
    'general.yes' => 'Taip',
    'general.no' => 'Ne',
    'general.back' => 'Grįžti',
    'general.close' => 'Užverti',

    // JavaScript / Frontend Messages
    'downloads.js.file_selected' => 'Pasirinktas',
    'downloads.js.file_size_bytes' => 'Baitai',
    'downloads.js.file_size_kb' => 'KB',
    'downloads.js.file_size_mb' => 'MB',
    'downloads.js.file_size_gb' => 'GB',
];

<?php
function http_error_defaults($code)
{
    $code = (int)$code;

    return match ($code) {
        400 => ['title' => 'Netinkama uzklausa', 'message' => 'Uzklausa negalejo buti apdorota. Patikrinkite ivestus duomenis ir bandykite dar karta.'],
        401 => ['title' => 'Reikalingas prisijungimas', 'message' => 'Norint pasiekti si puslapi, reikia prisijungti.'],
        403 => ['title' => 'Prieiga uzdrausta', 'message' => 'Neturite teisiu atlikti sio veiksmo.'],
        404 => ['title' => 'Puslapis nerastas', 'message' => 'Ieskomas puslapis neegzistuoja arba buvo perkeltas.'],
        408 => ['title' => 'Uzklausos laikas baigesi', 'message' => 'Serveris per ilgai lauke uzklausos. Pabandykite dar karta.'],
        500 => ['title' => 'Vidine serverio klaida', 'message' => 'Ivyko nenumatyta klaida. Ji uzregistruota zurnale.'],
        502 => ['title' => 'Serverio rysio klaida', 'message' => 'Nepavyko gauti atsakymo is vidines sistemos arba duomenu bazes.'],
        default => ['title' => 'Klaida', 'message' => 'Ivyko klaida apdorojant jusu uzklausa.'],
    };
}

function render_http_error_page($code, $message = null, $title = null)
{
    $code = (int)$code;
    $defaults = http_error_defaults($code);

    $errorCode = $code;
    $errorTitle = $title !== null ? (string)$title : $defaults['title'];
    $errorMessage = $message !== null ? (string)$message : $defaults['message'];
    $errorBackUrl = function_exists('public_path') ? public_path('index.php') : '../index.php';

    if (!headers_sent()) {
        http_response_code($errorCode);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $template = BASEDIR . 'errors/template.php';
    if (is_file($template)) {
        require $template;
        return;
    }

    echo '<!doctype html><html lang="lt"><head><meta charset="utf-8"><title>' .
        htmlspecialchars((string)$errorCode, ENT_QUOTES, 'UTF-8') . ' ' .
        htmlspecialchars($errorTitle, ENT_QUOTES, 'UTF-8') .
        '</title></head><body><h1>' .
        htmlspecialchars((string)$errorCode, ENT_QUOTES, 'UTF-8') . ' ' .
        htmlspecialchars($errorTitle, ENT_QUOTES, 'UTF-8') .
        '</h1><p>' .
        htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') .
        '</p></body></html>';
}

function abort_http($code, $message = null, $title = null)
{
    render_http_error_page($code, $message, $title);
    exit;
}

function register_http_error_handlers()
{
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;

    set_exception_handler(function (Throwable $e) {
        error_log('Unhandled exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Unhandled exception: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }

        abort_http(500);
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if (!$error) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int)$error['type'], $fatalTypes, true)) {
            return;
        }

        error_log('Fatal shutdown error: ' . ($error['message'] ?? '') . ' in ' . ($error['file'] ?? '') . ':' . ($error['line'] ?? ''));

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            render_http_error_page(500);
        }
    });
}

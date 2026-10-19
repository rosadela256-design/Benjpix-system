<?php
declare(strict_types=1);

/** Escape for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Returns a CSRF token for the current session, creating one if needed. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Renders the hidden CSRF input for a form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Dies with 400 if the submitted token does not match the session token. */
function csrf_check(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    $real = $_SESSION['csrf_token'] ?? '';
    if ($sent === '' || $real === '' || !hash_equals($real, $sent)) {
        http_response_code(400);
        exit('Your session expired. Please go back and submit the form again.');
    }
}

/**
 * Loads config/config.xml safely (no external entities, no network access).
 * Falls back to safe defaults if the file is missing or malformed.
 */
function load_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $defaults = [
        'shop_name'    => 'benjpix',
        'shop_tagline' => 'photo and video services',
        'shop_address' => '',
        'services'     => [
            ['name' => 'Photo shoot',          'mode' => 'booking', 'note' => ''],
            ['name' => 'Event videography',    'mode' => 'booking', 'note' => ''],
            ['name' => 'Printing',             'mode' => 'request', 'note' => ''],
            ['name' => 'Photo/video editing',  'mode' => 'request', 'note' => ''],
        ],
        'allowed_ext'  => ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'mp4', 'mov'],
        'max_size_mb'  => 50,
        'statuses'     => ['Pending', 'Processing', 'Ready', 'Completed'],
        'gallery'      => [],
    ];

    $path = __DIR__ . '/../config/config.xml';
    if (!file_exists($path)) {
        return $cfg = $defaults;
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    if ($xml === false) {
        return $cfg = $defaults;
    }

    try {
        $services = [];
        foreach ($xml->services->service as $s) {
            $mode = (string)$s->mode === 'booking' ? 'booking' : 'request';
            $services[] = [
                'name' => (string)$s->name,
                'mode' => $mode,
                'note' => (string)$s->note,
            ];
        }
        $statuses = [];
        foreach ($xml->statuses->status as $s) {
            $statuses[] = (string)$s;
        }
        $ext = array_filter(array_map('trim', explode(',', (string)$xml->uploads->allowed_ext)));

        $gallery = [];
        if (isset($xml->gallery->item)) {
            foreach ($xml->gallery->item as $item) {
                $file = (string)$item->file;
                if ($file !== '' && is_file(__DIR__ . '/../assets/img/gallery/' . $file)) {
                    $gallery[] = ['file' => $file, 'caption' => (string)$item->caption];
                }
            }
        }

        $cfg = [
            'shop_name'    => (string)($xml->shop->name ?: $defaults['shop_name']),
            'shop_tagline' => (string)($xml->shop->tagline ?: $defaults['shop_tagline']),
            'shop_address' => (string)$xml->shop->address,
            'services'     => $services ?: $defaults['services'],
            'allowed_ext'  => $ext ?: $defaults['allowed_ext'],
            'max_size_mb'  => (int)($xml->uploads->max_size_mb ?: $defaults['max_size_mb']),
            'statuses'     => $statuses ?: $defaults['statuses'],
            'gallery'      => $gallery,
        ];
    } catch (Throwable $e) {
        $cfg = $defaults;
    }

    return $cfg;
}

/** Generates a request reference like BJP-7F3K2Q. */
function generate_reference(PDO $pdo): string
{
    do {
        $ref = 'BJP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $stmt = $pdo->prepare('SELECT id FROM requests WHERE reference = ?');
        $stmt->execute([$ref]);
    } while ($stmt->fetch());

    return $ref;
}

/**
 * Validates an uploaded file against the XML-configured allow-list and size limit.
 * Returns ['ok' => bool, 'error' => string|null, 'ext' => string|null].
 */
function validate_upload(array $file, array $config): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'error' => null, 'ext' => null]; // file is optional
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'There was a problem uploading the file.', 'ext' => null];
    }

    $maxBytes = $config['max_size_mb'] * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'File is larger than ' . $config['max_size_mb'] . 'MB.', 'ext' => null];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $config['allowed_ext'], true)) {
        return ['ok' => false, 'error' => 'That file type is not accepted.', 'ext' => null];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string)$finfo->file($file['tmp_name']);
    $allowedMime = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    if (!isset($allowedMime[$ext]) || !str_starts_with($mime, explode('/', $allowedMime[$ext])[0])) {
        return ['ok' => false, 'error' => 'The file content does not match its extension.', 'ext' => null];
    }

    return ['ok' => true, 'error' => null, 'ext' => $ext];
}

/** Moves a validated upload into storage/uploads/ under a random name and records it. */
function store_upload(PDO $pdo, int $requestId, array $file, string $ext): void
{
    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/' . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        error_log('Failed to move uploaded file for request ' . $requestId);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO request_files (request_id, original_name, stored_name, size_bytes, mime_type, uploaded_at)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $stmt->execute([
        $requestId,
        $file['name'],
        $storedName,
        $file['size'],
        (string)$finfo->file($dest),
        date('Y-m-d H:i:s'),
    ]);
}

/** Finds a configured service by name; returns null if not found. */
function find_service(array $services, string $name): ?array
{
    foreach ($services as $s) {
        if ($s['name'] === $name) {
            return $s;
        }
    }
    return null;
}

/** Status badge CSS class helper for views. */
function status_class(string $status): string
{
    return 'badge ' . preg_replace('/[^A-Za-z]/', '', $status);
}

<?php
header('Access-Control-Allow-Origin: *');

$url = trim((string) ($_GET['url'] ?? ''));
$parts = parse_url($url);
if (
    $url === '' ||
    !in_array($parts['scheme'] ?? '', ['http', 'https'], true) ||
    $parts['host'] === '' ||
    preg_match('/(^|\.)(localhost|local)$/i', $parts['host'])
) {
    http_response_code(400);
    exit;
}

$cacheDirectory = __DIR__ . '/../../../../uploads/menu_images';
if (!is_dir($cacheDirectory)) {
    mkdir($cacheDirectory, 0775, true);
}
$cacheFile = $cacheDirectory . '/' . sha1($url) . '.img';

if (is_file($cacheFile) && filesize($cacheFile) > 0) {
    $content = file_get_contents($cacheFile);
} else {
    $content = null;
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Kantin-Sekolah/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        $content = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);
        if ($status < 200 || $status >= 300) {
            $content = null;
        }
    }

    if ($content === null) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'header' => "User-Agent: Kantin-Sekolah/1.0\r\n",
                'follow_location' => 1,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $content = @file_get_contents($url, false, $context);
    }

    if ($content === null) {
        http_response_code(502);
        exit;
    }

    $mime = isset($contentType) ? strtolower(trim(explode(';', $contentType)[0])) : '';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
        http_response_code(415);
        exit;
    }
    file_put_contents($cacheFile, $content);
}

$extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
$type = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
         'gif' => 'image/gif', 'webp' => 'image/webp'][$extension] ?? 'image/jpeg';
header('Content-Type: ' . $type);
header('Cache-Control: public, max-age=86400');
echo $content;

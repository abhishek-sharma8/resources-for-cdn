<?php
// index.php

// Define the environment variable name
const PROXY_URL_ENV_VAR = 'CAPROVER_APP_PUBLIC_URL';

// Get the public URL of the CapRover app from the environment variable.
// IMPORTANT: This variable MUST be set in your CapRover app's environment config.
$caproverAppPublicUrl = getenv(PROXY_URL_ENV_VAR);

if (empty($caproverAppPublicUrl)) {
    // Log an error and exit if the environment variable is not set.
    // In a real application, you might want more sophisticated logging.
    error_log("Error: Environment variable " . PROXY_URL_ENV_VAR . " is not set. Cannot determine PROXY_URL_PREFIX.");
    http_response_code(500); // Internal Server Error
    header('Content-Type: text/plain');
    echo 'Server configuration error: Proxy URL prefix is not defined. Please contact support.';
    exit;
}

// Construct the PROXY_URL_PREFIX dynamically
// Ensure it ends with /?url= as expected by your parsing logic
const PROXY_URL_PREFIX_SUFFIX = '/?url=';
$proxyUrlPrefix = rtrim($caproverAppPublicUrl, '/') . PROXY_URL_PREFIX_SUFFIX; // Remove trailing slash if present, then add suffix

// Define the MIME type for an M3U8 playlist.
const PLAYLIST_MIME_TYPE = 'application/vnd.apple.mpegurl';

// Set CORS headers.
// These can also be handled by Nginx in CapRover if you prefer.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

// Handle OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get the original playlist URL from the 'playlist' query parameter.
$playlistUrl = $_GET['playlist'] ?? null;

// Validate the playlist URL.
if (empty($playlistUrl) || !filter_var($playlistUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Invalid or missing "playlist" query parameter. Please provide a full URL like `/?playlist=https://original-domain.com/path/to/playlist.m3u8`';
    exit;
}

try {
    // Fetch the original M3U8 playlist content.
    $playlistText = @file_get_contents($playlistUrl);

    if ($playlistText === false) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo "Failed to fetch playlist from: " . htmlspecialchars($playlistUrl);
        exit;
    }

    if (!str_starts_with($playlistText, '#EXTM3U')) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo 'Not a valid M3U8 playlist format.';
        exit;
    }

    $lines = explode("\n", $playlistText);

    $modifiedLines = array_map(function($line) use ($proxyUrlPrefix) { // Pass $proxyUrlPrefix into the closure
        if (str_starts_with($line, $proxyUrlPrefix)) {
            $originalEncodedUrl = substr($line, strlen($proxyUrlPrefix));
            return urldecode($originalEncodedUrl);
        }
        return $line;
    }, $lines);

    $modifiedPlaylistText = implode("\n", $modifiedLines);

    header('Content-Type: ' . PLAYLIST_MIME_TYPE);
    echo $modifiedPlaylistText;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    error_log("Playlist proxy error: " . $e->getMessage());
    echo 'Playlist proxy error: An unexpected error occurred.';
    exit;
}

?>

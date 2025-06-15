<?php
// index.php

// Define the environment variable name for the proxy URL to remove
const PROXY_TO_REMOVE_ENV_VAR = 'PROXY_TO_REMOVE_PREFIX';

// Get the proxy URL prefix to remove from the environment variable.
$proxyToRemovePrefix = getenv(PROXY_TO_REMOVE_ENV_VAR);

if (empty($proxyToRemovePrefix)) {
    error_log("Error: Environment variable " . PROXY_TO_REMOVE_ENV_VAR . " is not set. Cannot un-proxy M3U8.");
    http_response_code(500); // Internal Server Error
    header('Content-Type: text/plain');
    echo 'Server configuration error: Proxy prefix to remove is not defined. Please contact support.';
    exit;
}

// Define the MIME type for an M3U8 playlist.
const PLAYLIST_MIME_TYPE = 'application/vnd.apple.mpegurl';

// Set CORS headers to allow requests from any origin.
header('Access-Control-Allow-Origin: *'); // Allowing all origins for simplicity
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

// Handle OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// Get the original playlist URL from the 'playlist' query parameter.
// This will be the URL of the M3U8 playlist itself, which might be internal.
// Example: http://srv-captain--abcd:7860/watch/master.m3u8
$playlistUrl = $_GET['playlist'] ?? null;

// Validate the playlist URL.
if (empty($playlistUrl) || !filter_var($playlistUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400); // Bad Request
    header('Content-Type: text/plain');
    echo 'Invalid or missing "playlist" query parameter. Please provide a full URL like `/?playlist=http://internal-source.com/path/to/playlist.m3u8`';
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

    // Split the playlist into individual lines.
    $lines = explode("\n", $playlistText);

    // Process each line to remove the proxy prefix and URL-decode.
    $modifiedLines = array_map(function($line) use ($proxyToRemovePrefix) {
        // Check if the line starts with the specified proxy URL prefix.
        if (str_starts_with($line, $proxyToRemovePrefix)) {
            // Extract the part of the URL after the proxy prefix.
            $originalEncodedUrl = substr($line, strlen($proxyToRemovePrefix));
            // URL-decode the extracted part.
            return urldecode($originalEncodedUrl);
        }
        return $line; // Return unchanged if not a URL with the specified proxy prefix.
    }, $lines);

    // Join the modified lines back into a single playlist string.
    $modifiedPlaylistText = implode("\n", $modifiedLines);

    // Set the correct MIME type for M3U8 playlist.
    header('Content-Type: ' . PLAYLIST_MIME_TYPE);

    // Output the modified playlist.
    echo $modifiedPlaylistText;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    error_log("Playlist proxy error: " . $e->getMessage());
    echo 'Playlist proxy error: An unexpected error occurred.';
    exit;
}

?>

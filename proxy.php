<?php

<?php
const PROXY_URL_PREFIX = 'CAPROVER_APP_PUBLIC_URL';
$caproverAppPublicUrl = getenv(PROXY_URL_PREFIX);

// Define the MIME type for an M3U8 playlist.
const PLAYLIST_MIME_TYPE = 'application/vnd.apple.mpegurl';

// Set CORS header to allow requests from any origin.
header('Access-Control-Allow-Origin: *');

// Get the original playlist URL from the 'playlist' query parameter.
$playlistUrl = $_GET['playlist'] ?? null;

// Validate the playlist URL.
if (empty($playlistUrl) || !filter_var($playlistUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400); // Bad Request
    header('Content-Type: text/plain');
    echo 'Invalid or missing "playlist" query parameter. Please provide a full URL like `/?playlist=https://original-domain.com/path/to/playlist.m3u8`';
    exit;
}

try {
    $playlistText = @file_get_contents($playlistUrl);

    // Check if fetching the playlist was successful.
    if ($playlistText === false) {
        http_response_code(500); // Internal Server Error
        header('Content-Type: text/plain');
        echo "Failed to fetch playlist from: " . htmlspecialchars($playlistUrl);
        exit;
    }

    // Basic validation to ensure it's an M3U8 playlist.
    if (!str_starts_with($playlistText, '#EXTM3U')) {
        http_response_code(400); // Bad Request
        header('Content-Type: text/plain');
        echo 'Not a valid M3U8 playlist format.';
        exit;
    }

    // Split the playlist into individual lines.
    $lines = explode("\n", $playlistText);

    // Process each line to remove the proxy prefix from chunk URLs.
    $modifiedLines = array_map(function($line) {
        // Check if the line starts with the specified proxy URL prefix.
        if (str_starts_with($line, PROXY_URL_PREFIX)) {
            // Extract the part of the URL after the proxy prefix.
            $originalEncodedUrl = substr($line, strlen(PROXY_URL_PREFIX));
            // Attempt to URL-decode the extracted part, as it might be encoded.
            // urldecode() is safe here as it will not double-decode already decoded parts.
            return urldecode($originalEncodedUrl);
        }
        return $line; // Return unchanged if not a chunk URL with the prefix.
    }, $lines);

    // Join the modified lines back into a single playlist string.
    $modifiedPlaylistText = implode("\n", $modifiedLines);

    // Set the correct MIME type for M3U8 playlist.
    header('Content-Type: ' . PLAYLIST_MIME_TYPE);

    // Output the modified playlist.
    echo $modifiedPlaylistText;

} catch (Exception $e) {
    // Catch any unexpected errors during processing.
    http_response_code(500); // Internal Server Error
    header('Content-Type: text/plain');
    error_log("Playlist proxy error: " . $e->getMessage()); // Log the error to your server logs
    echo 'Playlist proxy error: An unexpected error occurred.';
    exit;
}

?>

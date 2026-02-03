<?php
// ranking.php - Secure JSON-based ranking API
// Note: On Vercel Serverless Functions, the filesystem is read-only.
// This means new scores cannot be permanently saved to ranking.json.
// To make this persistent, you would need to use Vercel KV, a database, or an external storage service.

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Helper to send JSON response with proper HTTP status
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Path to ranking data
$jsonFile = __DIR__ . '/../quiz/ranking.json';

// Initialize default ranking if file doesn't exist
$ranking = [];
if (file_exists($jsonFile)) {
    $content = file_get_contents($jsonFile);
    if ($content) {
        $ranking = json_decode($content, true) ?: [];
    }
} else {
    // Default dummy data for display
    $ranking = [
        ['name' => 'CloudMaster', 'score' => 10, 'date' => date('Y-m-d H:i')],
        ['name' => 'DevOpsPro', 'score' => 9, 'date' => date('Y-m-d H:i')],
        ['name' => 'SRE_Hero', 'score' => 8, 'date' => date('Y-m-d H:i')]
    ];
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendResponse(['success' => true], 200);
}

// Handle POST request (Submit Score)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!is_array($input) || !isset($input['name']) || !isset($input['score'])) {
        sendResponse(['success' => false, 'message' => 'Invalid input'], 400);
    }
    
    // Sanitize and validate name
    $name = trim($input['name']);
    if (empty($name) || strlen($name) > 50) {
        sendResponse(['success' => false, 'message' => 'Invalid name'], 400);
    }
    
    // Validate score
    $score = filter_var($input['score'], FILTER_VALIDATE_INT, [
        'options' => [
            'min_range' => 0,
            'max_range' => 1000
        ]
    ]);
    
    if ($score === false) {
        sendResponse(['success' => false, 'message' => 'Invalid score'], 400);
    }
    
    // Create new entry
    $newEntry = [
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'score' => $score,
        'date' => date('Y-m-d H:i')
    ];
    
    // Add new score
    $ranking[] = $newEntry;
    
    // Sort by score (descending)
    usort($ranking, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // Keep top 20
    $ranking = array_slice($ranking, 0, 20);
    
    // Try to save (Will fail or be ephemeral on Vercel)
    $saveSuccess = false;
    if (is_writable($jsonFile)) {
        $saveSuccess = @file_put_contents($jsonFile, json_encode($ranking, JSON_PRETTY_PRINT));
    }
    
    sendResponse([
        'success' => true,
        'message' => $saveSuccess ? 'Score submitted successfully' : 'Score submitted (Note: Persistence may not work on Vercel free tier without KV)',
        'ranking' => $ranking,
        'saved' => $saveSuccess
    ]);
} 
// Handle GET request (Get Ranking)
else {
    sendResponse([
        'success' => true,
        'ranking' => $ranking
    ]);
}
?>

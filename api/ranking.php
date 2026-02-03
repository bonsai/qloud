<?php
// ranking.php - Simple JSON-based ranking API
// Note: On Vercel Serverless Functions, the filesystem is read-only.
// This means new scores cannot be permanently saved to ranking.json.
// To make this persistent, you would need to use Vercel KV, a database, or an external storage service.

header('Content-Type: application/json');

// Helper to send JSON response
function sendResponse($data) {
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

// Handle POST request (Submit Score)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['name']) && isset($input['score'])) {
        $newEntry = [
            'name' => htmlspecialchars($input['name']),
            'score' => (int)$input['score'],
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
        // We wrap in try-catch or just suppress errors for Vercel
        @file_put_contents($jsonFile, json_encode($ranking, JSON_PRETTY_PRINT));
        
        sendResponse([
            'success' => true,
            'message' => 'Score submitted (Note: Persistence may not work on Vercel free tier without KV)',
            'ranking' => $ranking
        ]);
    } else {
        sendResponse(['success' => false, 'message' => 'Invalid input']);
    }
} 
// Handle GET request (Get Ranking)
else {
    sendResponse([
        'success' => true,
        'ranking' => $ranking
    ]);
}
?>

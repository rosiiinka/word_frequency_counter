<?php

require_once __DIR__ . '/Word.php';

$parts = explode("/", $_SERVER['REQUEST_URI']);
$method = $_SERVER['REQUEST_METHOD'];

$wordStore = new Word();

header('Content-Type: application/json; charset=utf-8');

try {
    if ($method === 'POST' && $parts[1] == 'text' && empty($parts[2])) {
        if (!isset($_POST['text']) || !is_string($_POST['text']) || trim($_POST['text']) === '') {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "No text provided in request body."]);
            exit;
        }

        $text = $_POST['text'];

        try {
            $wordStore->storeWords($text);
            echo json_encode(["success" => true, "message" => "Text processed successfully."]);
            exit;
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    if ($method === 'GET' && $parts[1] == 'words' && empty($parts[2])) {
        $allCounts = $wordStore->getAllCounts();
        echo json_encode($allCounts, JSON_PRETTY_PRINT);
        exit;
    }

    if ($method === 'GET' && $parts[1] === 'word' && !empty($parts[2])) {
        $word = $parts[2];

        if(is_string($word)){
            // Return count for a single word
            $count = $wordStore->getWordCount($word);
            echo json_encode($count);
            exit;
        }
    }

    // If none of the above matched, return 404
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Not Found"]);
} catch (Throwable $e) {
    // Return a 500 with error details
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

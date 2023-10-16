<?php
header('Content-Type: application/json');

function isValidQuery($query) {
    if (isDictionaryWord($query)) {
        return true;
    }
    $words = explode(' ', $query); 
    foreach ($words as $word) {
        if (isDictionaryWord($word)) {
            return true;
        }
    }
    return false;
}

function isDictionaryWord($word) {
    $encodedWord = str_replace(' ', '%20', $word); // Replace space with %20
    $url = "https://api.dictionaryapi.dev/api/v2/entries/en/" . $encodedWord;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    file_put_contents('log.txt', $url . " - " . $httpcode . " - " . $output . PHP_EOL, FILE_APPEND); // Log the encoded URL
    return $httpcode == 200;
}


$response = ['valid' => false];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["query"])) {
    $query = $_POST["query"];
    if (isValidQuery($query)) {
        $response['valid'] = true;
    }
}

echo json_encode($response);
exit;
?>



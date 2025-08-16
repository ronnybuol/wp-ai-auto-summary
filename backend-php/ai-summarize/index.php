<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Backend-Key');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
require_once __DIR__ . '/config.php';

function read_input_json() {
  $raw = file_get_contents('php://input');
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}
function get_auth_token() {
  if (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization']) && stripos($headers['Authorization'], 'Bearer ') === 0) {
      return substr($headers['Authorization'], 7);
    }
    if (isset($headers['X-Backend-Key'])) { return $headers['X-Backend-Key']; }
  }
  if (!empty($_SERVER['HTTP_AUTHORIZATION']) && stripos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === 0) {
    return substr($_SERVER['HTTP_AUTHORIZATION'], 7);
  }
  if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && stripos($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 'Bearer ') === 0) {
    return substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 7);
  }
  if (!empty($_SERVER['HTTP_X_BACKEND_KEY'])) {
    return $_SERVER['HTTP_X_BACKEND_KEY'];
  }
  if (!empty($_GET['auth'])) { return $_GET['auth']; }
  $body = read_input_json();
  if (!empty($body['secret'])) { return $body['secret']; }
  return '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method Not Allowed']); exit; }

if (defined('OPTIONAL_BACKEND_API_KEY') && OPTIONAL_BACKEND_API_KEY !== '') {
  $token = get_auth_token();
  if ($token !== OPTIONAL_BACKEND_API_KEY) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
}

$input = read_input_json();
$title = isset($input['title']) ? $input['title'] : '';
$text  = isset($input['text']) ? $input['text'] : '';
$lang  = isset($input['language']) ? $input['language'] : 'id_ID';
$len   = isset($input['length']) ? intval($input['length']) : 100;
if (!$text) { http_response_code(400); echo json_encode(['error' => 'Missing text']); exit; }

$prompt = "Ringkas berita berikut dalam bahasa Indonesia, sekitar {$len} kata, gaya newsroom (faktual, padat, tanpa clickbait), " .
          "pertahankan angka/tanggal penting.\n\nJudul: {$title}\n\nTeks:\n{$text}";

if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === '') { http_response_code(500); echo json_encode(['error' => 'Missing GEMINI_API_KEY in config.php']); exit; }

$model = getenv('GEMINI_MODEL') ?: 'gemini-1.5-flash';
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode(GEMINI_API_KEY);

$payload = [ "contents" => [ ["parts" => [["text" => $prompt]]] ] ];
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($response === false || $httpcode >= 400) { http_response_code(502); echo json_encode(['error' => 'Gemini request failed', 'status' => $httpcode, 'detail' => $err]); exit; }

$data = json_decode($response, true);
$summary = '';
if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
  $summary = $data['candidates'][0]['content']['parts'][0]['text'];
} else if (isset($data['candidates'][0]['content']['parts'])) {
  foreach ($data['candidates'][0]['content']['parts'] as $p) { if (isset($p['text'])) { $summary .= $p['text']; } }
}
$summary = trim($summary);
if ($summary === '') { http_response_code(502); echo json_encode(['error' => 'Empty summary from Gemini']); exit; }

echo json_encode(['summary' => $summary, 'model' => $model], JSON_UNESCAPED_UNICODE);

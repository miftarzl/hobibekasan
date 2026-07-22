<?php
// Cegah akses langsung ke file ini
if (!defined('CONFIG_INCLUDED')) {
    define('CONFIG_INCLUDED', true);

    $host = getenv('DB_HOST') ?: "localhost";
    $db_user = getenv('DB_USER') ?: "root";
    $db_pass = getenv('DB_PASS') ?: "";
    $database = getenv('DB_DATABASE') ?: "hobibekasan";

    $conn = new mysqli($host, $db_user, $db_pass, $database);

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    if (!defined('BASE_URL')) {
        $baseUrl = getenv('BASE_URL');
        if (!$baseUrl) {
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                $scheme = 'https';
            } elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
                $scheme = 'https';
            } else {
                $scheme = 'http';
            }
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8087';
            $baseUrl = $scheme . '://' . $host;
        }
        define('BASE_URL', rtrim($baseUrl, '/'));
    }

    if (!defined('SITE_NAME')) {
        define('SITE_NAME', 'hobiBekasan');
    }

    /**
     * Asisten AI (navbar → api/ai_chat.php). Isi manual atau pakai environment.
     * Gemini: https://aistudio.google.com/apikey | OpenAI (opsional): https://platform.openai.com/api-keys
     */
    $gemini_key_manual = 'AIzaSyDwc2H2GRkNOriNrREmq4K0_Ur9AKf3lzs';
    $openai_key_manual = '';
    $gemini_env = getenv('GEMINI_API_KEY');
    $openai_env = getenv('OPENAI_API_KEY');
    define(
        'GEMINI_API_KEY',
        $gemini_key_manual !== '' ? $gemini_key_manual : (($gemini_env !== false && $gemini_env !== '') ? $gemini_env : '')
    );
    define(
        'OPENAI_API_KEY',
        $openai_key_manual !== '' ? $openai_key_manual : (($openai_env !== false && $openai_env !== '') ? $openai_env : '')
    );

    /**
     * Asisten AI via Node.js (nodejs-ai-service). Contoh: http://127.0.0.1:3000
     * Kosong = pakai PHP api/ai_chat.php. Jika diisi, kunci API cukup di .env Node, tidak wajib di PHP.
     */
    define('AI_CHAT_NODE_URL', '');
}

?>
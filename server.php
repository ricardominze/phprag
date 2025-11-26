<?php

use PHPRag\Services\ChatService;
use PHPRag\Services\ElevenLabsService;
use PHPRag\Services\EmbeddingService;
use PHPRag\Services\VectorSearchService;
use PHPRag\Services\WhisperService;
use Swoole\Http\Server;

require_once __DIR__ . '/vendor/autoload.php';
envload(__DIR__ . '/.env');

$server = new Server("0.0.0.0", 9501);

// Evento disparado quando o servidor inicia
$server->on("start", function () {
  echo "[SERVER] Servidor iniciado em http://localhost:9501\n";
});

// Evento disparado a cada requisição
$server->on("request", function ($req, $res) {

  $path   = $req->server['request_uri'] ?? '/';
  $method = $req->server['request_method'] ?? 'GET';

  // Log simples da requisição

  echo "[REQUEST] {$method} {$path}\n";

  // --------- RENDERIZA O TEMPLATE HTML ---------

  if ($path === '/') {

    echo "[INFO] Renderizando template form.html\n";

    $html = file_get_contents(__DIR__ . "/app/form.html");
    $res->header("Content-Type", "text/html; charset=UTF-8");
    $res->end($html);

    echo "[RESPONSE] HTML enviado\n";
    return;
  }

  // --------- RECEBE DADOS DO FORM SENDO POST ---------

  if ($path === '/submit' && $method === 'POST') {


    if (!empty($req->files['audio'])) {

      $file = $req->files['audio'];

      echo "[UPLOAD] Arquivo recebido: {$file['name']} ({$file['size']} bytes)\n";

      $dest = __DIR__ . "/uploads/" . $file['name'];
      if (!file_exists(dirname($dest))) {
        mkdir(dirname($dest), 0777, true);
      }

      move_uploaded_file($file['tmp_name'], $dest);

      $whisper = new WhisperService();
      $pergunta = $whisper->transcribe($dest);

      $embed = new EmbeddingService();
      $search = new VectorSearchService();
      $chat = new ChatService('gemma3:1b');

      $embedInput = $embed->embed($pergunta);
      $context = $search->searchRelevant($embedInput, 5);
      $prompt = $search->buildPrompt($pergunta, $context);

      $resposta = $chat->chat(
        [[
          'role' => 'user',
          'content' => $prompt,
        ]],
        ['temperature' => 0.2]
      );

      $elevenLabs = new ElevenLabsService();
      $tmpOutput = $elevenLabs->gerarAudio($resposta['message']['content']);

      $res->header("Content-Type", "audio/mpeg");
      $res->header("Content-Disposition", 'inline; filename="converted.mp3"');
      $res->end(file_get_contents($tmpOutput));
    } else {

      $embed = new EmbeddingService();
      $search = new VectorSearchService();
      $chat = new ChatService('gemma3:1b');

      $pergunta = $req->post['text'];

      $embedInput = $embed->embed($pergunta);
      $context = $search->searchRelevant($embedInput, 5);
      $prompt = $search->buildPrompt($pergunta, $context);

      /*
      $chat->chatStream(
        [[
          'role' => 'user',
          'content' => $prompt,
        ]],
        function ($token) use ($res) {

          $chunk = $token['message']['content'] ?? '';

          if ($chunk !== '') {
            $res->write($chunk);
          }

          if (!empty($token['done']) && $token['done'] === true) {
            $res->write("\n\n--- STREAM FINALIZADO ---\n");
            $res->end();
          }
        },
        ['temperature' => 0.2]
      );
    }
    */

      $res->header("Content-Type", "text/event-stream");
      $res->header("Cache-Control", "no-cache");
      $res->header("Connection", "keep-alive");

      $chat->chatStream(
        [['role' => 'user', 'content' => $prompt]],
        function ($token) use ($res) {
          $chunk = $token['message']['content'] ?? '';
          if ($chunk !== '') {
            $res->write($chunk);
          }
          if ($token['done']) {
            $res->end();
          }
        }
      );
    }
  }

  echo "[WARN] Rota desconhecida: {$path}\n";

  $res->status(404);
  $res->end("Rota não encontrada.");
});

$server->start();

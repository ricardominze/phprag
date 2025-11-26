<?php

require_once __DIR__ . '/../vendor/autoload.php';

envload(__DIR__ . '/../.env');

use PHPRag\Services\ChatService;
use PHPRag\Services\DataBaseService;
use PHPRag\Services\EmbeddingService;
use PHPRag\Services\VectorIngestService;
use PHPRag\Services\VectorSearchService;
use PHPRag\Services\WhisperService;

########################
# INGESTAO DE DOCUMENTO
########################

/*
$embed = new EmbeddingService();
$ingest = new VectorIngestService();
$filePath = __DIR__ . '/../docs/regras_futebol.pdf';

$embedChunks = $embed->embedPdf($filePath);
$ingest->ingest(\basename($filePath), \basename($filePath), 'Regras do Futebol', $embedChunks);
*/


####################
# BUSCA NO DOCUMENTO
####################

$embed = new EmbeddingService();
$search = new VectorSearchService();
$whisper = new WhisperService();

$pergunta = "O que é posição de impedimento ?";
//$pergunta = "Como funciona o intervalo do jogo ?, resuma";

$embedInput = $embed->embed($pergunta);
$context = $search->searchRelevant($embedInput, 5);
$prompt = $search->buildPrompt($pergunta, $context);

$chat = new ChatService('gemma3:1b');

echo "pensando...\n\n";

$chat->chatStream(
  [[
    'role' => 'user',
    'content' => $prompt,
  ]],
  function ($token) {
    echo $token['message']['content'];
    if ($token['done']) {
      echo "\n\n";
    }
  },
  ['temperature' => 0.2]
);

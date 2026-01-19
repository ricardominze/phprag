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


$embed = new EmbeddingService();
$ingest = new VectorIngestService();
$filePath = __DIR__ . '/../docs/regras_futebol.pdf';

$embedChunks = $embed->embedPdf($filePath);
$ingest->ingest(\basename($filePath), \basename($filePath), 'Regras do Futebol', $embedChunks);

#docker exec -it phprag-swoole php scripts/ingest.php
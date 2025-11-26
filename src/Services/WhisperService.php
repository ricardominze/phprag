<?php

declare(strict_types=1);

namespace PHPRag\Services;

use GuzzleHttp\Client;

class WhisperService
{
  private Client $http;

  public function __construct()
  {
    $this->http = new Client([
      'base_uri' => rtrim(env('WHISPHER_BASE_URL'), '/'),
      'timeout'  => 60,
    ]);
  }

  public function generate(string $prompt): string
  {
    if (! is_file($prompt)) {
      throw new \InvalidArgumentException(
        "O WhisperModel espera que o parâmetro seja um caminho de arquivo de áudio. Recebido: {$prompt}"
      );
    }

    return $this->transcribe($prompt);
  }

  public function transcribe(string $filePath): string
  {
    if (! is_file($filePath)) {
      throw new \InvalidArgumentException("Arquivo não encontrado: {$filePath}");
    }

    $response = $this->http->post('/transcribe', [
      'multipart' => [
        [
          'name'     => 'file',
          'contents' => fopen($filePath, 'r'),
          'filename' => basename($filePath),
        ],
      ],
    ]);

    return trim((string) $response->getBody());
  }
}

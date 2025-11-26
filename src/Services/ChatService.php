<?php

namespace PHPRag\Services;

use GuzzleHttp\Client;

class ChatService
{
  private Client $http;
  private string $model;

  public function __construct(string $model)
  {
    $this->model = $model;
    $this->http = new Client([
      'base_uri' => rtrim(env('OLLAMA_BASE_URL'), '/'),
      'timeout'  => 0,
    ]);
  }

  public function chat(array $messages, array $options = []): array
  {
    $payload = array_merge([
      'model'    => $this->model,
      'messages' => $messages,
      'stream'   => false,
    ], $options);

    $response = $this->http->post('/api/chat', [
      'json' => $payload,
    ]);

    return json_decode((string) $response->getBody(), true);
  }

  public function chatStream(array $messages, callable $onChunk, array $options = []): void
  {
    $payload = array_merge([
      'model'    => $this->model,
      'messages' => $messages,
      'stream'   => true,
    ], $options);

    $response = $this->http->post('/api/chat', [
      'json'   => $payload,
      'stream' => true,
    ]);

    $body = $response->getBody();

    $buffer = "";

    while (!$body->eof()) {

      $chunk = $body->read(1);
      if ($chunk === "") {
        continue;
      }

      $buffer .= $chunk;

      if (str_ends_with($buffer, "\n")) {

        $line = trim($buffer);
        $buffer = "";

        if ($line === "") {
          continue;
        }

        $decoded = json_decode($line, true);

        if ($decoded !== null) {
          $onChunk($decoded);
          \usleep(10000);
        }

        if (php_sapi_name() === 'cli') {
          flush();
        }
      }
    }
  }
}

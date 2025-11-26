<?php

namespace PHPRag\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ElevenLabsService
{
  private string $apiKey;
  private string $voiceId;
  private Client $client;

  public function __construct()
  {
    $this->apiKey = env('ELEVENLABS_API_KEY');
    $this->voiceId = env('ELEVENLABS_VOICE_ID');
    $this->client = new Client(['base_uri' => 'https://api.elevenlabs.io/v1/']);
  }

  public function gerarAudio(string $texto): ?string
  {
    if (empty($this->apiKey)) {
      throw new \RuntimeException('Chave ELEVENLABS_API_KEY não configurada.');
    }

    $url = "text-to-speech/{$this->voiceId}";

    try {
      $response = $this->client->post($url, [
        'headers' => [
          'Accept' => 'audio/mpeg',
          'Content-Type' => 'application/json',
          'xi-api-key' => $this->apiKey,
        ],
        'json' => [
          'text' => $texto,
          'voice_settings' => [
            'stability' => 0.2,
            'similarity_boost' => 0.9,
          ],
        ],
        'timeout' => 60,
      ]);

      if ($response->getStatusCode() === 200) {
        $filename = __DIR__ . '/../../runtime/jarvis_' . time() . '.mp3';
        file_put_contents($filename, $response->getBody());
        return $filename;
      }
    } catch (GuzzleException $e) {

      echo "Erro ElevenLabs: " . $e->getMessage();
    }

    return null;
  }
}

<?php

namespace PHPRag\Services;

use GuzzleHttp\Client;

class EmbeddingService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function embed(string $text): array
    {
        $response = $this->client->post(env('OLLAMA_BASE_URL') . '/api/embeddings', [
            'json' => [
                'prompt' => $text,
                'model' => 'nomic-embed-text'
            ],
            'timeout' => 60,
        ]);

        $json = json_decode((string)$response->getBody(), true);

        return $json['embedding'] ?? [];
    }

    public function embedString(string $text): array
    {

        // Divide o texto em blocos de até 1000 caracteres
        // $chunks = str_split($text, 1000);
        $chunks = $this->chunkSmart($text);

        $chunkEmbeddings = [];
        foreach ($chunks as $index => $chunk) {

            // Garante que sempre será string
            $chunk = (string) $chunk;
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            // Remove caracteres binários e invisíveis
            $chunk = @preg_replace('/[[:^print:]\x00-\x1F\x7F]/u', '', $chunk) ?? '';

            // Garante UTF-8 válido
            if (!mb_check_encoding($chunk, 'UTF-8')) {
                $detected = mb_detect_encoding($chunk, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($detected) {
                    $chunk = mb_convert_encoding($chunk, 'UTF-8', $detected);
                } else {
                    // fallback remove bytes inválidos
                    $chunk = iconv('UTF-8', 'UTF-8//IGNORE', $chunk);
                }
            }

            // Limita tamanho com segurança
            $chunk = mb_substr((string) $chunk, 0, 2000);

            if ($chunk === '') {
                continue;
            }

            try {
                $response = $this->client->post(
                    rtrim(env('OLLAMA_BASE_URL'), '/') . '/api/embeddings',
                    [
                        'json' => [
                            'model' => 'nomic-embed-text',
                            'prompt' => $chunk,
                        ],
                        'timeout' => 60,
                    ]
                );

                $data = json_decode((string) $response->getBody(), true);

                // estrutura esperada: { "embedding": [0.12, -0.05, ...] }
                if (!isset($data['embedding']) || !is_array($data['embedding'])) {
                    continue;
                }

                // converte para formato PostgreSQL [0.12,-0.05,...]
                $embedding = $data['embedding'];
                $embeddingStr = '[' . implode(',', $embedding) . ']';

                // guarda para inserção posterior
                $chunkEmbeddings[] = [
                    'index' => $index,
                    'content' => $chunk,
                    'embedding' => $embeddingStr,
                ];
            } catch (\Throwable $e) {

                continue;
            }
        }

        return $chunkEmbeddings;
    }

    public function embedPdf(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Arquivo PDF não encontrado em: {$filePath}");
        }

        $cmd = sprintf('pdftotext -layout -nopgbrk %s - 2>/dev/null', escapeshellarg($filePath));
        $text = shell_exec($cmd);

        if (!$text || trim($text) === '') {
            throw new \RuntimeException("Falha ao extrair texto do PDF: {$filePath}. Verifique se o pdftotext está instalado e o arquivo é válido.");
        }

        return $this->embedString($text);
    }

    public function chunkSmart(string $text, int $maxLength = 1000, int $overlap = 100): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $chunks = [];
        $current = '';
        $prevChunkEnd = '';

        foreach ($sentences as $sentence) {
            if (strlen($current . ' ' . $sentence) > $maxLength) {

                $chunks[] = trim($current);

                $current = substr($prevChunkEnd, -$overlap) . ' ' . $sentence;
            } else {

                $current .= ' ' . $sentence;
            }

            $prevChunkEnd = $current;
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return $chunks;
    }
}

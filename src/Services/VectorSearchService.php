<?php

namespace PHPRag\Services;

class VectorSearchService
{
  protected DataBaseService $db;
  protected EmbeddingService $embedding;

  public function __construct()
  {
    $this->db = new DataBaseService();
    $this->embedding = new EmbeddingService();
  }

  public function searchRelevant(array $embeddingVector, $limit = 5): string
  {
    $embeddingStr = '[' . implode(', ', $embeddingVector) . ']';

    $raw = $this->db->fetchAll("SELECT * FROM fn_search_embeddings(?::vector, ?, NULL)", [$embeddingStr, $limit]);

    $context = "";
    foreach ($raw as $item) {
      $context .= $item['content'];
    }

    return $context;
  }

  public function buildPrompt(string $question, string $context): string
  {
    return <<<PROMPT
Coloque-se no papel de um assistente especializado e responda somente com base nas informações presentes no documento abaixo.
Se a resposta não estiver clara nos trechos fornecidos, diga explicitamente que o documento não contém dados suficientes.

---------------------------------------------

DOCUMENTO DE REFERÊNCIA (contexto extraído):
$context

---------------------------------------------

Pergunta:
$question

---------------------------------------------

Escreva uma resposta clara, direta e totalmente fundamentada no documento, usando texto corrido, sem listas, sem tópicos e sem criar conteúdo que não esteja nas informações fornecidas. 
Priorize uma redação natural, objetiva e coerente, conectando as partes relevantes do documento de forma fluida. Se houver múltiplos trechos relacionados, integre-os em uma única explicação.
Caso algum detalhe solicitado não esteja presente no documento, informe isso claramente, sem tentar deduzir, completar ou inventar dados.
PROMPT;
  }
}

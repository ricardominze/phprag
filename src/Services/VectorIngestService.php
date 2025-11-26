<?php

namespace PHPRag\Services;

class VectorIngestService
{
    public static function ingest(string $name, string $source, string $description, array $chunkEmbeddings)
    {
        $db = new DataBaseService();

        $db->exec("INSERT INTO documents (title, source, description) VALUES (?, ?, ?)", [$name, $source, $description]);

        $documentId = $db->lastInsertId();

        foreach ($chunkEmbeddings as $chunk) {
            $db->exec("INSERT INTO document_chunks (document_id, chunk_index, content, embedding) VALUES (?, ?, ?, ?)", [
                $documentId,
                $chunk['index'],
                $chunk['content'],
                $chunk['embedding'],
            ]);
        }

    }
}
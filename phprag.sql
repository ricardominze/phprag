-- Habilita extensão vetorial
CREATE EXTENSION IF NOT EXISTS vector;

-- ===========================================
-- Tabela principal: documents
-- ===========================================

CREATE TABLE IF NOT EXISTS documents (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    source TEXT,
    description TEXT,
    user_id INT, -- opcional (para multiusuário)
    created_at TIMESTAMP DEFAULT NOW()
);

-- ===========================================
-- Tabela de trechos (chunks)
-- ===========================================

CREATE TABLE IF NOT EXISTS document_chunks (
    id SERIAL PRIMARY KEY,
    document_id INT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    chunk_index INT NOT NULL,
    content TEXT NOT NULL,
    embedding vector(768),  -- ajuste dimensão conforme o modelo de embedding usado
    created_at TIMESTAMP DEFAULT NOW()
);

-- ===========================================
-- Índices otimizados (busca vetorial)
-- ===========================================

-- Índice por documento (para filtragem rápida)
CREATE INDEX IF NOT EXISTS idx_document_chunks_docid ON document_chunks(document_id);

-- Índice vetorial com HNSW (similaridade rápida)
CREATE INDEX IF NOT EXISTS idx_document_chunks_embedding
ON document_chunks USING hnsw (embedding vector_l2_ops)
WITH (m = 16, ef_construction = 64);

-- ===========================================
-- Função de busca vetorial
-- ===========================================

-- Esta função retorna os trechos mais próximos
-- com distância e nome do documento de origem
CREATE OR REPLACE FUNCTION fn_search_embeddings(
    query_embedding vector(768),
    limit_count INT DEFAULT 5,
    filter_document_id INT DEFAULT NULL
)
RETURNS TABLE (
    document_id INT,
    document_title TEXT,
    chunk_id INT,
    content TEXT,
    distance FLOAT
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT 
        c.document_id,
        d.title AS document_title,
        c.id AS chunk_id,
        c.content,
        (c.embedding <-> query_embedding) AS distance
    FROM document_chunks c
    JOIN documents d ON d.id = c.document_id
    WHERE filter_document_id IS NULL OR c.document_id = filter_document_id
    ORDER BY c.embedding <-> query_embedding
    LIMIT limit_count;
END;
$$;

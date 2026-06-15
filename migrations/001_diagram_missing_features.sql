BEGIN;

ALTER TABLE listas
    ADD COLUMN IF NOT EXISTS privada boolean NOT NULL DEFAULT false;

CREATE TABLE IF NOT EXISTS tags (
    id serial PRIMARY KEY,
    nome varchar(80) NOT NULL UNIQUE,
    tipo varchar(50),
    descricao text,
    criado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS jogo_tags (
    jogo_id integer NOT NULL REFERENCES jogos(id) ON DELETE CASCADE,
    tag_id integer NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (jogo_id, tag_id)
);

CREATE TABLE IF NOT EXISTS reports (
    id serial PRIMARY KEY,
    reporter_id integer NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    tipo_conteudo varchar(30) NOT NULL,
    conteudo_id integer NOT NULL,
    motivo text NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'aberto',
    observacao_admin text,
    moderado_por integer REFERENCES usuarios(id) ON DELETE SET NULL,
    moderado_em timestamp without time zone,
    criado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT reports_tipo_conteudo_check
        CHECK (tipo_conteudo IN ('jogo', 'review', 'comentario', 'lista', 'usuario')),
    CONSTRAINT reports_status_check
        CHECK (status IN ('aberto', 'em_analise', 'resolvido', 'rejeitado'))
);

CREATE INDEX IF NOT EXISTS idx_jogo_tags_tag_id ON jogo_tags(tag_id);
CREATE INDEX IF NOT EXISTS idx_reports_status ON reports(status);
CREATE INDEX IF NOT EXISTS idx_reports_tipo_conteudo ON reports(tipo_conteudo, conteudo_id);

COMMIT;

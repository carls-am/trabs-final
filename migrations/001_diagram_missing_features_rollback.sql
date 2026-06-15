BEGIN;

DO $$
BEGIN
    IF to_regclass('public.reports') IS NOT NULL AND EXISTS (SELECT 1 FROM reports LIMIT 1) THEN
        RAISE EXCEPTION 'Rollback abortado: reports contem dados.';
    END IF;

    IF to_regclass('public.jogo_tags') IS NOT NULL AND EXISTS (SELECT 1 FROM jogo_tags LIMIT 1) THEN
        RAISE EXCEPTION 'Rollback abortado: jogo_tags contem dados.';
    END IF;

    IF to_regclass('public.tags') IS NOT NULL AND EXISTS (SELECT 1 FROM tags LIMIT 1) THEN
        RAISE EXCEPTION 'Rollback abortado: tags contem dados.';
    END IF;

    IF to_regclass('public.listas') IS NOT NULL AND EXISTS (SELECT 1 FROM listas WHERE privada = true LIMIT 1) THEN
        RAISE EXCEPTION 'Rollback abortado: existem listas privadas.';
    END IF;
END $$;

DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS jogo_tags;
DROP TABLE IF EXISTS tags;
ALTER TABLE listas DROP COLUMN IF EXISTS privada;

COMMIT;

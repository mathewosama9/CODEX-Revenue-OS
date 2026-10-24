SELECT 'CREATE DATABASE codex_testing'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'codex_testing')\gexec

\c codex
CREATE EXTENSION IF NOT EXISTS vector;
CREATE EXTENSION IF NOT EXISTS pg_trgm;

\c codex_testing
CREATE EXTENSION IF NOT EXISTS vector;
CREATE EXTENSION IF NOT EXISTS pg_trgm;

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Database-level guard for append-only tables (audit_events, consent_records, ...).
 *
 * UPDATE is never allowed. DELETE is allowed only inside a transaction that set
 * `codex.allow_retention_purge = 'on'` (the retention job), so the application
 * cannot rewrite history even through a bug or a raw query.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION codex_forbid_mutation() RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'DELETE' AND current_setting('codex.allow_retention_purge', true) = 'on' THEN
                    RETURN OLD;
                END IF;

                RAISE EXCEPTION '% is append-only: % is not allowed', TG_TABLE_NAME, TG_OP
                    USING ERRCODE = 'P0001';
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS codex_forbid_mutation()');
    }
};

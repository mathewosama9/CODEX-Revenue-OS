<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M05 Knowledge (E09, 09 §9.12). Product discovery is hybrid: Postgres FTS +
 * trigram on the normalised name, brand and SKU, plus pgvector for meaning.
 * Prices and stock are NOT indexed: the agent reads them live (`get_product`).
 *
 * Embeddings are 1024 dims (Voyage AI default in the Laravel AI SDK).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('url', 500)->nullable();
            $table->bigInteger('regular_price_halalas')->nullable();
            $table->bigInteger('sale_price_halalas')->nullable();
            $table->boolean('is_available')->nullable();
            $table->boolean('unlimited_quantity')->default(false);
            $table->jsonb('variants')->nullable();
            $table->timestampTz('synced_at')->nullable();
        });

        Schema::create('product_search_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('product_external_id', 64);
            $table->text('search_text');
            $table->string('content_hash', 64);
            $table->timestampTz('embedded_at')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'product_external_id']);
        });

        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title', 160);
            $table->text('body');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['tenant_id', 'kind']);
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('content');
            $table->text('search_text');
            $table->timestampTz('embedded_at')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'document_id']);
        });

        foreach (['product_search_index', 'knowledge_chunks'] as $name) {
            DB::statement("ALTER TABLE {$name} ADD COLUMN embedding vector(1024)");
            DB::statement("ALTER TABLE {$name} ADD COLUMN tsv tsvector GENERATED ALWAYS AS (to_tsvector('simple', search_text)) STORED");
            DB::statement("CREATE INDEX {$name}_tsv_idx ON {$name} USING gin (tsv)");
            DB::statement("CREATE INDEX {$name}_trgm_idx ON {$name} USING gin (search_text gin_trgm_ops)");
            DB::statement("CREATE INDEX {$name}_embedding_idx ON {$name} USING hnsw (embedding vector_cosine_ops)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('product_search_index');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description', 'url', 'regular_price_halalas', 'sale_price_halalas', 'is_available', 'unlimited_quantity', 'variants', 'synced_at']);
        });
    }
};

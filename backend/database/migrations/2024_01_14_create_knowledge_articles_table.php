<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_categories')->onDelete('cascade');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['parent_id', 'is_active']);
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->foreignId('category_id')->constrained('knowledge_categories');
            $table->foreignUuid('author_id')->constrained('users');
            $table->enum('status', ['draft', 'published', 'archived', 'under_review'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->json('tags')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('helpful_count')->default(0);
            $table->unsignedBigInteger('not_helpful_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users');
            $table->integer('version')->default(1);
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index('author_id');
            $table->fullText(['title', 'content']);
        });

        Schema::create('knowledge_article_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_articles')->onDelete('cascade');
            $table->foreignId('related_id')->constrained('knowledge_articles')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['article_id', 'related_id']);
        });

        Schema::create('knowledge_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_articles')->onDelete('cascade');
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignUuid('uploaded_by')->constrained('users');
            $table->timestamps();
            
            $table->index('article_id');
        });

        Schema::create('knowledge_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_articles')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users');
            $table->boolean('helpful');
            $table->text('comment')->nullable();
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['article_id', 'user_id']);
            $table->index(['article_id', 'helpful']);
        });

        Schema::create('knowledge_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_articles')->onDelete('cascade');
            $table->longText('content');
            $table->integer('version');
            $table->foreignUuid('author_id')->constrained('users');
            $table->text('change_notes')->nullable();
            $table->timestamps();
            
            $table->index(['article_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_revisions');
        Schema::dropIfExists('knowledge_feedback');
        Schema::dropIfExists('knowledge_attachments');
        Schema::dropIfExists('knowledge_article_relations');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_categories');
    }
};
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;

class KnowledgeArticle extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'category_id',
        'author_id',
        'status',
        'featured',
        'tags',
        'meta_description',
        'meta_keywords',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'average_rating',
        'published_at',
        'reviewed_at',
        'reviewer_id',
        'version',
        'internal_notes'
    ];

    protected $casts = [
        'tags' => 'array',
        'featured' => 'boolean',
        'published_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'average_rating' => 'float',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'version' => 'integer'
    ];

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['category', 'author']);
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => strip_tags($this->content),
            'excerpt' => $this->excerpt,
            'tags' => $this->tags ?? [],
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug ?? str()->slug($this->category->name),
            ] : null,
            'author' => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'email' => $this->author->email,
            ] : null,
            'status' => $this->status,
            'featured' => $this->featured,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'view_count' => $this->view_count ?? 0,
            'helpful_count' => $this->helpful_count ?? 0,
            'not_helpful_count' => $this->not_helpful_count ?? 0,
            'average_rating' => $this->average_rating ?? 0,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'version' => $this->version ?? 1,
            'tenant_id' => $this->tenant_id ?? null,
        ];
    }

    /**
     * Get the value used to index the model.
     *
     * @return mixed
     */
    public function getScoutKey()
    {
        return $this->id;
    }

    /**
     * Get the key name used to index the model.
     *
     * @return mixed
     */
    public function getScoutKeyName()
    {
        return 'id';
    }

    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return 'knowledge_articles';
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        return $this->status === 'published' && !$this->trashed();
    }

    /**
     * Get searchable suggestions for autocomplete.
     *
     * @return array
     */
    public function toSearchableSuggestions(): array
    {
        $suggestions = [];
        
        if ($this->title) {
            $suggestions[] = $this->title;
            
            // Add variations of the title
            $words = explode(' ', $this->title);
            if (count($words) > 2) {
                $suggestions[] = implode(' ', array_slice($words, 0, 3));
            }
        }
        
        if ($this->tags && is_array($this->tags)) {
            $suggestions = array_merge($suggestions, $this->tags);
        }
        
        return array_unique($suggestions);
    }

    /**
     * Scope for published articles
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope for featured articles
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    /**
     * Scope for articles by category
     */
    public function scopeInCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get the author of the article
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the reviewer of the article
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the category of the article
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }

    /**
     * Get the related articles
     */
    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'knowledge_article_relations', 'article_id', 'related_id')
                    ->withTimestamps();
    }

    /**
     * Get the article's attachments
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(KnowledgeAttachment::class, 'article_id');
    }

    /**
     * Get the article's feedback
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(KnowledgeFeedback::class, 'article_id');
    }

    /**
     * Get the article's revision history
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(KnowledgeRevision::class, 'article_id')
                    ->orderBy('version', 'desc');
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Update helpful counts
     */
    public function updateHelpfulCounts(bool $helpful): void
    {
        if ($helpful) {
            $this->increment('helpful_count');
        } else {
            $this->increment('not_helpful_count');
        }
        
        // Recalculate average rating
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total > 0) {
            $this->average_rating = ($this->helpful_count / $total) * 5;
            $this->save();
        }
    }

    /**
     * Create a new revision
     */
    public function createRevision(string $content, int $authorId, string $changeNotes = null): void
    {
        $this->revisions()->create([
            'content' => $content,
            'version' => $this->version + 1,
            'author_id' => $authorId,
            'change_notes' => $changeNotes
        ]);
        
        $this->increment('version');
    }

    /**
     * Generate slug from title
     */
    public function generateSlug(): string
    {
        $slug = str()->slug($this->title);
        $count = static::where('slug', 'like', "{$slug}%")
                      ->where('id', '!=', $this->id)
                      ->count();
        
        return $count > 0 ? "{$slug}-" . ($count + 1) : $slug;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = $article->generateSlug();
            }
            
            if (empty($article->excerpt)) {
                $article->excerpt = str()->limit(strip_tags($article->content), 200);
            }
            
            $article->version = 1;
        });
        
        static::updating(function ($article) {
            if ($article->isDirty('title') && !$article->isDirty('slug')) {
                $article->slug = $article->generateSlug();
            }
        });
    }
}
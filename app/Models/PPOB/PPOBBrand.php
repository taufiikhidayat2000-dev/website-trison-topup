<?php

namespace App\Models\PPOB;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class PPOBBrand extends Model implements HasMedia
{
    use HasSlug, InteractsWithMedia;

    protected $fillable = [
        'p_p_o_b_category_id',
        'name',
        'provider',
        'slug',
        'description',
        'featured',
        'order',
        'settings',
        'status',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom([
                'name',
            ])
            ->saveSlugsTo('slug')
            ->usingSuffixGenerator(
                fn (string $slug, int $iteration) => bin2hex(random_bytes(4))
            );
    }

    public function category()
    {
        return $this->belongsTo(PPOBCategory::class, 'p_p_o_b_category_id');
    }

    public function products()
    {
        return $this->hasMany(PPOBProduct::class, 'p_p_o_b_brand_id');
    }

    /**
     * Match brands by name, slug, description, or category name. Shared by
     * SearchController (autocomplete) and HomeController (full results grid)
     * so a term that surfaces a brand in the dropdown always finds it on the
     * full search page too, instead of each maintaining its own narrower copy.
     */
    #[Scope]
    public function search(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        $query->where(function (Builder $builder) use ($like) {
            $builder->where('name', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like));
        });
    }
}

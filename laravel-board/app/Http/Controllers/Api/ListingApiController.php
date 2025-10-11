<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);

        if (!Schema::hasTable('listings')) {
            return response()->json($this->emptyResponse($filters));
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(50, (int) $request->query('limit', 10)));
        $offset = ($page - 1) * $limit;

        $hasImageTable = Schema::hasTable('listing_images');
        $hasFavoritesTable = Schema::hasTable('favorites');
        $hasCategoryTable = Schema::hasTable('categories');

        $relations = ['user:id,name'];

        if ($hasCategoryTable) {
            $relations['category'] = static fn ($query) => $query->select('id', 'name');
        }

        if ($hasImageTable) {
            $relations['images'] = static fn ($query) => $query
                ->orderByDesc('is_main')
                ->orderBy('id');
        }

        $query = Listing::query()
            ->with($relations)
            ->where('status', 'active');

        if ($filters['search'] !== '') {
            $searchTerm = Str::lower($filters['search']);

            $query->where(static function ($builder) use ($searchTerm) {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        if ($filters['category'] !== null) {
            $query->where('category_id', $filters['category']);
        }

        if ($filters['min_price'] !== null) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if ($filters['max_price'] !== null) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $listings = $query
            ->orderByDesc('created_at')
            ->skip($offset)
            ->take($limit + 1)
            ->get();

        $hasMore = $listings->count() > $limit;

        if ($hasMore) {
            $listings = $listings->take($limit);
        }

        if (!$hasImageTable) {
            $listings->each(static fn (Listing $listing) => $listing->setRelation('images', collect()));
        }

        $favorites = $this->resolveFavorites($listings->pluck('id'), $hasFavoritesTable);

        $data = $listings->map(function (Listing $listing) use ($favorites, $hasImageTable) {
            $mainImagePath = null;
            $mainImageThumb = null;

            if ($hasImageTable) {
                $mainImage = $listing->images
                    ->firstWhere('is_main', true)
                    ?? $listing->images->first();

                if ($mainImage !== null) {
                    $mainImagePath = $this->buildPublicPath($mainImage->image_path);
                    $mainImageThumb = $mainImage->thumbnail_path
                        ? $this->buildPublicPath($mainImage->thumbnail_path)
                        : $mainImagePath;
                }
            }

            $categoryName = $listing->relationLoaded('category')
                ? optional($listing->category)->name
                : null;

            $authorName = $listing->relationLoaded('user')
                ? optional($listing->user)->name
                : null;

            return [
                'id' => (int) $listing->id,
                'title' => (string) $listing->title,
                'description' => (string) $listing->description,
                'price' => (float) $listing->price,
                'created_at' => optional($listing->created_at)->toDateTimeString(),
                'category_name' => $categoryName,
                'author_name' => $authorName,
                'views_count' => (int) ($listing->views_count ?? 0),
                'main_image_path' => $mainImagePath,
                'main_image_thumb' => $mainImageThumb,
                'url' => route('listings.show', $listing),
                'is_favorite' => in_array((int) $listing->id, $favorites, true),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'currentPage' => $page,
                'hasMore' => $hasMore,
                'nextPage' => $hasMore ? $page + 1 : null,
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * @return array{search:string, category:int|null, min_price:float|null, max_price:float|null}
     */
    private function extractFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));

        $categoryInput = trim((string) $request->query('category', ''));
        $category = $categoryInput !== '' && ctype_digit($categoryInput) ? (int) $categoryInput : null;

        $minPriceInput = trim((string) $request->query('min_price', ''));
        $maxPriceInput = trim((string) $request->query('max_price', ''));

        $minPrice = $this->normalizePrice($minPriceInput);
        $maxPrice = $this->normalizePrice($maxPriceInput);

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        return [
            'search' => $search,
            'category' => $category,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ];
    }

    private function normalizePrice(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $value);

        if (!is_numeric($normalized)) {
            return null;
        }

        $price = (float) $normalized;

        return $price < 0 ? 0.0 : $price;
    }

    private function buildPublicPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @param Collection<int, int|string> $ids
     *
     * @return array<int>
     */
    private function resolveFavorites(Collection $ids, bool $hasFavoritesTable): array
    {
        $userId = Auth::id();

        if ($userId === null || !$hasFavoritesTable || $ids->isEmpty()) {
            return [];
        }

        return Favorite::query()
            ->where('user_id', $userId)
            ->whereIn('listing_id', $ids)
            ->pluck('listing_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param array{search:string, category:int|null, min_price:float|null, max_price:float|null} $filters
     *
     * @return array<string, mixed>
     */
    private function emptyResponse(array $filters): array
    {
        return [
            'data' => [],
            'pagination' => [
                'currentPage' => 1,
                'hasMore' => false,
                'nextPage' => null,
            ],
            'filters' => $filters,
        ];
    }
}

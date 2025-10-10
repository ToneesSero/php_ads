<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Http\UploadedFile;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListingController extends Controller
{
    public const MAX_IMAGES = 5;
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $categoryFilter = trim((string) $request->query('category', ''));
        $categoryId = $categoryFilter !== '' && ctype_digit($categoryFilter)
            ? (int) $categoryFilter
            : null;

        $minPriceInput = trim((string) $request->query('min_price', ''));
        $maxPriceInput = trim((string) $request->query('max_price', ''));

        $minPrice = $this->normalizePrice($minPriceInput);
        $maxPrice = $this->normalizePrice($maxPriceInput);

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
            [$minPriceInput, $maxPriceInput] = [
                $this->formatPrice($minPrice),
                $this->formatPrice($maxPrice),
            ];
        }
        $categories = Schema::hasTable('categories')
            ? Category::orderBy('name')->get()
            : collect();

        $filters = [
            'search' => $search,
            'category' => $categoryId,
            'min_price' => $minPriceInput,
            'max_price' => $maxPriceInput,
        ];

        if (!Schema::hasTable('listings')) {
            $listings = $this->emptyPaginator($request);

            return view('listings.index', compact('listings', 'categories', 'filters'));
        }
        $relations = ['category', 'user'];
        $hasImageTable = Schema::hasTable('listing_images');

        if ($hasImageTable) {
            $relations[] = 'images';
        }

        $query = Listing::query()
            ->with($relations)
            ->where('status', 'active');

        if ($search !== '') {
            $searchTerm = Str::lower($search);

            $query->where(function ($builder) use ($searchTerm) {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        $listings = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();
        if (!$hasImageTable) {
            $listings->getCollection()->each(static function (Listing $listing) {
                $listing->setRelation('images', collect());
            });
        }

        return view('listings.index', compact('listings', 'categories', 'filters'));
    }

    public function show(Listing $listing)
    {
        $listing->load(['category', 'user']);
        if (Schema::hasTable('listing_images')) {
            $listing->loadMissing('images');
        } else {
            $listing->setRelation('images', collect());
        }
        $user = Auth::user();
        $isOwner = $user !== null && (int) $user->id === (int) $listing->user_id;

        if ($listing->status !== 'active' && !$isOwner) {
            abort(404);
        }

        $listing->increment('views_count');
        $listing->update(['last_viewed_at' => now()]);
        $listing->refresh();

        return view('listings.show', [
            'listing' => $listing,
            'isOwner' => $isOwner,
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('listings.create', compact('categories'));
    }

    public function store(ListingRequest $request)
    {
        $data = $this->prepareData($request->validated());
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'active';

        $listing = Listing::create($data);

        $this->storeUploadedImages($listing, $request);
        return redirect()
            ->route('listings.show', $listing)
            ->with('status', 'Объявление успешно опубликовано.');
    }

    public function edit(Listing $listing)
    {
        $this->ensureOwner($listing);

        $categories = Category::orderBy('name')->get();

        if (Schema::hasTable('listing_images')) {
            $listing->loadMissing('images');
        } else {
            $listing->setRelation('images', collect());
        }
        return view('listings.edit', compact('listing', 'categories'));
    }

    public function update(ListingRequest $request, Listing $listing)
    {
        $this->ensureOwner($listing);

        $data = $this->prepareData($request->validated());

        $listing->update($data);

        $this->storeUploadedImages($listing, $request);
        return redirect()
            ->route('listings.show', $listing)
            ->with('status', 'Объявление обновлено.');
    }

    public function destroy(Listing $listing)
    {
        $this->ensureOwner($listing);

        $this->deleteListingFiles($listing);
        $listing->delete();

        return redirect()
            ->route('listings.index')
            ->with('status', 'Объявление удалено.');
    }

    private function prepareData(array $data): array
    {
        $data['price'] = round((float) $data['price'], 2);
        $data['category_id'] = $data['category_id'] !== null ? (int) $data['category_id'] : null;

        return $data;
    }

    private function storeUploadedImages(Listing $listing, Request $request): void
    {
        if (!Schema::hasTable('listing_images')) {
            return;
        }

        $files = $request->file('images');

        if ($files === null) {
            return;
        }

        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        $files = array_values(array_filter($files, static fn ($file) => $file instanceof UploadedFile && $file->isValid()));

        if ($files === []) {
            return;
        }

        $existingCount = $listing->images()->count();
        $availableSlots = max(0, self::MAX_IMAGES - $existingCount);

        if ($availableSlots === 0) {
            return;
        }

        $files = array_slice($files, 0, $availableSlots);

        $hasMainImage = $listing->images()->where('is_main', true)->exists();

        foreach ($files as $index => $file) {
            $storedPath = $file->store('listings', 'public');
            $thumbnailPath = $this->generateThumbnail($storedPath);

            $listing->images()->create([
                'image_path' => $storedPath,
                'thumbnail_path' => $thumbnailPath,
                'is_main' => !$hasMainImage && $index === 0,
            ]);

            if (!$hasMainImage && $index === 0) {
                $hasMainImage = true;
            }
        }
    }

    private function generateThumbnail(string $storedPath): string
    {
        $disk = Storage::disk('public');
        $absolutePath = $disk->path($storedPath);

        if (!is_file($absolutePath)) {
            return $storedPath;
        }

        try {
            [$width, $height, $type] = getimagesize($absolutePath);

            if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                return $storedPath;
            }

            $source = $type === IMAGETYPE_JPEG
                ? imagecreatefromjpeg($absolutePath)
                : imagecreatefrompng($absolutePath);

            $thumbnailWidth = 300;
            $thumbnailHeight = 200;

            $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);

            // Заполняем фон белым цветом, чтобы миниатюра выглядела аккуратно
            $background = imagecolorallocate($thumbnail, 255, 255, 255);
            imagefill($thumbnail, 0, 0, $background);

            $scale = min($thumbnailWidth / $width, $thumbnailHeight / $height);
            $scaledWidth = (int) round($width * $scale);
            $scaledHeight = (int) round($height * $scale);
            $offsetX = (int) floor(($thumbnailWidth - $scaledWidth) / 2);
            $offsetY = (int) floor(($thumbnailHeight - $scaledHeight) / 2);

            imagecopyresampled(
                $thumbnail,
                $source,
                $offsetX,
                $offsetY,
                0,
                0,
                $scaledWidth,
                $scaledHeight,
                $width,
                $height
            );

            $extension = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));
            $baseName = pathinfo($storedPath, PATHINFO_FILENAME);
            $directory = trim(dirname($storedPath), '/');
            $thumbnailRelativePath = ($directory !== '' ? $directory . '/' : '') . $baseName . '_thumb.' . $extension;
            $thumbnailAbsolutePath = $disk->path($thumbnailRelativePath);

            if (!is_dir(dirname($thumbnailAbsolutePath))) {
                mkdir(dirname($thumbnailAbsolutePath), 0755, true);
            }

            if ($extension === 'png') {
                imagepng($thumbnail, $thumbnailAbsolutePath, 6);
            } else {
                imagejpeg($thumbnail, $thumbnailAbsolutePath, 85);
            }

            return $thumbnailRelativePath;
        } catch (Throwable $exception) {
            // Если генерация миниатюры завершилась ошибкой, возвращаем оригинальный путь
            return $storedPath;
        } finally {
            if (isset($source) && \is_resource($source)) {
                imagedestroy($source);
            }

            if (isset($thumbnail) && \is_resource($thumbnail)) {
                imagedestroy($thumbnail);
            }
        }
    }

    private function deleteListingFiles(Listing $listing): void
    {
        if (!Schema::hasTable('listing_images')) {
            return;
        }

        $listing->loadMissing('images');

        foreach ($listing->images as $image) {
            $paths = array_filter([
                $image->image_path,
                $image->thumbnail_path,
            ]);

            if ($paths !== []) {
                Storage::disk('public')->delete($paths);
            }
        }
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

    private function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }

    private function ensureOwner(Listing $listing): void
    {
        if ((int) Auth::id() !== (int) $listing->user_id) {
            abort(403, 'Недостаточно прав для выполнения действия.');
        }
    }

    private function emptyPaginator(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            10,
            LengthAwarePaginator::resolveCurrentPage(),
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ListingController extends Controller
{
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

        $query = Listing::query()
            ->with(['category', 'user'])
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
        return view('listings.index', compact('listings', 'categories', 'filters'));
    }

    public function show(Listing $listing)
    {
        $listing->load(['category', 'user']);

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

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', 'Объявление успешно опубликовано.');
    }

    public function edit(Listing $listing)
    {
        $this->ensureOwner($listing);

        $categories = Category::orderBy('name')->get();

        return view('listings.edit', compact('listing', 'categories'));
    }

    public function update(ListingRequest $request, Listing $listing)
    {
        $this->ensureOwner($listing);

        $data = $this->prepareData($request->validated());

        $listing->update($data);

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', 'Объявление обновлено.');
    }

    public function destroy(Listing $listing)
    {
        $this->ensureOwner($listing);

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

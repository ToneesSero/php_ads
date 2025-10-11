@extends('layouts.app')

@section('title', 'Объявления')

@section('content')
    @php
        $filterQuery = [];

        if ($filters['search'] !== '') {
            $filterQuery['search'] = $filters['search'];
        }

        if ($filters['category'] !== null) {
            $filterQuery['category'] = (string) $filters['category'];
        }

        if ($filters['min_price'] !== '') {
            $filterQuery['min_price'] = $filters['min_price'];
        }

        if ($filters['max_price'] !== '') {
            $filterQuery['max_price'] = $filters['max_price'];
        }

        $baseQuery = http_build_query($filterQuery);
        $nextPage = $listings->hasMorePages() ? $listings->currentPage() + 1 : null;
    @endphp

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <h1 class="h3 mb-0">Объявления</h1>

        @auth
            <a class="btn btn-primary" href="{{ route('listings.create') }}">Добавить объявление</a>
        @endauth
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4 col-xl-3">
            <div class="card h-100">
                <div class="card-header">Фильтры</div>
                <div class="card-body">
                    <form method="GET" action="{{ route('listings.index') }}" class="vstack gap-3" data-listings-filter>
                        <div>
                            <label for="search" class="form-label">Поиск</label>
                            <input type="text" name="search" id="search" class="form-control"
                                value="{{ $filters['search'] }}" placeholder="Название или описание">
                        </div>

                        <div>
                            <label for="category" class="form-label">Категория</label>
                            <select class="form-select" name="category" id="category">
                                <option value="">Все категории</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label for="min_price" class="form-label">Цена от</label>
                                <input type="text" name="min_price" id="min_price" class="form-control"
                                    value="{{ $filters['min_price'] }}" placeholder="0">
                            </div>
                            <div class="col-6">
                                <label for="max_price" class="form-label">Цена до</label>
                                <input type="text" name="max_price" id="max_price" class="form-control"
                                    value="{{ $filters['max_price'] }}" placeholder="10000">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Применить</button>
                            <button type="button" class="btn btn-outline-secondary" data-reset-filters>Сбросить</button>

                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-xl-9">
            @if ($listings->isEmpty())
                <div class="alert alert-info">Пока нет объявлений, подходящих под выбранные условия.</div>
            @else
                <div
                    class="row row-cols-1 row-cols-md-2 g-3"
                    data-listings-container
                    data-next-page="{{ $nextPage ? e($nextPage) : '' }}"
                    data-base-query="{{ e($baseQuery) }}"
                >
                    @foreach ($listings as $listing)
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                @php
                                    $mainImage = $listing->images->firstWhere('is_main', true) ?? $listing->images->first();
                                @endphp

                                @if ($mainImage)
                                    <img src="{{ asset('storage/' . ($mainImage->thumbnail_path ?? $mainImage->image_path)) }}"
                                        class="card-img-top" alt="Превью объявления">
                                @endif
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <h2 class="h5">
                                                <a class="text-decoration-none" href="{{ route('listings.show', $listing) }}">
                                                    {{ $listing->title }}
                                                </a>
                                            </h2>
                                            <div class="text-muted small">
                                                {{ $listing->category?->name ?? 'Без категории' }} ·
                                                {{ $listing->created_at?->format('d.m.Y H:i') }}
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary fs-6">
                                                {{ number_format($listing->price, 2, '.', ' ') }} ₽
                                            </span>
                                        </div>
                                    </div>

                                    <p class="text-muted mt-3 mb-4">
                                        {{ \Illuminate\Support\Str::limit($listing->description, 160) }}
                                    </p>

                                    <div class="mt-auto d-flex justify-content-between text-muted small">
                                        <span>Автор: {{ $listing->user?->name ?? 'Неизвестно' }}</span>
                                        <span>Просмотры: {{ $listing->views_count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <div class="text-center" data-loading-indicator hidden>
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <p class="text-muted mt-2 mb-0">Загружаем ещё объявления…</p>
                    </div>

                    <div class="alert alert-danger mt-3" data-loading-error hidden>
                        Не удалось загрузить объявления. Попробуйте позже.
                    </div>

                    <div data-pagination>
                        {{ $listings->withQueryString()->links() }}
                    </div>

                    <div data-scroll-sentinel class="pt-1" aria-hidden="true"></div>
                </div>
            @endif
        </div>
    </div>
@push('scripts')
    <script src="{{ asset('js/listings.js') }}" defer></script>
    <script src="{{ asset('js/infinite-scroll.js') }}" defer></script>
@endpush

@endsection

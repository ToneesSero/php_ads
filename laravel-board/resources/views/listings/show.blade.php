@extends('layouts.app')

@section('title', $listing->title)

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <h1 class="h3 mb-0">{{ $listing->title }}</h1>
        <span class="badge bg-primary fs-5">{{ number_format($listing->price, 2, '.', ' ') }} ₽</span>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
    @php
        $mainImage = $listing->images->firstWhere('is_main', true) ?? $listing->images->first();
    @endphp

    @if ($mainImage)
        <div class="mb-4">
            <img src="{{ asset('storage/' . $mainImage->image_path) }}" alt="Главное фото объявления"
                class="img-fluid rounded shadow-sm w-100" style="max-height: 400px; object-fit: cover;">
        </div>
    @endif
    <div class="text-muted small mb-3">
        <span>Категория: {{ $listing->category?->name ?? 'Без категории' }}</span>
        <span class="mx-2">•</span>
        <span>Автор: {{ $listing->user?->name ?? 'Неизвестно' }}</span>
        <span class="mx-2">•</span>
        <span>Создано: {{ $listing->created_at?->format('d.m.Y H:i') }}</span>
    </div>

    @if ($listing->images->count() > 1)
        <div class="row g-3 mb-4">
            @foreach ($listing->images as $image)
                <div class="col-6 col-md-3">
                    <a href="{{ asset('storage/' . $image->image_path) }}" class="d-block" target="_blank">
                        <img src="{{ asset('storage/' . ($image->thumbnail_path ?? $image->image_path)) }}"
                            alt="Фотография объявления"
                            class="img-fluid rounded border @if ($image->is_main) border-primary border-2 @else border-light @endif">
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            {!! nl2br(e($listing->description)) !!}
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Статистика просмотра</div>
                    <div class="fs-4 fw-semibold">{{ $listing->views_count }}</div>
                    <div class="text-muted small">Просмотров всего</div>
                    <hr>
                    <div class="text-muted small">Последний просмотр</div>
                    <div>{{ $listing->last_viewed_at?->format('d.m.Y H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($isOwner)
        <div class="d-flex flex-column flex-md-row gap-2">
            <a class="btn btn-primary" href="{{ route('listings.edit', $listing) }}">Редактировать</a>
            <form method="POST" action="{{ route('listings.destroy', $listing) }}"
                onsubmit="return confirm('Удалить объявление без возможности восстановления?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Удалить</button>
            </form>
        </div>
    @endif
@endsection

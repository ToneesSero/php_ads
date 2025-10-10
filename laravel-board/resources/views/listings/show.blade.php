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

    <div class="text-muted small mb-3">
        <span>Категория: {{ $listing->category?->name ?? 'Без категории' }}</span>
        <span class="mx-2">•</span>
        <span>Автор: {{ $listing->user?->name ?? 'Неизвестно' }}</span>
        <span class="mx-2">•</span>
        <span>Создано: {{ $listing->created_at?->format('d.m.Y H:i') }}</span>
    </div>

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

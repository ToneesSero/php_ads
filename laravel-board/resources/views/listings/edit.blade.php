@extends('layouts.app')

@section('title', 'Редактировать объявление')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header">Редактирование</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('listings.update', $listing) }}" class="vstack gap-3" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="title" class="form-label">Заголовок</label>
                            <input type="text" name="title" id="title" class="form-control"
                                value="{{ old('title', $listing->title) }}" required>
                            @error('title')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="form-label">Описание</label>
                            <textarea name="description" id="description" rows="6" class="form-control" required>{{ old('description', $listing->description) }}</textarea>
                            @error('description')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Цена</label>
                                <div class="input-group">
                                    <input type="text" name="price" id="price" class="form-control"
                                        value="{{ old('price', number_format($listing->price, 2, '.', '')) }}" required>
                                    <span class="input-group-text">₽</span>
                                </div>
                                @error('price')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Категория</label>
                                <select class="form-select" name="category_id" id="category_id">
                                    <option value="">Не выбрана</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            @selected(old('category_id', $listing->category_id) == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if ($listing->images->isNotEmpty())
                            <div class="border rounded p-3 bg-light-subtle">
                                <div class="fw-semibold mb-2">Текущие фотографии</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($listing->images as $image)
                                        <div class="text-center">
                                            <img src="{{ asset('storage/' . ($image->thumbnail_path ?? $image->image_path)) }}"
                                                alt="Превью объявления" class="img-thumbnail" width="150" height="100">
                                            @if ($image->is_main)
                                                <div class="small text-primary mt-1">Главное фото</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="images" class="form-label">Добавить новые фотографии</label>
                            <input class="form-control" type="file" name="images[]" id="images" multiple
                                accept=".jpg,.jpeg,.png">
                            @php
                                $remainingSlots = max(\App\Http\Controllers\ListingController::MAX_IMAGES - $listing->images->count(), 0);
                            @endphp
                            <div class="form-text">Можно добавить до {{ $remainingSlots }} файлов за раз, общий лимит — {{ \App\Http\Controllers\ListingController::MAX_IMAGES }} фото.</div>
                            @error('images')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            @error('images.*')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between">
                            <a class="btn btn-outline-secondary" href="{{ route('listings.show', $listing) }}">Назад</a>
                            <div class="d-flex flex-column flex-md-row gap-2">
                                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

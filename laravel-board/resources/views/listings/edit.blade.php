@extends('layouts.app')

@section('title', 'Редактировать объявление')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header">Редактирование</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('listings.update', $listing) }}" class="vstack gap-3">
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

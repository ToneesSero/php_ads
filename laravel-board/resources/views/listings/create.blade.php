@extends('layouts.app')

@section('title', 'Добавить объявление')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header">Новое объявление</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('listings.store') }}" class="vstack gap-3" enctype="multipart/form-data">
                        @csrf

                        <div>
                            <label for="title" class="form-label">Заголовок</label>
                            <input type="text" name="title" id="title" class="form-control"
                                value="{{ old('title') }}" required>
                            @error('title')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="form-label">Описание</label>
                            <textarea name="description" id="description" rows="6" class="form-control" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Цена</label>
                                <div class="input-group">
                                    <input type="text" name="price" id="price" class="form-control"
                                        value="{{ old('price') }}" required>
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
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="images" class="form-label">Фотографии</label>
                            <input class="form-control" type="file" name="images[]" id="images" multiple
                                accept=".jpg,.jpeg,.png">
                            <div class="form-text">До {{ \App\Http\Controllers\ListingController::MAX_IMAGES }} файлов в формате JPG или PNG, размером до 5 МБ каждый.</div>
                            @error('images')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            @error('images.*')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                            <a class="btn btn-outline-secondary" href="{{ route('listings.index') }}">Отмена</a>
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

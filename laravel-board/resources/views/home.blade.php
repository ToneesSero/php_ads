@extends('layouts.app')

@section('title', 'Добро пожаловать')

@section('content')
    <div class="py-5 text-center">
        <h1 class="display-5 mb-3">Добро пожаловать в «ПоРукам»</h1>
        <p class="lead">Сервис объявлений в разработке. Перейдите в раздел «Объявления», чтобы начать работу.</p>
        <a class="btn btn-primary" href="{{ url('/listings') }}">Перейти к объявлениям</a>
    </div>
@endsection

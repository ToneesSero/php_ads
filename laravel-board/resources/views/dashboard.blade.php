@extends('layouts.app')

@section('title', __('Dashboard'))

@section('header', __('Dashboard'))

@section('content')
    <div class="alert alert-success" role="alert">
        {{ __('You are logged in!') }}
    </div>
@endsection

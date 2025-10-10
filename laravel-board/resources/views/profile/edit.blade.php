@extends('layouts.app')

@section('title', __('Profile'))

@section('header', __('Profile'))

@section('content')
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="col-12 col-lg-6">
            @include('profile.partials.update-password-form')
        </div>
        <div class="col-12">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection

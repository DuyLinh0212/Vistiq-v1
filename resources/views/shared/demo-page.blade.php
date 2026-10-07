@extends('layouts.app')

@php($hasAuthSpotlight = ($page['shell'] ?? 'user') === 'auth' && in_array($page['slug'] ?? '', ['login', 'register'], true))

@section('content')
    @include('layouts.' . ($page['shell'] ?? 'user'))
@endsection

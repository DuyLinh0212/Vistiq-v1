@extends('layouts.app')

@section('content')
    @include('layouts.' . ($page['shell'] ?? 'admin'))
@endsection

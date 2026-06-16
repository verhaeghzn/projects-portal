@extends('errors.layout')

@section('title', 'Not Found')
@section('code', '404')
@section('heading', 'Page not found')
@section('message', 'The page you’re looking for doesn’t exist, was moved, or is temporarily unavailable.')

@section('actions')
    <a href="{{ route('projects.index') }}" class="btn-primary text-center w-full sm:w-auto">
        Browse projects
    </a>
    <a href="{{ route('home') }}" class="btn-secondary text-center w-full sm:w-auto">
        Go to homepage
    </a>
@endsection

@section('details')
    Requested URL: <span class="font-mono">{{ request()->fullUrl() }}</span>
@endsection



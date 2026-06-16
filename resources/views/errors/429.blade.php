@extends('errors.layout')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('heading', 'Too many requests')
@section('message', 'You have made too many requests in a short period. Please wait a moment and try again.')

@section('actions')
    <a href="{{ request()->fullUrl() }}" class="btn-primary text-center w-full sm:w-auto">
        Try again
    </a>
    <a href="{{ route('home') }}" class="btn-secondary text-center w-full sm:w-auto">
        Go to homepage
    </a>
@endsection

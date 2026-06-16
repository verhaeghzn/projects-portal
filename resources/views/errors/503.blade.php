@extends('errors.layout')

@section('title', 'Maintenance')
@section('code', '503')
@section('heading', 'Down for maintenance')
@section('message', 'We’re performing a quick maintenance update. Please try again in a moment.')

@section('head')
    <meta http-equiv="refresh" content="20">
@endsection

@section('actions')
    <a href="{{ request()->fullUrl() }}" class="btn-primary text-center w-full sm:w-auto">
        Try again
    </a>
    <a href="{{ route('home') }}" class="btn-secondary text-center w-full sm:w-auto">
        Go to homepage
    </a>
@endsection



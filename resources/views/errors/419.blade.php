@extends('errors.layout')

@section('title', 'Page Expired')
@section('code', '419')
@section('heading', 'Page expired')
@section('message', 'Your session has expired. Please refresh the page and try again.')

@section('actions')
    <a href="{{ request()->fullUrl() }}" class="btn-primary text-center w-full sm:w-auto">
        Refresh page
    </a>
    <a href="{{ url()->previous() }}" class="btn-secondary text-center w-full sm:w-auto">
        Go back
    </a>
@endsection

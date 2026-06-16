@extends('errors.layout')

@section('title', 'Unauthorized')
@section('code', '401')
@section('heading', 'Please sign in')
@section('message', 'You need to be authenticated to access this page.')

@section('actions')
    <a href="{{ url('/admin/login') }}" class="btn-primary text-center w-full sm:w-auto">
        Admin login
    </a>
    <a href="{{ route('home') }}" class="btn-secondary text-center w-full sm:w-auto">
        Go to homepage
    </a>
@endsection



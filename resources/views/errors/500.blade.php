@extends('errors.layout')

@section('title', 'Server Error')
@section('code', '500')
@section('heading', 'Something went wrong')
@section('message', 'An unexpected error occurred on our side. Please try again in a few minutes, or contact us if the problem persists.')

@section('actions')
    <a href="{{ route('home') }}" class="btn-primary text-center w-full sm:w-auto">
        Go to homepage
    </a>
    <a href="{{ route('contact') }}" class="btn-secondary text-center w-full sm:w-auto">
        Contact us
    </a>
@endsection



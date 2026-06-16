@extends('errors.layout')

@section('title', 'Forbidden')
@section('code', '403')
@section('heading', 'Access denied')
@section('message', 'You don’t have permission to view this page. If you believe this is a mistake, please contact us.')

@section('actions')
    <a href="{{ route('home') }}" class="btn-primary text-center w-full sm:w-auto">
        Go to homepage
    </a>
    <a href="{{ route('contact') }}" class="btn-secondary text-center w-full sm:w-auto">
        Contact us
    </a>
    <a href="{{ url('/admin/login') }}" class="text-sm text-gray-600 hover:text-gray-800 self-center sm:self-auto sm:ml-auto">
        Admin login
    </a>
@endsection



@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Forbidden'))

@section('content')
    <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                <h1 class="px-4 text-lg dark:text-gray-300 text-gray-700 border-r border-gray-400 tracking-wider">
                    403
                </h1>

                <div class="ml-4 text-lg dark:text-gray-300 text-gray-700 uppercase tracking-wider">
                    {{ $exception->getMessage() ?: 'Forbidden' }}
                </div>
            </div>

            <div class="mt-8 flex gap-4 justify-center">
                <a href="/" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Go Home
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

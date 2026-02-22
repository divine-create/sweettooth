@extends('components.layouts.app.branch-dashboard')

@section('content')
<div class="flex items-center justify-center min-h-screen bg-red-50">
    <div class="text-center max-w-md">
        <div class="inline-block p-4 bg-red-100 rounded-full mb-4">
            <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-0a9 9 0 110-18 9 9 0 010 18z"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-900 mb-2">No Role Assigned</h1>
        <p class="text-gray-600 mb-6">
            Your account doesn't have a role assigned yet. Please contact your administrator to set up your account.
        </p>
        
        <div class="space-y-3">
            <a href="{{ route('settings.profile') }}" class="block w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Update Profile
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="w-full px-4 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition">
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Gói - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Gói
                </h1>
                <p class="text-gray-600 mt-1">
                    Xem và đăng ký gói của bạn
                </p>
            </div>
        </div>
    </div>

    <!-- Available Plans -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                Các gói có sẵn
            </h2>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($availablePlans as $plan)
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow duration-200">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{ $plan->name }}
                        </h3>
                        <div class="text-3xl font-bold text-indigo-600 mb-2">
                            {{ $plan->formatted_price }}
                        </div>
                        <div class="text-sm text-gray-500 mb-6">
                            {{ $plan->formatted_duration }}
                        </div>
                        
                        <form action="{{ route('user.subscription.request', $plan) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                Yêu cầu đăng ký
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

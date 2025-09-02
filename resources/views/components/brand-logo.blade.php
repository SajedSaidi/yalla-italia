@props([
    'size' => 'lg',
    'clickable' => true,
    'url' => 'https://yalaaitalia.com'
])

@php
    $sizeClasses = match($size) {
        'sm' => 'h-6',
        'md' => 'h-8', 
        'lg' => 'h-10',
        'xl' => 'h-12',
        default => 'h-10'
    };
    
    $logoSize = match($size) {
        'sm' => 'w-6 h-6',
        'md' => 'w-8 h-8',
        'lg' => 'w-10 h-10', 
        'xl' => 'w-12 h-12',
        default => 'w-10 h-10'
    };
    
    $textSize = match($size) {
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
        'xl' => 'text-xl',
        default => 'text-lg'
    };
@endphp

@if($clickable)
    <a 
        href="{{ $url }}" 
        target="_blank" 
        rel="noopener noreferrer"
        class="group flex items-center space-x-3 transition-all duration-300 ease-out hover:scale-[1.02] focus:outline-none rounded-lg p-1"
        title="Visit YalaaItalia Portfolio"
    >
        {{-- Logo --}}
        <div class="relative flex-shrink-0">
            <div class="">
                <img 
                    src="{{ asset('images/logo.webp') }}" 
                    width="66"
                    alt="YalaaItalia Logo"
                >
            </div>
            {{-- Subtle accent --}}
            <div class="absolute -top-1 -right-1 w-3 h-3 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full opacity-80 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        {{-- Brand Text --}}
        <div class="flex flex-col min-w-0">
            <span class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300 {{ $textSize }}">
                YalaaItalia
            </span>
        </div>

        {{-- External link indicator --}}
        <div class="flex-shrink-0 opacity-0 group-hover:opacity-60 transition-all duration-300 transform translate-x-0 group-hover:translate-x-1">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </div>
    </a>
@else
    <div class="flex items-center space-x-3">
        {{-- Logo --}}
        <div class="flex-shrink-0">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <img 
                    src="{{ asset('images/logo.webp') }}" 
                    class="{{ $logoSize }}"
                    alt="YalaaItalia Logo"
                >
            </div>
        </div>

        {{-- Brand Text --}}
        <div class="flex flex-col min-w-0">
            <span class="font-semibold text-gray-900 dark:text-gray-100 {{ $textSize }}">
                YalaaItalia
            </span>
        </div>
    </div>
@endif
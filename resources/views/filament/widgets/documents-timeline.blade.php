<x-filament::widget>
    <x-filament::card>
        <div class="space-y-4">
            <h2 class="text-lg font-medium tracking-tight">Recent Documents</h2>
            
            <div class="space-y-4">
                @foreach($this->getDocuments() as $document)
                    <div class="flex items-center space-x-4">
                        {{-- <div class="flex">
                            <x-filament::badge
                                :color="match($document->status) {
                                    'submitted' => 'info',
                                    'accepted' => 'success',
                                    'rejected' => 'danger',
                                    'draft' => 'gray',
                                    'missing' => 'warning',
                                    default => 'gray'
                                }"
                            >
                                {{ ucfirst($document->status) }}
                            </x-filament::badge>
                        </div> --}}
                        
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $document->name }}
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $document->documentType->name }}
                            </p>
                        </div>
                        
                        <div class="flex-shrink-0">
                            <p class="text-sm text-gray-500">
                                {{ $document->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
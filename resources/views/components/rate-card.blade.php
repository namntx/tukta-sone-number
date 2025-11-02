<div {{ $attributes->merge(['class'=>'border rounded-lg']) }}>
    <div class="px-4 py-2 bg-gray-50 border-b rounded-t-lg font-medium text-gray-800">{{ $title }}</div>
    <div class="p-4 space-y-3">
        {{ $slot }}
    </div>
</div>


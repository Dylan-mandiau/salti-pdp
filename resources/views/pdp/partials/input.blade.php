@php
    /** @var string $name */ /** @var string $label */ /** @var ?string $value */
@endphp
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <input type="text" name="{{ $name }}" value="{{ $value }}"
           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-salti-yellow focus:ring-2 focus:ring-salti-yellow/30 outline-none">
</div>

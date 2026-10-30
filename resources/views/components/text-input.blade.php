@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'px-4 py-3 border-gray-200 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 rounded-xl shadow-sm transition-all text-sm']) }}>

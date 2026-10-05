<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-navy-950 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-wider hover:bg-navy-900 focus:bg-navy-900 active:bg-navy-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-all duration-150 shadow-md shadow-navy-950/20']) }}>
    {{ $slot }}
</button>

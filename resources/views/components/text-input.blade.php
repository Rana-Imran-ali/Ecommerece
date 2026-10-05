@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full px-3.5 py-2.5 border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 rounded-xl shadow-sm text-sm text-slate-900 placeholder-slate-400 transition-all duration-150 outline-none bg-white']) }}>

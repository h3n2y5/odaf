<div class="w-full max-w-sm">
    <div class="text-center mb-6">
        <div class="text-xs uppercase tracking-widest text-slate-400">ODAF Runtime</div>
        <h1 class="text-2xl font-semibold text-slate-800">Masuk</h1>
    </div>

    <form wire:submit="login" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4">
            <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
            <input id="username" type="text" wire:model="username" autofocus autocomplete="username"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('username')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('password')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses...</span>
        </button>
    </form>

    <p class="mt-4 text-center text-xs text-slate-400">
        Metadata-driven Application Framework
    </p>
</div>

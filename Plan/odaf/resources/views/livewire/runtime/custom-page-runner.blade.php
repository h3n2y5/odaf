<div>
    @if(class_exists($componentClass))
        @livewire($componentClass, ['appCode' => $appCode, 'pageCode' => $pageCode])
    @else
        <div class="p-8 text-center bg-rose-50 rounded-xl border border-rose-200">
            <h2 class="text-xl font-bold text-rose-700">Custom Page Belum Dikompilasi</h2>
            <p class="mt-2 text-rose-600">
                Kelas <code>{{ $componentClass }}</code> tidak ditemukan. Pastikan Anda sudah mengklik <strong>"Kompilasi Aplikasi"</strong> di halaman Designer setelah membuat Custom Page.
            </p>
        </div>
    @endif
</div>

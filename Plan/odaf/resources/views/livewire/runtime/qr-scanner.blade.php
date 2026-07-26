<div>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 flex flex-col items-center justify-center p-4">
        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600/20 backdrop-blur-sm border border-indigo-500/30 mb-4">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white mb-1">QR Scanner</h1>
            <p class="text-slate-400 text-sm">Arahkan kamera ke QR Code dokumen ODAF</p>
        </div>

        {{-- Scanner Container --}}
        <div class="w-full max-w-md">
            {{-- Camera View --}}
            <div class="relative rounded-2xl overflow-hidden bg-black/50 backdrop-blur border border-white/10 shadow-2xl"
                 x-data="odafQrScanner()"
                 x-init="initScanner()">

                {{-- Video element --}}
                <div id="qr-reader" class="w-full aspect-square"></div>

                {{-- Scanning overlay --}}
                <div class="absolute inset-0 pointer-events-none">
                    {{-- Corner brackets animation --}}
                    <div class="absolute inset-8">
                        <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-indigo-400 rounded-tl-lg animate-pulse"></div>
                        <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-indigo-400 rounded-tr-lg animate-pulse"></div>
                        <div class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-indigo-400 rounded-bl-lg animate-pulse"></div>
                        <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-indigo-400 rounded-br-lg animate-pulse"></div>
                    </div>

                    {{-- Scanning line animation --}}
                    <div class="absolute inset-x-8 h-0.5 bg-gradient-to-r from-transparent via-indigo-400 to-transparent animate-scan-line"></div>
                </div>

                {{-- Status indicator --}}
                <div class="absolute bottom-4 left-0 right-0 text-center">
                    <span class="inline-flex items-center gap-2 bg-black/60 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full">
                        <span class="w-2 h-2 rounded-full animate-pulse"
                              :class="scanning ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                        <span x-text="scanning ? 'Memindai...' : 'Memuat kamera...'"></span>
                    </span>
                </div>
            </div>

            {{-- Success state --}}
            @if ($scanStatus === 'success')
                <div class="mt-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 text-center animate-fade-in">
                    <svg class="w-12 h-12 text-emerald-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-emerald-300 font-medium">QR Code terdeteksi!</p>
                    <p class="text-slate-400 text-xs mt-1">Mengalihkan ke dokumen...</p>
                </div>
            @endif

            {{-- Error state --}}
            @if ($scanStatus === 'error')
                <div class="mt-4 bg-rose-500/10 border border-rose-500/30 rounded-xl p-4 text-center">
                    <svg class="w-8 h-8 text-rose-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <p class="text-rose-300 font-medium">{{ $errorMessage }}</p>
                    <button wire:click="resetScanner"
                            class="mt-3 text-sm text-indigo-400 hover:text-indigo-300 underline underline-offset-4">
                        Coba lagi
                    </button>
                </div>
            @endif

            {{-- Divider --}}
            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-white/10"></div>
                <span class="text-xs text-slate-500 uppercase tracking-wider">atau</span>
                <div class="flex-1 h-px bg-white/10"></div>
            </div>

            {{-- Manual input --}}
            <div class="bg-white/5 backdrop-blur border border-white/10 rounded-xl p-4">
                <label class="block text-xs text-slate-400 mb-2">Masukkan URL QR Code secara manual</label>
                <div class="flex gap-2">
                    <input type="text" wire:model="manualCode"
                           placeholder="https://odaf.local/qr/..."
                           class="flex-1 rounded-lg bg-white/10 border border-white/10 px-3 py-2 text-sm text-white placeholder-slate-500
                                  focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                    <button wire:click="submitManual"
                            class="rounded-lg bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-sm font-medium text-white
                                   transition-colors duration-150 shadow-lg shadow-indigo-500/20">
                        Buka
                    </button>
                </div>
            </div>

            {{-- Back to app --}}
            <div class="mt-6 text-center">
                <a href="{{ url('/') }}" wire:navigate
                   class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke aplikasi
                </a>
            </div>
        </div>
    </div>

    {{-- Scanner animation CSS --}}
    <style>
        @keyframes scan-line {
            0% { top: 2rem; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: calc(100% - 2rem); opacity: 0; }
        }
        .animate-scan-line {
            animation: scan-line 2s ease-in-out infinite;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>

    {{-- html5-qrcode library + scanner initialization --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        function odafQrScanner() {
            return {
                scanning: false,
                scanner: null,

                async initScanner() {
                    try {
                        this.scanner = new Html5Qrcode("qr-reader");

                        await this.scanner.start(
                            { facingMode: "environment" }, // Kamera belakang
                            {
                                fps: 10,
                                qrbox: { width: 250, height: 250 },
                                aspectRatio: 1.0,
                            },
                            (decodedText) => {
                                // QR berhasil di-scan.
                                this.scanning = false;
                                this.scanner.stop().catch(() => {});

                                // Kirim ke Livewire.
                                @this.call('onQrDetected', decodedText);
                            },
                            (errorMessage) => {
                                // Scanning frame tanpa QR (normal, diabaikan).
                            }
                        );

                        this.scanning = true;
                    } catch (err) {
                        console.error('QR Scanner init error:', err);
                        // Fallback: tampilkan pesan jika kamera tidak tersedia.
                        const reader = document.getElementById('qr-reader');
                        if (reader) {
                            reader.innerHTML = `
                                <div class="flex flex-col items-center justify-center h-full min-h-[300px] text-center p-8">
                                    <svg class="w-12 h-12 text-slate-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-slate-400 text-sm font-medium">Kamera tidak tersedia</p>
                                    <p class="text-slate-500 text-xs mt-1">Gunakan input manual di bawah, atau pastikan izin kamera aktif.</p>
                                </div>
                            `;
                        }
                    }
                },

                destroy() {
                    if (this.scanner) {
                        this.scanner.stop().catch(() => {});
                    }
                }
            };
        }
    </script>
</div>

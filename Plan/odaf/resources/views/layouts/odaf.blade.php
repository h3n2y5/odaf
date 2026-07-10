<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ODAF Runtime' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Alpine tidak dimuat terpisah: Livewire 3 sudah membundel Alpine.
         Memuat Alpine dari CDN menyebabkan konflik "multiple Alpine instances". --}}
    <script>
        // Format angka mengikuti locale masing-masing client (browser/OS),
        // bukan locale server. Sehingga angka yang di-copy ke Excel memakai
        // pemisah desimal/ribuan sesuai setting pengguna.
        (function () {
            var probe = new Intl.NumberFormat(undefined).formatToParts(12345.6);
            window.odafNumberParts = {
                group: (probe.find(function (p) { return p.type === 'group'; }) || {}).value || ',',
                decimal: (probe.find(function (p) { return p.type === 'decimal'; }) || {}).value || '.'
            };

            // Format nilai numerik kanonik -> string sesuai locale client.
            window.odafNum = function (value, decimals, grouping) {
                if (value === null || value === undefined || value === '' || isNaN(Number(value))) {
                    return (value === null || value === undefined) ? '' : String(value);
                }
                var o = { useGrouping: !!grouping };
                if (decimals !== null && decimals !== undefined && decimals !== '') {
                    o.minimumFractionDigits = decimals;
                    o.maximumFractionDigits = decimals;
                }
                return new Intl.NumberFormat(undefined, o).format(Number(value));
            };

            // Parse string berformat locale -> angka kanonik ('.' desimal, tanpa ribuan).
            window.odafParseNum = function (str) {
                if (str === null || str === undefined) return '';
                var s = String(str).trim();
                if (s === '') return '';
                var g = window.odafNumberParts.group;
                var d = window.odafNumberParts.decimal;
                s = s.split(g).join('');           // buang pemisah ribuan
                if (d !== '.') s = s.split(d).join('.'); // desimal -> titik
                s = s.replace(/[^0-9.\-]/g, '');
                return s;
            };
        })();
    </script>
    <script>
        // Resize lebar kolom grid: handle drag di header, lebar disimpan di
        // localStorage per tabel, dan diterapkan ulang setelah Livewire re-render.
        (function () {
            function keyOf(t) { return t.getAttribute('data-resize-key'); }
            function load(t) { try { return JSON.parse(localStorage.getItem('gridw:' + keyOf(t)) || '{}'); } catch (e) { return {}; } }
            function save(t, w) { try { localStorage.setItem('gridw:' + keyOf(t), JSON.stringify(w)); } catch (e) {} }
            function headerCells(t) {
                var thead = t.querySelector('thead'); if (!thead) return [];
                var row = thead.querySelector('tr'); return row ? Array.prototype.slice.call(row.children) : [];
            }
            function applyOne(t) {
                if (!keyOf(t)) return;
                var w = load(t), cells = headerCells(t);
                cells.forEach(function (th, i) {
                    if (w[i]) { th.style.width = w[i] + 'px'; th.style.minWidth = w[i] + 'px'; th.style.maxWidth = w[i] + 'px'; }
                });
            }
            window.odafGridApplyAll = function () {
                document.querySelectorAll('table[data-resize-key]').forEach(applyOne);
            };

            var drag = null;
            document.addEventListener('mousedown', function (e) {
                var h = e.target.closest ? e.target.closest('.col-resizer') : null;
                if (!h) return;
                var th = h.closest('th'), table = h.closest('table');
                if (!th || !table) return;
                e.preventDefault(); e.stopPropagation();
                var cells = headerCells(table);
                drag = { table: table, index: cells.indexOf(th), startX: e.clientX, startW: th.offsetWidth, th: th };
                h.classList.add('active');
                document.body.style.cursor = 'col-resize'; document.body.style.userSelect = 'none';
            });
            document.addEventListener('mousemove', function (e) {
                if (!drag) return;
                var nw = Math.max(60, drag.startW + (e.clientX - drag.startX));
                drag.th.style.width = nw + 'px'; drag.th.style.minWidth = nw + 'px'; drag.th.style.maxWidth = nw + 'px';
            });
            document.addEventListener('mouseup', function () {
                if (!drag) return;
                var w = load(drag.table); w[drag.index] = drag.th.offsetWidth; save(drag.table, w);
                document.querySelectorAll('.col-resizer.active').forEach(function (x) { x.classList.remove('active'); });
                document.body.style.cursor = ''; document.body.style.userSelect = ''; drag = null;
            });

            document.addEventListener('livewire:init', function () {
                if (window.Livewire && Livewire.hook) {
                    Livewire.hook('commit', function (payload) {
                        var succeed = payload && payload.succeed;
                        if (typeof succeed === 'function') {
                            succeed(function () { queueMicrotask(function () { window.odafGridApplyAll(); }); });
                        }
                    });
                }
            });
            document.addEventListener('livewire:navigated', function () { window.odafGridApplyAll(); });
            window.addEventListener('load', function () { window.odafGridApplyAll(); });
        })();
    </script>
    <style>
        [x-cloak] { display: none !important; }
        /* Resize lebar kolom grid */
        th.resizable { position: relative; }
        .col-resizer {
            position: absolute; top: 0; right: 0; width: 6px; height: 100%;
            cursor: col-resize; user-select: none; z-index: 6;
        }
        .col-resizer:hover, .col-resizer.active { background: rgba(99, 102, 241, .45); }
        /* Mode bungkus teks: tinggi baris menyesuaikan isi kolom */
        table.grid-wrap td { white-space: normal !important; word-break: break-word; vertical-align: top; }
        /* Splitter horizontal header-detail */
        .hd-splitter { height: 12px; cursor: row-resize; background: #e2e8f0; border-radius: 5px; margin: 6px 0; position: relative; }
        .hd-splitter:hover, .hd-splitter.active { background: #c7d2fe; }
        .hd-splitter::after { content: ''; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 44px; height: 3px; background: #94a3b8; border-radius: 2px; }
        /* Custom scrollbar for sidebar */
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: rgb(15 23 42); /* slate-900 */
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgb(51 65 85); /* slate-700 */
            border-radius: 4px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: rgb(71 85 105); /* slate-600 */
        }
        
        /* Smooth transitions */
        aside {
            transition: width 0.2s ease-out;
        }
        
        /* Prevent text selection during resize */
        .no-select {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
    </style>
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-screen">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>

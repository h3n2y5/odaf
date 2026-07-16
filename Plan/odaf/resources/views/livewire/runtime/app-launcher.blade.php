<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative">
    
    <div class="absolute top-0 right-0 p-4 flex gap-4 text-sm font-medium">
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-rose-600 hover:text-rose-900">Keluar</button>
        </form>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Pilih Aplikasi
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Pilih aplikasi ODAF yang ingin Anda akses.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            @if(empty($apps))
                <div class="text-center text-gray-500 py-4">
                    Belum ada aplikasi yang tersedia (aktif).<br>
                    Silakan hubungi Administrator.
                </div>
            @else
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($apps as $app)
                        <li class="py-4 flex">
                            <a href="{{ route('odaf.home', ['appCode' => $app['code']]) }}" class="group block w-full hover:bg-gray-50 rounded-lg p-2 transition ease-in-out duration-150">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-500 font-bold">
                                            {{ substr($app['name'], 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate group-hover:text-indigo-600">
                                            {{ $app['name'] }}
                                        </p>
                                        <p class="text-sm text-gray-500 truncate">
                                            Kode: {{ $app['code'] }}
                                        </p>
                                    </div>
                                    <div>
                                        <!-- Heroicon chevron-right -->
                                        <svg class="h-5 w-5 text-gray-400 group-hover:text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

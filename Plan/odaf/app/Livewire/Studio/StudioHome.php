<?php

declare(strict_types=1);

namespace App\Livewire\Studio;

use App\Support\StudioAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Odaf\Studio\TableIntrospector;

/**
 * Beranda ODAF Studio Data Manager: katalog seluruh tabel (dikelompokkan per
 * domain) yang dapat dikelola CRUD lewat web.
 */
#[Layout('layouts.odaf')]
final class StudioHome extends Component
{
    public function render(StudioAccess $access, TableIntrospector $introspector)
    {
        $access->ensureAdmin();

        return view('livewire.studio.home', [
            'groups' => $introspector->groupedTables(),
        ]);
    }
}

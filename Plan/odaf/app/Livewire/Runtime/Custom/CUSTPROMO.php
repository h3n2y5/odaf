<?php

namespace App\Livewire\Runtime\Custom;

use Livewire\Component;

class CUSTPROMO extends Component
{
    public float $basePrice = 1000000;
    public string $discountType = 'PERCENT';
    public float $discountValue = 20;
    public float $maxDiscount = 150000;
    public float $minPurchase = 500000;

    public float $calculatedDiscount = 0;
    public float $finalPrice = 1000000;
    public bool $isCapped = false;

    public function mount()
    {
        $this->calculate();
    }

    public function updated($property)
    {
        $this->calculate();
    }

    public function calculate()
    {
        $this->isCapped = false;
        
        if ($this->basePrice < $this->minPurchase) {
            $this->calculatedDiscount = 0;
            $this->finalPrice = $this->basePrice;
            return;
        }

        if ($this->discountType === 'PERCENT') {
            $rawDiscount = $this->basePrice * ($this->discountValue / 100);
            if ($this->maxDiscount > 0 && $rawDiscount > $this->maxDiscount) {
                $this->calculatedDiscount = $this->maxDiscount;
                $this->isCapped = true;
            } else {
                $this->calculatedDiscount = $rawDiscount;
            }
        } else {
            $this->calculatedDiscount = $this->discountValue;
        }

        // Hindari diskon lebih besar dari harga
        if ($this->calculatedDiscount > $this->basePrice) {
            $this->calculatedDiscount = $this->basePrice;
        }

        $this->finalPrice = $this->basePrice - $this->calculatedDiscount;
    }

    public function render()
    {
        return view('livewire.runtime.custom.c-u-s-t_-p-r-o-m-o');
    }
}
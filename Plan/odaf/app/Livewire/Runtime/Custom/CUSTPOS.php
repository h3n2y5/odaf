<?php

namespace App\Livewire\Runtime\Custom;

use Livewire\Component;

class CUSTPOS extends Component
{
    public array $products = [
        ['id' => 1, 'name' => 'Kopi Espresso Premium', 'price' => 25000],
        ['id' => 2, 'name' => 'Caramel Macchiato', 'price' => 35000],
        ['id' => 3, 'name' => 'Matcha Latte', 'price' => 30000],
        ['id' => 4, 'name' => 'Croissant Butter', 'price' => 20000],
        ['id' => 5, 'name' => 'Red Velvet Cake (Slice)', 'price' => 45000],
        ['id' => 6, 'name' => 'Air Mineral Botol', 'price' => 10000],
        ['id' => 7, 'name' => 'Tumbler Eksklusif', 'price' => 150000],
        ['id' => 8, 'name' => 'Biji Kopi Arabica 250g', 'price' => 85000],
    ];

    public string $searchQuery = '';
    public array $cart = [];
    public float $subtotal = 0;
    public float $tax = 0;
    public float $total = 0;
    public bool $paymentSuccess = false;

    public function addToCart(int $productId)
    {
        $this->paymentSuccess = false;
        $product = collect($this->products)->firstWhere('id', $productId);
        if (!$product) return;

        $existingIdx = collect($this->cart)->search(fn($item) => $item['id'] === $productId);
        
        if ($existingIdx !== false) {
            $this->cart[$existingIdx]['qty']++;
        } else {
            $this->cart[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'qty' => 1
            ];
        }
        $this->calculateTotals();
    }

    public function updateQty(int $idx, int $change)
    {
        $this->paymentSuccess = false;
        if (!isset($this->cart[$idx])) return;
        
        $this->cart[$idx]['qty'] += $change;
        if ($this->cart[$idx]['qty'] <= 0) {
            unset($this->cart[$idx]);
            $this->cart = array_values($this->cart); // reindex
        }
        $this->calculateTotals();
    }

    public function removeItem(int $idx)
    {
        unset($this->cart[$idx]);
        $this->cart = array_values($this->cart);
        $this->calculateTotals();
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->calculateTotals();
        $this->paymentSuccess = false;
    }

    public function calculateTotals()
    {
        $this->subtotal = collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
        $this->tax = $this->subtotal * 0.11;
        $this->total = $this->subtotal + $this->tax;
    }

    public function processPayment()
    {
        if (count($this->cart) === 0) return;
        
        // Di sini bisa ditambahkan logika insert ke tabel T_SALES
        $this->paymentSuccess = true;
        
        // Kosongkan keranjang setelah beberapa detik (dibuat simple)
        $this->cart = [];
        $this->calculateTotals();
    }

    public function render()
    {
        return view('livewire.runtime.custom.c-u-s-t_-p-o-s');
    }
}
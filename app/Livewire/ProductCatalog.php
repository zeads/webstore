<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\ProductCollectionData;
use App\Data\ProductData;
use App\Models\Product;
use App\Models\Tag;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCatalog extends Component
{
    use WithPagination;

    // $queryString adalah properti bawaan yang digunakan oleh Livewire (library tambahan populer untuk Laravel) untuk mengikat (bind) variabel komponen ke query string URL secara otomatis.
    public $queryString = [
        'select_collection' => ['except' => []],
        'search' => ['except' => ''],
        'order_by' => ['except' => 'newest'],
    ];

    public array $select_collection = [];
    public string $search = '';
    public string $sort_by = 'newest';

    public function applyFilter()
    {
        // dd($this->select_collection, $this->search, $this->order_by);
        $this->resetPage();
    }

    public function resetFilter()
    {
        $this->select_collection = [];
        $this->search = '';
        $this->sort_by = 'newest';
        $this->resetPage();
    }

    public function render()
    {
        $collection_result = Tag::query()->withType('collection')->withCount('products')->get();
        // $result = Product::paginate(1);

        $query = Product::query();

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if(!empty($this->select_collection)) {
            $query->whereHas('tags', function ($query) {
                $query->whereIn('tags.id', $this->select_collection);
            });
        }

        switch($this->sort_by) {
            case 'latest':
                $query->oldest();
                break;
            case 'price_asc':
                $query->orderBy('price','asc');
                break;
            case 'price_desc':
                $query->orderBy('price','desc');
                break;
            default:
                $query->latest();
                break;
        }

        // $products = ProductData::collect($result);
        $products = ProductData::collect(
            $query->paginate(9)
        );

        $collections = ProductCollectionData::collect($collection_result);

        return view('livewire.product-catalog', compact('products', 'collections'));
    }
}

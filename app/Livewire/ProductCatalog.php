<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\ProductCollectionData;
use App\Data\ProductData;
use App\Models\Product;
use App\Models\Tag;
use Livewire\Component;

class ProductCatalog extends Component
{
    public function render()
    {
        $collection_result = Tag::query()->withType('collection')->withCount('products')->get();
        $result = Product::paginate(1);

        $products = ProductData::collect($result);

        $collections = ProductCollectionData::collect($collection_result);

        return view('livewire.product-catalog', compact('products', 'collections'));
    }
}

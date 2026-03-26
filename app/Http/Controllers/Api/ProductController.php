<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function getCategoryProducts(Category $category)
    {
        $products = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.price',
                'products.old_price',
                'products.count',
                'products.category_id',
            ])
            ->where('products.category_id', $category->id)
            ->where('products.count', '>', 0)
            ->orderBy('products.created_at', 'desc')
            ->limit(4)
            ->get();

        return response()->json([
            'category' => $category->name,
            'products' => $products,
        ]);
    }
    private function query()
    {
        return Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.count',
                'products.old_price',
                'products.price',
                'products.created_at',
                'products.updated_at',
                'categories.name as category',
            ])
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if (!$request->has('search')) {
            return;
        }

        $search = $request->input('search');

        if (is_array($search)) {
            $search = $search['value'] ?? null;
        }

        if (empty($search)) {
            return;
        }

        $query->where(function (Builder $query) use ($search) {
            $query->where('products.name', 'LIKE', "%{$search}%")
                ->orWhere('categories.name', 'LIKE', "%{$search}%");
        });
    }

    public function getDataTable(Request $request)
    {
        return DataTables::eloquent($this->query())
            ->setTransformer(function (Product $product) {
                return [
                    ...$product->toArray(),
                ];
            })
            ->filter(function (Builder $query) use ($request) {
                $this->applyFilters($query, $request);
            })->toJson();
    }

    public function index()
    {
        return Product::all();
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function store(ProductRequest $request)
    {
        Product::create($request->validated());
    }

    public function update(Product $product, ProductRequest $request)
    {
        $product->update($request->validated());
    }

    public function destroy(Product $product)
    {
        $product->delete();
    }
}

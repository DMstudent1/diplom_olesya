<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Log;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function getCategoryAllProducts(Category $category)
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
            ->with('media')
            ->where('products.category_id', $category->id)
            ->where('products.count', '>', 0)
            ->orderBy('products.created_at', 'desc')
            ->get();
        return response()->json([
            'category' => $category->name,
            'products' => $products,
        ]);
    }

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
            ->with('media')
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
            ->with('media')
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
        return Product::with('media')->get();
    }

    public function show(Product $product)
    {
        return response()->json($product->load('media'));
    }

    public function store(ProductRequest $request)
    {
        DB::beginTransaction();
        try {
            $product = Product::create($request->validated());
            $product->addMedia($request->file('img'))->toMediaCollection('images');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }

    public function update(ProductRequest $request, Product $product)
    {
        DB::beginTransaction();
        try {
            $product->update($request->validated());

            // Обновляем изображение только если загружен новый файл
            if ($request->hasFile('img')) {
                // Удаляем все изображения из коллекции 'images'
                $product->clearMediaCollection('images');

                // Добавляем новое изображение
                $product->addMedia($request->file('img'))
                    ->toMediaCollection('images');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'product' => $product->load('media'),
                'message' => 'Товар успешно обновлен'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении товара: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        $product->delete();
    }
}

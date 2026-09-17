<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    private const TYPES = [
        'products' => Product::class,
        'categories' => Category::class,
        'brands' => Brand::class,
        'units' => Unit::class,
        'suppliers' => Supplier::class,
        'warehouses' => Warehouse::class,
        'bank-accounts' => BankAccount::class,
    ];

    public function index(string $type, Request $request): View
    {
        $model = $this->model($type);
        $items = $model::query()
            ->when($type === 'products', fn ($query) => $query->with(['category', 'brand', 'unit']))
            ->when($request->filled('search') && $type !== 'bank-accounts', function ($query) use ($request, $type): void {
                $search = '%'.$request->string('search').'%';
                $query->where(function ($nested) use ($search, $type): void {
                    $nested->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                    if ($type === 'products') {
                        $nested->orWhere('sku', 'like', $search)
                            ->orWhere('barcode', 'like', $search);
                    }
                });
            })
            ->when($request->filled('search') && $type === 'bank-accounts', function ($query) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $query->where('bank_name', 'like', $search)
                    ->orWhere('account_number', 'like', $search)
                    ->orWhere('account_holder', 'like', $search);
            })
            ->latest()->paginate(20)->withQueryString();

        return view('master-data.index', ['type' => $type, 'items' => $items, 'fields' => $this->fields($type), 'categories' => Category::where('is_active', true)->get(), 'brands' => Brand::where('is_active', true)->get(), 'units' => Unit::where('is_active', true)->get()]);
    }

    public function store(string $type, Request $request): RedirectResponse
    {
        $model = $this->model($type);
        $data = $this->validated($type, $request);
        $data = array_merge($this->generatedCode($type), $data);
        $model::create($data);

        return back()->with('status', 'Master data berhasil ditambahkan.');
    }

    public function update(string $type, int $id, Request $request): RedirectResponse
    {
        $model = $this->model($type);
        $item = $model::findOrFail($id);
        $item->update($this->validated($type, $request, $item));

        return back()->with('status', 'Master data berhasil diperbarui.');
    }

    private function model(string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    private function fields(string $type): array
    {
        return match ($type) {
            'products' => ['name', 'barcode', 'minimum_stock', 'specification', 'category_id', 'brand_id', 'unit_id', 'is_active'],
            'suppliers' => ['name', 'contact', 'address', 'bank_account', 'is_active'],
            'warehouses' => ['name', 'address', 'pic', 'is_active'],
            'bank-accounts' => ['bank_name', 'account_number', 'account_holder', 'is_active'],
            default => ['name', 'is_active'],
        };
    }

    private function validated(string $type, Request $request, ?object $item = null): array
    {
        $rules = [];
        foreach ($this->fields($type) as $field) {
            $rules[$field] = match ($field) {
                'name', 'bank_name', 'account_holder' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
                'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'.($item ? ','.$item->id : '')],
                'minimum_stock' => [$item ? 'sometimes' : 'required', 'numeric', 'gte:0'],
                'category_id', 'brand_id', 'unit_id' => ['required_if:'.$field.',*', 'nullable', 'integer'],
                'is_active' => ['sometimes', 'boolean'],
                default => ['nullable', 'string', 'max:2000'],
            };
        }
        if ($type === 'products') {
            $rules['category_id'] = ['nullable', 'exists:categories,id'];
            $rules['brand_id'] = ['nullable', 'exists:brands,id'];
            $rules['unit_id'] = [$item ? 'sometimes' : 'required', 'exists:units,id'];
        }
        if ($type === 'bank-accounts') {
            $rules['account_number'] = ['required', 'string', 'max:100'];
        }

        return Validator::make($request->all(), $rules)->validate();
    }

    private function generatedCode(string $type): array
    {
        if ($type === 'bank-accounts') {
            return [];
        }
        $model = $this->model($type);
        $prefix = ['products' => 'PRD-', 'categories' => 'CAT-', 'brands' => 'BRD-', 'units' => 'UNT-', 'suppliers' => 'SUP-', 'warehouses' => 'WH-'][$type];
        $width = $type === 'products' || $type === 'suppliers' ? 5 : 3;
        $next = ((int) $model::max('id')) + 1;
        $code = $prefix.str_pad((string) $next, $width, '0', STR_PAD_LEFT);

        return $type === 'products' ? ['code' => 'PRD-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT), 'sku' => 'SP-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT)] : ['code' => $code];
    }
}

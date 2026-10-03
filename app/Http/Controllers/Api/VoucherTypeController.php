<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VoucherType;
use Illuminate\Http\Request;

class VoucherTypeController extends Controller
{
    public function index()
    {
        return response()->json(VoucherType::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:voucher_types,code',
            'effect' => 'required|in:stock_in,stock_out,consumable_stock_in',
            'active' => 'boolean',
        ]);

        $voucherType = VoucherType::create($validated);
        return response()->json($voucherType, 201);
    }

    public function update(Request $request, VoucherType $voucherType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:voucher_types,code,' . $voucherType->id,
            'effect' => 'required|in:stock_in,stock_out,consumable_stock_in',
            'active' => 'boolean',
        ]);

        $voucherType->update($validated);
        return response()->json($voucherType);
    }

    public function destroy(VoucherType $voucherType)
    {
        $voucherType->delete();
        return response()->json(['message' => 'Voucher type deleted successfully']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $stats = Order::where('merchant_id', auth()->user()->merchant->id)
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(subtotal) as revenue'),
                DB::raw('SUM(CASE WHEN affiliate_id IS NOT NULL THEN commission_owed ELSE 0 END) as commissions_owed')
            )
            ->first();

        return response()->json([
            'count' => $stats->count,
            'revenue' => $stats->revenue,
            'commissions_owed' => $stats->commissions_owed,
        ]);
    }
}

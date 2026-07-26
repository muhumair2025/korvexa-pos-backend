<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\KhataTransaction;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Order::orderBy('id', 'desc')->get();

        $totalSales = (float) Order::completed()->sum('total_amount');
        $totalOrders = Order::completed()->count();
        $cashSales = (float) Order::completed()->where('payment_method', 'Cash')->sum('total_amount');
        $cardSales = (float) Order::completed()->where('payment_method', 'Card')->sum('total_amount');
        $khataSales = (float) Order::completed()->where('payment_method', 'Khata / Credit')->sum('total_amount');

        return response()->json([
            'success' => true,
            'orders'  => $orders,
            'stats'   => [
                'totalSales'  => $totalSales,
                'totalOrders' => $totalOrders,
                'cashSales'   => $cashSales,
                'cardSales'   => $cardSales,
                'khataSales'  => $khataSales,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subtotal'       => 'required|numeric|min:0',
            'tax_amount'     => 'required|numeric|min:0',
            'total_amount'   => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'items'          => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $orderNo = $request->input('order_no') ?? ('ORD-' . date('Ymd') . '-' . rand(1000, 9999));
            $items = $request->input('items', []);
            $customerId = $request->input('customer_id');
            $customerName = $request->input('customer_name', 'Walk-in Customer');
            $paymentMethod = $request->input('payment_method');
            $totalAmount = (float) $request->input('total_amount');

            // 1. Save Order
            $order = Order::create([
                'uuid'            => $request->input('uuid') ?? (string) Str::uuid(),
                'order_no'        => $orderNo,
                'customer_id'     => $customerId,
                'customer_name'   => $customerName,
                'cashier_name'    => $request->input('cashier_name', auth()->user()->full_name ?? 'Admin'),
                'subtotal'        => (float) $request->input('subtotal'),
                'tax_amount'      => (float) $request->input('tax_amount'),
                'discount_amount' => (float) $request->input('discount_amount', 0),
                'total_amount'    => $totalAmount,
                'payment_method'  => $paymentMethod,
                'status'          => 'COMPLETED',
                'items_json'      => $items,
                'synced_at'       => now(),
            ]);

            // 2. Decrement inventory stock
            foreach ($items as $item) {
                $productId = $item['id'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 1);
                if ($productId) {
                    Product::where('id', $productId)->decrement('stock', $quantity);
                }
            }

            // 3. Update customer stats / Khata balance if customer is attached
            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $earnedPoints = (int) floor($totalAmount / 10);
                    $newTotalSpent = $customer->total_spent + $totalAmount;
                    $newPoints = $customer->loyalty_points + $earnedPoints;
                    $newTier = $newPoints >= 800 ? 'Platinum' : ($newPoints >= 400 ? 'Gold' : ($newPoints >= 150 ? 'Silver' : 'Bronze'));

                    $updateData = [
                        'loyalty_points' => $newPoints,
                        'tier'           => $newTier,
                        'total_spent'    => $newTotalSpent,
                    ];

                    // If paid via Khata (Credit)
                    if (str_contains(strtolower($paymentMethod), 'khata') || str_contains(strtolower($paymentMethod), 'credit')) {
                        $prevBalance = (float) $customer->credit_balance;
                        $newBalance = $prevBalance + $totalAmount;
                        $updateData['credit_balance'] = $newBalance;

                        // Create Khata transaction entry
                        KhataTransaction::create([
                            'uuid'             => (string) Str::uuid(),
                            'customer_id'      => $customer->id,
                            'customer_name'    => $customer->name,
                            'type'             => 'DEBT_ADD',
                            'amount'           => $totalAmount,
                            'previous_balance' => $prevBalance,
                            'new_balance'      => $newBalance,
                            'payment_method'   => $paymentMethod,
                            'notes'            => 'Sales Receipt #' . $orderNo,
                            'items_json'       => $items,
                            'synced_at'        => now(),
                        ]);
                    }

                    $customer->update($updateData);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order completed successfully.',
                'order'   => $order,
            ], 201);
        });
    }

    public function show(string $id): JsonResponse
    {
        $order = is_numeric($id) ? Order::find($id) : Order::where('uuid', $id)->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
        return response()->json(['success' => true, 'order' => $order]);
    }

    public function refund(string $id): JsonResponse
    {
        $order = is_numeric($id) ? Order::find($id) : Order::where('uuid', $id)->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->status === 'REFUNDED') {
            return response()->json(['success' => false, 'message' => 'Order has already been refunded.'], 400);
        }

        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'REFUNDED', 'synced_at' => now()]);

            // Restore product stock
            $items = is_array($order->items_json) ? $order->items_json : json_decode($order->items_json, true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    $productId = $item['id'] ?? null;
                    $quantity = (int) ($item['quantity'] ?? 1);
                    if ($productId) {
                        Product::where('id', $productId)->increment('stock', $quantity);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order refunded successfully.',
                'order'   => $order,
            ]);
        });
    }
}

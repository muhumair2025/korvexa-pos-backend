<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\KhataTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KhataController extends Controller
{
    /**
     * GET /api/khata/ledger
     * Returns customers with active credit debt and summary stats.
     */
    public function getLedger(): JsonResponse
    {
        $customersWithDebt = Customer::where('credit_balance', '>', 0)
            ->orderBy('credit_balance', 'desc')
            ->get();

        $totalDebt = (float) Customer::sum('credit_balance');
        $activeLoaners = Customer::where('credit_balance', '>', 0)->count();
        $monthRepayments = (float) KhataTransaction::repayments()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return response()->json([
            'success'   => true,
            'customers' => $customersWithDebt,
            'stats'     => [
                'totalDebt'       => $totalDebt,
                'activeLoaners'   => $activeLoaners,
                'monthRepayments' => $monthRepayments,
            ],
        ]);
    }

    /**
     * POST /api/khata/collect-repayment
     */
    public function collectRepayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id'    => 'required|integer',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $customerId = $request->input('customer_id');
        $customer = Customer::find($customerId);

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        $repaymentAmount = (float) $request->input('amount');
        $prevBalance = (float) $customer->credit_balance;
        $newBalance = max(0, $prevBalance - $repaymentAmount);

        return DB::transaction(function () use ($customer, $repaymentAmount, $prevBalance, $newBalance, $request) {
            $customer->update(['credit_balance' => $newBalance]);

            $tx = KhataTransaction::create([
                'uuid'             => $request->input('uuid') ?? (string) Str::uuid(),
                'customer_id'      => $customer->id,
                'customer_name'    => $customer->name,
                'type'             => 'REPAYMENT',
                'amount'           => $repaymentAmount,
                'previous_balance' => $prevBalance,
                'new_balance'      => $newBalance,
                'payment_method'   => $request->input('payment_method', 'Cash'),
                'notes'            => $request->input('notes', 'Repayment collection'),
                'synced_at'        => now(),
            ]);

            return response()->json([
                'success'     => true,
                'message'     => 'Repayment collected successfully.',
                'transaction' => $tx,
                'customer'    => $customer->fresh(),
            ]);
        });
    }

    /**
     * POST /api/khata/record-debt-add
     */
    public function recordDebtAdd(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer',
            'amount'      => 'required|numeric|min:0.01',
            'notes'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $customer = Customer::find($request->input('customer_id'));
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        $debtAmount = (float) $request->input('amount');
        $prevBalance = (float) $customer->credit_balance;
        $newBalance = $prevBalance + $debtAmount;

        return DB::transaction(function () use ($customer, $debtAmount, $prevBalance, $newBalance, $request) {
            $customer->update(['credit_balance' => $newBalance]);

            $tx = KhataTransaction::create([
                'uuid'             => $request->input('uuid') ?? (string) Str::uuid(),
                'customer_id'      => $customer->id,
                'customer_name'    => $customer->name,
                'type'             => 'DEBT_ADD',
                'amount'           => $debtAmount,
                'previous_balance' => $prevBalance,
                'new_balance'      => $newBalance,
                'payment_method'   => 'Khata Credit',
                'notes'            => $request->input('notes', 'Manual Khata debt added'),
                'items_json'       => $request->input('items'),
                'synced_at'        => now(),
            ]);

            return response()->json([
                'success'     => true,
                'message'     => 'Debt recorded successfully.',
                'transaction' => $tx,
                'customer'    => $customer->fresh(),
            ]);
        });
    }

    /**
     * GET /api/khata/statement/{customerId}
     */
    public function getStatement(int $customerId): JsonResponse
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
        }

        $transactions = KhataTransaction::where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success'      => true,
            'customer'     => $customer,
            'transactions' => $transactions,
        ]);
    }
}

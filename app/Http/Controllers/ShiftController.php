<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\Order;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ShiftController extends Controller
{
    /**
     * GET /api/shift/active
     */
    public function getActive(): JsonResponse
    {
        $shift = Shift::open()->with('cashMovements')->latest()->first();

        return response()->json([
            'success' => true,
            'shift'   => $shift,
        ]);
    }

    /**
     * POST /api/shift/start
     */
    public function startShift(Request $request): JsonResponse
    {
        $activeShift = Shift::open()->first();
        if ($activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Register shift is already open. Please close current shift before starting a new one.',
                'shift'   => $activeShift,
            ], 400);
        }

        $cashierName = $request->input('cashier_name') ?? (auth()->user()->full_name ?? 'Admin');
        $shiftNo = 'SHIFT-' . date('Ymd') . '-' . rand(100, 999);
        $float = (float) $request->input('opening_float', 200.00);

        $shift = Shift::create([
            'uuid'            => $request->input('uuid') ?? (string) Str::uuid(),
            'shift_no'        => $shiftNo,
            'cashier_name'    => $cashierName,
            'shift_schedule'  => $request->input('shift_schedule', 'Flexible / Full Day'),
            'terminal_id'     => $request->input('terminal_id', 'Terminal #01'),
            'opening_float'   => $float,
            'cash_sales'      => 0,
            'card_sales'      => 0,
            'khata_repayments'=> 0,
            'pay_ins'         => 0,
            'pay_outs'        => 0,
            'expected_cash'   => $float,
            'status'          => 'OPEN',
            'opened_at'       => now(),
            'notes'           => $request->input('notes'),
            'synced_at'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift started successfully.',
            'shift'   => $shift,
        ], 201);
    }

    /**
     * POST /api/shift/record-movement
     */
    public function recordCashMovement(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shift_id' => 'required|integer',
            'type'     => 'required|string|in:PAY_IN,PAY_OUT,SAFE_DROP',
            'amount'   => 'required|numeric|min:0.01',
            'reason'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $shift = Shift::find($request->input('shift_id'));
        if (!$shift || $shift->status !== 'OPEN') {
            return response()->json(['success' => false, 'message' => 'Active open shift not found.'], 404);
        }

        $type = $request->input('type');
        $amount = (float) $request->input('amount');

        return DB::transaction(function () use ($shift, $type, $amount, $request) {
            $movement = CashMovement::create([
                'uuid'         => $request->input('uuid') ?? (string) Str::uuid(),
                'shift_id'     => $shift->id,
                'type'         => $type,
                'amount'       => $amount,
                'reason'       => $request->input('reason'),
                'cashier_name' => auth()->user()->full_name ?? 'Admin',
                'synced_at'    => now(),
            ]);

            if ($type === 'PAY_IN') {
                $shift->increment('pay_ins', $amount);
            } else {
                $shift->increment('pay_outs', $amount);
            }

            return response()->json([
                'success'  => true,
                'message'  => 'Cash movement recorded.',
                'movement' => $movement,
                'shift'    => $shift->fresh()->load('cashMovements'),
            ]);
        });
    }

    /**
     * POST /api/shift/close
     */
    public function closeShift(Request $request): JsonResponse
    {
        $shiftId = $request->input('shift_id');
        $shift = $shiftId ? Shift::find($shiftId) : Shift::open()->first();

        if (!$shift || $shift->status !== 'OPEN') {
            return response()->json(['success' => false, 'message' => 'No active open shift found to close.'], 404);
        }

        // Calculate actual sales during shift duration
        $openedAt = $shift->opened_at;
        $closedAt = now();

        $cashSales = (float) Order::completed()
            ->where('payment_method', 'Cash')
            ->whereBetween('created_at', [$openedAt, $closedAt])
            ->sum('total_amount');

        $cardSales = (float) Order::completed()
            ->where('payment_method', 'Card')
            ->whereBetween('created_at', [$openedAt, $closedAt])
            ->sum('total_amount');

        $expectedCash = $shift->opening_float + $cashSales + $shift->pay_ins - $shift->pay_outs;
        $actualCash = (float) $request->input('actual_cash', $expectedCash);
        $difference = $actualCash - $expectedCash;

        $shift->update([
            'cash_sales'    => $cashSales,
            'card_sales'    => $cardSales,
            'expected_cash' => $expectedCash,
            'actual_cash'   => $actualCash,
            'difference'    => $difference,
            'status'        => 'CLOSED',
            'closed_at'     => $closedAt,
            'notes'         => $request->input('notes', $shift->notes),
            'synced_at'     => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Register shift closed successfully.',
            'shift'   => $shift->fresh()->load('cashMovements'),
        ]);
    }

    /**
     * GET /api/shift/history
     */
    public function getHistory(): JsonResponse
    {
        $shifts = Shift::with('cashMovements')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'shifts'  => $shifts,
        ]);
    }
}

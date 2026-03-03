<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryLedgerService
{
    public function recordMovement(array $payload): InventoryMovement
    {
        return DB::transaction(function () use ($payload) {
            $stock = InventoryStock::query()->firstOrCreate(
                [
                    'site_id' => $payload['site_id'],
                    'item_id' => $payload['item_id'],
                ],
                [
                    'quantity' => 0,
                    'low_stock_threshold' => 0,
                    'avg_unit_cost' => 0,
                ]
            );

            $direction = $this->movementDirection($payload['movement_type']);
            $quantity = (float) $payload['quantity'];

            $newBalance = $direction === 'in'
                ? $stock->quantity + $quantity
                : $stock->quantity - $quantity;

            if ($newBalance < 0) {
                throw new RuntimeException('Insufficient stock for this movement.');
            }

            if ($direction === 'in' && isset($payload['unit_cost']) && $payload['unit_cost'] !== null) {
                $incomingCost = (float) $payload['unit_cost'];
                $currentCostValue = $stock->quantity * (float) $stock->avg_unit_cost;
                $newCostValue = $currentCostValue + ($quantity * $incomingCost);
                $stock->avg_unit_cost = $newBalance > 0 ? ($newCostValue / $newBalance) : 0;
            }

            $stock->quantity = $newBalance;
            $stock->save();

            return InventoryMovement::create([
                'site_id' => $payload['site_id'],
                'item_id' => $payload['item_id'],
                'movement_type' => $payload['movement_type'],
                'quantity' => $quantity,
                'unit_cost' => $payload['unit_cost'] ?? null,
                'running_balance_after' => $newBalance,
                'procurement_request_id' => $payload['procurement_request_id'] ?? null,
                'reference' => $payload['reference'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'performed_by' => $payload['performed_by'],
            ]);
        });
    }

    private function movementDirection(string $movementType): string
    {
        return in_array($movementType, ['procurement_in', 'adjustment_in', 'transfer_in'], true) ? 'in' : 'out';
    }
}

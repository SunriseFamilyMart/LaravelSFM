use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public static function fifoReturn(
        int $productId,
        string $branch,
        int $returnQty,
        float $unitPrice,
        string $referenceType,
        int $referenceId
    ) {
        DB::transaction(function () use (
            $productId,
            $branch,
            $returnQty,
            $unitPrice,
            $referenceType,
            $referenceId
        ) {

            // 1️⃣ Fetch OUT batches in FIFO order
            $outBatches = InventoryTransaction::where([
                    'product_id' => $productId,
                    'branch'     => $branch,
                    'type'       => 'OUT',
                ])
                ->where('remaining_qty', '>', 0)
                ->orderBy('created_at') // FIFO
                ->lockForUpdate()
                ->get();

            $remainingToReturn = $returnQty;

            foreach ($outBatches as $batch) {
                if ($remainingToReturn <= 0) break;

                $deduct = min($batch->remaining_qty, $remainingToReturn);

                // 2️⃣ Reduce remaining_qty from OUT batch
                $batch->remaining_qty -= $deduct;
                $batch->save();

                // 3️⃣ Create IN transaction for returned stock
                InventoryTransaction::create([
                    'product_id'    => $productId,
                    'branch'        => $branch,
                    'type'          => 'IN',
                    'quantity'      => $deduct,
                    'remaining_qty' => $deduct,
                    'unit_price'    => $unitPrice,
                    'total_value'   => $unitPrice * $deduct,
                    'batch_id'      => $batch->batch_id, // SAME batch
                    'reference_type'=> $referenceType,
                    'reference_id'  => $referenceId,
                ]);

                $remainingToReturn -= $deduct;
            }

            if ($remainingToReturn > 0) {
                throw new \Exception('Return qty exceeds sold qty (FIFO mismatch)');
            }
        });
    }
}

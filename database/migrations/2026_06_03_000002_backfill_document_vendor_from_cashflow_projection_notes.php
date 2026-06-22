<?php

use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CashflowProjectionLineItem::query()
            ->whereNull('no_dokumen')
            ->whereNull('nama_vendor')
            ->whereNotNull('notes')
            ->orderBy('id')
            ->chunkById(100, function ($lineItems): void {
                foreach ($lineItems as $lineItem) {
                    $documentNumber = $this->extractNoteValue((string) $lineItem->notes, 'No Dokumen');
                    $vendorName = $this->extractNoteValue((string) $lineItem->notes, 'Vendor');

                    if ($documentNumber === null && $vendorName === null) {
                        continue;
                    }

                    $lineItem->forceFill([
                        'no_dokumen' => $documentNumber,
                        'nama_vendor' => $vendorName,
                    ])->save();
                }
            });
    }

    public function down(): void
    {
        // Data backfill is intentionally not reversible.
    }

    private function extractNoteValue(string $notes, string $label): ?string
    {
        if (! preg_match('/^'.preg_quote($label, '/').':\s*(.+)$/mi', $notes, $matches)) {
            return null;
        }

        $value = trim((string) $matches[1]);

        return $value === '' ? null : $value;
    }
};

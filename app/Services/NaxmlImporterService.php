<?php

namespace App\Services;

use App\Models\NaxmlImport;
use App\Models\PbSku;
use App\Models\PbSkuUpc;
use App\Models\PosFinancialEvent;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\PosTransactionTender;
use Carbon\Carbon;

class NaxmlImporterService
{
    private array $skuCache = [];
    private array $upcCache = [];

    public function importForDate(string $date, bool $force = false): array
    {
        $dir = base_path("pos/data/{$date}/receive");

        if (!is_dir($dir)) {
            return ['error' => "Directory not found: {$dir}", 'total' => 0, 'processed' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $files = glob("{$dir}/NAXML-POSJournal*.XML");
        $results = ['total' => count($files), 'processed' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($files as $filepath) {
            $filename = basename($filepath);

            if (!$force && NaxmlImport::where('filename', $filename)->where('status', 'completed')->exists()) {
                $results['skipped']++;
                continue;
            }

            $import = $this->importFile($filepath);
            $import->status === 'completed' ? $results['processed']++ : $results['failed']++;
        }

        return $results;
    }

    public function importFile(string $filepath): NaxmlImport
    {
        $filename = basename($filepath);

        $import = NaxmlImport::updateOrCreate(
            ['filename' => $filename],
            ['filepath' => $filepath, 'status' => 'processing', 'error_message' => null]
        );

        try {
            $xml = new \SimpleXMLElement(file_get_contents($filepath));

            $storeLocationId = (string) $xml->TransmissionHeader->StoreLocationID;
            $header = $xml->JournalReport->JournalHeader;
            $shiftId = (string) $header->SecondaryReportPeriod;
            $businessDate = (string) $header->BusinessDate;

            $import->update([
                'store_location_id' => $storeLocationId,
                'shift_id' => $shiftId,
                'business_date' => $businessDate,
            ]);

            $transactionCount = 0;
            $itemCount = 0;
            $financialEventCount = 0;

            if (isset($xml->JournalReport->SaleEvent)) {
                $itemCount += $this->processSaleTransaction(
                    $xml->JournalReport->SaleEvent,
                    $import,
                    $storeLocationId,
                    $shiftId,
                    $businessDate
                );
                $transactionCount++;
            }

            if (isset($xml->JournalReport->FinancialEvent)) {
                $this->processFinancialEvent(
                    $xml->JournalReport->FinancialEvent,
                    $import,
                    $storeLocationId,
                    $shiftId,
                    $businessDate
                );
                $financialEventCount++;
            }

            $import->update([
                'status' => 'completed',
                'transaction_count' => $transactionCount,
                'item_count' => $itemCount,
                'financial_event_count' => $financialEventCount,
                'imported_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $import->fresh();
    }

    private function processSaleTransaction(
        \SimpleXMLElement $event,
        NaxmlImport $import,
        string $storeLocationId,
        string $shiftId,
        string $businessDate
    ): int {
        $summary = $event->TransactionSummary;
        $seqNumber = (string) $event['uuid'];

        // Upsert: update if same sequence_number already exists, else create
        $transaction = PosTransaction::updateOrCreate(
            ['sequence_number' => $seqNumber],
            [
                'naxml_import_id'         => $import->id,
                'store_location_id'       => $storeLocationId,
                'shift_id'                => $shiftId,
                'register_id'             => (string) $event->RegisterID,
                'cashier_id'              => (string) $event->CashierID,
                'transaction_id'          => (string) $event->TransactionID,
                'business_date'           => $businessDate,
                'started_at'              => $this->parseDateTime((string) $event->EventStartDate, (string) $event->EventStartTime),
                'ended_at'                => $this->parseDateTime((string) $event->EventEndDate, (string) $event->EventEndTime),
                'receipt_at'              => $this->parseDateTime((string) $event->ReceiptDate, (string) $event->ReceiptTime),
                'is_training'             => $this->yesNo($event->TrainingModeFlag),
                'is_outside_sale'         => $this->yesNo($event->OutsideSalesFlag),
                'is_offline'              => $this->yesNo($event->OfflineFlag),
                'is_suspended'            => $this->yesNo($event->SuspendFlag),
                'total_gross_amount'      => (float) ($summary->TransactionTotalGrossAmount ?? 0),
                'total_net_amount'        => (float) ($summary->TransactionTotalNetAmount ?? 0),
                'total_tax_exempt_amount' => (float) ($summary->TransactionTotalTaxExemptAmount ?? 0),
                'total_tax_amount'        => (float) ($summary->TransactionTotalTaxNetAmount ?? 0),
                'total_grand_amount'      => (float) ($summary->TransactionTotalGrandAmount ?? 0),
            ]
        );

        // Always delete and re-insert items/tenders so product links refresh
        $transaction->items()->delete();
        $transaction->tenders()->delete();

        $itemCount = 0;

        if (!isset($event->TransactionDetailGroup->TransactionLine)) {
            return $itemCount;
        }

        foreach ($event->TransactionDetailGroup->TransactionLine as $line) {
            $seq = (int) $line->TransactionLineSequenceNumber;

            if (isset($line->ItemLine)) {
                $this->createItemLine($line->ItemLine, $transaction, $seq);
                $itemCount++;
            } elseif (isset($line->FuelLine)) {
                $this->createFuelLine($line->FuelLine, $transaction, $seq);
                $itemCount++;
            } elseif (isset($line->FuelPrepayLine)) {
                $this->createFuelPrepayLine($line->FuelPrepayLine, $transaction, $seq);
                $itemCount++;
            }

            if (isset($line->TenderInfo)) {
                $this->createTenderLine($line->TenderInfo, $transaction);
            }
        }

        return $itemCount;
    }

    private function createItemLine(\SimpleXMLElement $itemLine, PosTransaction $transaction, int $lineSeq): void
    {
        $pluCode = (string) $itemLine->ItemCode->POSCode;
        $sku = $this->resolveProduct($pluCode);

        PosTransactionItem::create([
            'pos_transaction_id'   => $transaction->id,
            'plu_code'             => $pluCode,
            'item_number'          => $sku?->item_number,
            'department_number'    => $sku?->department_number,
            'plu_modifier'         => ($m = (string) $itemLine->ItemCode->POSCodeModifier['name']) !== '' ? $m : null,
            'line_sequence'        => $lineSeq,
            'description'          => (string) $itemLine->Description,
            'merchandise_code'     => ($mc = (string) $itemLine->MerchandiseCode) !== '' ? $mc : null,
            'entry_method'         => ($em = (string) $itemLine->EntryMethod['method']) !== '' ? $em : null,
            'quantity'             => (float) $itemLine->SalesQuantity,
            'actual_sale_price'    => (float) $itemLine->ActualSalesPrice,
            'regular_sell_price'   => (float) $itemLine->RegularSellPrice,
            'sales_amount'         => (float) $itemLine->SalesAmount,
            'tax_level_id'         => ($tl = (string) ($itemLine->ItemTax->TaxLevelID ?? '')) !== '' ? $tl : null,
            'tax_collected_amount' => (float) ($itemLine->ItemTax->TaxCollectedAmount ?? 0),
            'taxable_sales_amount' => (float) ($itemLine->ItemTax->TaxableSalesAmount ?? 0),
            'item_type_code'       => ($tc = (string) $itemLine->ItemTypeCode) !== '' ? $tc : 'MDSE',
            'item_type_sub_code'   => ($tsc = (string) $itemLine->ItemTypeSubCode) !== '' ? $tsc : null,
        ]);
    }

    private function createFuelLine(\SimpleXMLElement $fuelLine, PosTransaction $transaction, int $lineSeq): void
    {
        $gradeId = (string) $fuelLine->FuelGradeID;
        $description = ($d = (string) $fuelLine->Description) !== '' ? $d : "Fuel Grade {$gradeId}";
        $uom = (string) $fuelLine->SalesQuantity['uom'];

        PosTransactionItem::create([
            'pos_transaction_id'   => $transaction->id,
            'plu_code'             => $gradeId,
            'item_number'          => null,
            'department_number'    => null,
            'plu_modifier'         => (string) $fuelLine->FuelPositionID ?: null,
            'line_sequence'        => $lineSeq,
            'description'          => $description,
            'merchandise_code'     => ($mc = (string) $fuelLine->MerchandiseCode) !== '' ? $mc : null,
            'entry_method'         => ($em = (string) $fuelLine->EntryMethod['method']) !== '' ? $em : null,
            'quantity'             => (float) $fuelLine->SalesQuantity,
            'actual_sale_price'    => (float) $fuelLine->ActualSalesPrice,
            'regular_sell_price'   => (float) $fuelLine->RegularSellPrice,
            'sales_amount'         => (float) $fuelLine->SalesAmount,
            'tax_level_id'         => null,
            'tax_collected_amount' => 0,
            'taxable_sales_amount' => 0,
            'item_type_code'       => 'FUEL',
            'item_type_sub_code'   => $uom !== '' ? $uom : 'liter',
        ]);
    }

    private function createFuelPrepayLine(\SimpleXMLElement $prepayLine, PosTransaction $transaction, int $lineSeq): void
    {
        $gradeId = (string) $prepayLine->MerchandiseCode;
        $amount = (float) $prepayLine->SalesAmount;
        $description = $amount < 0
            ? "Fuel Prepay Settlement (Grade {$gradeId})"
            : "Fuel Prepay (Grade {$gradeId})";

        PosTransactionItem::create([
            'pos_transaction_id'   => $transaction->id,
            'plu_code'             => $gradeId,
            'item_number'          => null,
            'department_number'    => null,
            'plu_modifier'         => (string) $prepayLine->FuelPositionID ?: null,
            'line_sequence'        => $lineSeq,
            'description'          => $description,
            'merchandise_code'     => $gradeId !== '' ? $gradeId : null,
            'entry_method'         => null,
            'quantity'             => 1,
            'actual_sale_price'    => abs($amount),
            'regular_sell_price'   => 0,
            'sales_amount'         => $amount,
            'tax_level_id'         => null,
            'tax_collected_amount' => 0,
            'taxable_sales_amount' => 0,
            'item_type_code'       => 'PREPAY',
            'item_type_sub_code'   => null,
        ]);
    }

    private function createTenderLine(\SimpleXMLElement $tenderInfo, PosTransaction $transaction): void
    {
        PosTransactionTender::create([
            'pos_transaction_id' => $transaction->id,
            'tender_code'        => (string) $tenderInfo->Tender->TenderCode,
            'tender_sub_code'    => ($ts = (string) $tenderInfo->Tender->TenderSubCode) !== '' ? $ts : null,
            'tender_amount'      => (float) $tenderInfo->TenderAmount,
            'is_change'          => $this->yesNo($tenderInfo->ChangeFlag),
        ]);
    }

    private function processFinancialEvent(
        \SimpleXMLElement $event,
        NaxmlImport $import,
        string $storeLocationId,
        string $shiftId,
        string $businessDate
    ): void {
        $seqNumber = (string) $event['uuid'];
        $detail = $event->FinancialEventDetail->PayOutDetail ?? null;
        $tenderInfo = $detail?->TenderInfo ?? null;
        $accountInfo = $tenderInfo?->AccountInfo ?? null;

        PosFinancialEvent::updateOrCreate(
            ['sequence_number' => $seqNumber],
            [
                'naxml_import_id'   => $import->id,
                'store_location_id' => $storeLocationId,
                'shift_id'          => $shiftId,
                'register_id'       => ($r = (string) $event->RegisterID) !== '' ? $r : null,
                'cashier_id'        => ($c = (string) $event->CashierID) !== '' ? $c : null,
                'business_date'     => $businessDate,
                'started_at'        => $this->parseDateTime((string) $event->EventStartDate, (string) $event->EventStartTime),
                'ended_at'          => $this->parseDateTime((string) $event->EventEndDate, (string) $event->EventEndTime),
                'account_id'        => $accountInfo ? ((string) $accountInfo->AccountID ?: null) : null,
                'account_name'      => $accountInfo ? ((string) $accountInfo->AccountName ?: null) : null,
                'detail_amount'     => $detail ? (float) $detail->DetailAmount : 0,
                'tender_code'       => $tenderInfo ? ((string) $tenderInfo->Tender->TenderCode ?: null) : null,
                'tender_sub_code'   => $tenderInfo ? ((string) $tenderInfo->Tender->TenderSubCode ?: null) : null,
                'tender_amount'     => $tenderInfo ? (float) $tenderInfo->TenderAmount : 0,
            ]
        );
    }

    private function resolveProduct(string $pluCode): ?PbSku
    {
        if (array_key_exists($pluCode, $this->skuCache)) {
            return $this->skuCache[$pluCode];
        }

        $upcRecord = PbSkuUpc::with('sku')->where('upc', $pluCode)->first();
        if ($upcRecord?->sku) {
            return $this->skuCache[$pluCode] = $upcRecord->sku;
        }

        $sku = PbSku::find($pluCode);
        return $this->skuCache[$pluCode] = $sku;
    }

    private function yesNo(\SimpleXMLElement $element): bool
    {
        return ((string) $element['value']) === 'yes';
    }

    private function parseDateTime(string $date, string $time): ?Carbon
    {
        if (!$date || $date === '0000-00-00') {
            return null;
        }
        try {
            return Carbon::parse("{$date} {$time}");
        } catch (\Throwable) {
            return null;
        }
    }
}

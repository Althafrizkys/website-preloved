<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SellerTransactionsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected int $sellerId,
        protected ?CarbonInterface $start = null,
        protected ?CarbonInterface $end = null,
    ) {}

    public function collection(): Collection
    {
        return Transaction::query()
            ->where('seller_id', $this->sellerId)
            ->with([
                'buyer:user_id,name,campus',
                'meetingPoint',
                'items.product',
            ])
            ->createdBetween($this->start, $this->end)
            ->orderByDesc('created_at')
            ->orderByDesc('transaction_id')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No. Pesanan',
            'Tanggal Transaksi',
            'Nama Barang',
            'Jumlah',
            'Pembeli',
            'Kampus Pembeli',
            'Lokasi COD',
            'Metode Pembayaran',
            'Total Harga',
            'Status',
            'Tanggal Selesai',
        ];
    }

    /**
     * @param  Transaction  $transaction
     * @return list<int|float|string>
     */
    public function map($transaction): array
    {
        $productNames = $transaction->items
            ->map(fn ($item) => $item->product?->product_name ?? 'Barang')
            ->filter()
            ->implode(', ');

        $meetingPoint = $transaction->meetingPoint
            ? trim(($transaction->meetingPoint->campus ?? '').' - '.($transaction->meetingPoint->point_name ?? ''), ' -')
            : '-';

        return [
            '#'.$transaction->transaction_id,
            $transaction->created_at?->format('d-m-Y H:i') ?? '-',
            $productNames !== '' ? $productNames : '-',
            (int) $transaction->items->sum('quantity'),
            $transaction->buyer?->name ?? '-',
            $transaction->buyer?->campus ?? '-',
            $meetingPoint !== '' ? $meetingPoint : '-',
            strtoupper((string) ($transaction->payment_method ?? 'cod')),
            (float) $transaction->total_price,
            $transaction->status_label,
            $transaction->completed_at?->format('d-m-Y H:i') ?? '-',
        ];
    }
}

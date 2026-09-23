<?php

namespace App\Services;

use App\Models\LabPackage;
use App\Models\LabTest;
use App\Support\PublicImage;
use Illuminate\Support\Collection;

class LabCart
{
    public const SESSION_KEY = 'lab_cart';

    /**
     * @return list<array{type: string, id: int, qty: int}>
     */
    public function raw(): array
    {
        return array_values(session(self::SESSION_KEY, []));
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return collect($this->raw())
            ->map(fn (array $row): string => $row['type'].':'.$row['id'])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{type: string, id: int, qty: int, item: LabTest|LabPackage, unit_price: float, line_total: float, key: string}>
     */
    public function lines(): Collection
    {
        $rows = $this->raw();
        $tests = LabTest::query()
            ->whereIn('id', collect($rows)->where('type', 'test')->pluck('id'))
            ->get()
            ->keyBy('id');
        $packages = LabPackage::query()
            ->whereIn('id', collect($rows)->where('type', 'package')->pluck('id'))
            ->with('tests')
            ->get()
            ->keyBy('id');

        return collect($rows)
            ->map(function (array $row) use ($tests, $packages) {
                $item = $row['type'] === 'package'
                    ? $packages->get((int) $row['id'])
                    : $tests->get((int) $row['id']);

                if (! $item) {
                    return null;
                }

                $unit = $row['type'] === 'package'
                    ? (float) $item->package_price
                    : (float) $item->suggested_price;
                $qty = max(1, (int) $row['qty']);

                return [
                    'type' => $row['type'],
                    'id' => (int) $row['id'],
                    'qty' => $qty,
                    'item' => $item,
                    'unit_price' => $unit,
                    'line_total' => $unit * $qty,
                    'key' => $row['type'].':'.$row['id'],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $lines = $this->lines();

        return [
            'count' => $this->count(),
            'total' => $this->total(),
            'total_label' => number_format($this->total()).' '.__('common.currency'),
            'keys' => $this->keys(),
            'lines' => $lines->map(function (array $line) {
                $item = $line['item'];

                return [
                    'type' => $line['type'],
                    'id' => $line['id'],
                    'key' => $line['key'],
                    'qty' => $line['qty'],
                    'name' => $item->name,
                    'kind' => $line['type'] === 'package' ? __('labs.packages') : __('labs.tests'),
                    'url' => $line['type'] === 'package'
                        ? route('labs.packages.show', $item)
                        : route('labs.tests.show', $item),
                    'photo' => PublicImage::url($item->image_path),
                    'unit_price' => $line['unit_price'],
                    'unit_label' => number_format($line['unit_price']).' '.__('common.currency'),
                    'line_total' => $line['line_total'],
                    'line_total_label' => number_format($line['line_total']).' '.__('common.currency'),
                    'includes' => $line['type'] === 'package' ? $item->includes : $item->measures,
                    'fasting' => $line['type'] === 'test' && $item->fasting_hours
                        ? __('labs.fasting', ['hours' => $item->fasting_hours])
                        : null,
                    'tests' => $line['type'] === 'package'
                        ? $item->tests->pluck('name')->filter()->values()->all()
                        : [],
                ];
            })->values()->all(),
        ];
    }

    public function add(string $type, int $id, int $qty = 1): void
    {
        $type = $type === 'package' ? 'package' : 'test';
        $items = $this->raw();

        foreach ($items as $row) {
            if ($row['type'] === $type && (int) $row['id'] === $id) {
                return;
            }
        }

        $items[] = ['type' => $type, 'id' => $id, 'qty' => max(1, $qty)];
        session([self::SESSION_KEY => $items]);
    }

    public function setQty(string $type, int $id, int $qty): void
    {
        $type = $type === 'package' ? 'package' : 'test';

        if ($qty < 1) {
            $this->remove($type, $id);

            return;
        }

        $qty = min(20, $qty);
        $items = $this->raw();

        foreach ($items as $index => $row) {
            if ($row['type'] === $type && (int) $row['id'] === $id) {
                $items[$index]['qty'] = $qty;
                session([self::SESSION_KEY => $items]);

                return;
            }
        }

        $items[] = ['type' => $type, 'id' => $id, 'qty' => $qty];
        session([self::SESSION_KEY => $items]);
    }

    public function remove(string $type, int $id): void
    {
        $items = array_values(array_filter(
            $this->raw(),
            fn (array $row) => ! ($row['type'] === $type && (int) $row['id'] === $id)
        ));

        session([self::SESSION_KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) collect($this->raw())->sum('qty');
    }

    public function total(): float
    {
        return (float) $this->lines()->sum('line_total');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrdersController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->query('date_from', now()->toDateString());
        $dateTo   = $request->query('date_to', now()->toDateString());

        return Inertia::render('Admin/Orders', [
            'orders'  => $this->getOrders(),
            'history' => $this->getHistory($dateFrom, $dateTo),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
            'date'    => $this->formatDatePtBr(),
        ]);
    }

    public function history(Request $request): Response
    {
        return $this->index($request);
    }

    public function poll(): JsonResponse
    {
        $ids = DB::table('orders')
            ->whereDate('created_at', today())
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->pluck('id')
            ->toArray();

        return response()->json([
            'pending_ids' => $ids,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function updateStatus(Request $request, string $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,ready,cancelled'],
        ]);

        DB::table('orders')
            ->where('id', $order)
            ->update([
                'status'     => $validated['status'],
                'updated_at' => now(),
            ]);

        return redirect()->back();
    }

    private function formatDatePtBr(): string
    {
        $months = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar',  4 => 'Abr',
            5 => 'Mai', 6 => 'Jun', 7 => 'Jul',  8 => 'Ago',
            9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
        ];

        $now = now();

        return sprintf('%s de %s de %s', $now->format('d'), $months[(int) $now->format('n')], $now->format('Y'));
    }

    private function getOrders(): array
    {
        $rows = DB::select("
            SELECT
                orders.id,
                orders.status,
                tables.number,
                tables.label,
                order_items.quantity,
                order_items.note,
                dishes.name AS dish_name
            FROM orders
            INNER JOIN tables ON tables.id = orders.table_id
            INNER JOIN order_items ON order_items.order_id = orders.id
            INNER JOIN dishes ON dishes.id = order_items.dish_id
            WHERE orders.origin = 'table'
              AND DATE(orders.created_at) = CURRENT_DATE
              AND orders.status IN ('pending', 'in_progress', 'ready')
            ORDER BY orders.created_at ASC
        ");

        $grouped = [];
        foreach ($rows as $row) {
            $uuid = $row->id;
            if (!isset($grouped[$uuid])) {
                $grouped[$uuid] = [
                    'id'           => $this->formatOrderDisplayId($uuid),
                    'uuid'         => $uuid,
                    'mesa'         => $row->label ?? 'Mesa ' . $row->number,
                    'status'       => $row->status,
                    'items'        => [],
                    'note_summary' => null,
                ];
            }
            $grouped[$uuid]['items'][] = [
                'qty'  => (int) $row->quantity,
                'name' => (string) $row->dish_name,
                'note' => ($row->note !== null && trim((string) $row->note) !== '')
                    ? (string) $row->note
                    : null,
            ];
        }

        $result = ['pending' => [], 'in_progress' => [], 'ready' => []];

        foreach ($grouped as $order) {
            $notes = array_filter(array_column($order['items'], 'note'));
            $order['note_summary'] = !empty($notes)
                ? implode(', ', array_map(fn(string $n) => '- ' . $n, array_values($notes)))
                : null;

            $status = $order['status'];
            unset($order['status']);

            if (isset($result[$status])) {
                $result[$status][] = $order;
            }
        }

        return $result;
    }

    private function getHistory(string $dateFrom, string $dateTo): array
    {
        $rows = DB::select("
            SELECT
                orders.id,
                orders.status,
                orders.updated_at,
                tables.number,
                tables.label,
                dishes.name AS dish_name
            FROM orders
            INNER JOIN tables ON tables.id = orders.table_id
            INNER JOIN order_items ON order_items.order_id = orders.id
            INNER JOIN dishes ON dishes.id = order_items.dish_id
            WHERE orders.origin = 'table'
              AND orders.status IN ('ready', 'cancelled')
              AND DATE(orders.updated_at) BETWEEN :date_from AND :date_to
            ORDER BY orders.updated_at DESC, orders.id
        ", ['date_from' => $dateFrom, 'date_to' => $dateTo]);

        $grouped = [];
        foreach ($rows as $row) {
            $uuid = $row->id;
            if (!isset($grouped[$uuid])) {
                $grouped[$uuid] = [
                    'id'     => '#' . $this->formatOrderDisplayId($uuid),
                    'mesa'   => $row->label ?? 'Mesa ' . $row->number,
                    'items'  => [],
                    'status' => $row->status,
                    'date'   => date('Y-m-d', strtotime($row->updated_at)),
                    'time'   => date('H:i', strtotime($row->updated_at)),
                ];
            }
            $name = (string) $row->dish_name;
            if (!in_array($name, $grouped[$uuid]['items'], true)) {
                $grouped[$uuid]['items'][] = $name;
            }
        }

        return array_values($grouped);
    }

    private function formatOrderDisplayId(string $uuid): string
    {
        return substr(str_replace('-', '', $uuid), 0, 4);
    }
}

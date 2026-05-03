<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrdersController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->query('date_from', now()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        return Inertia::render('Admin/Orders', [
            'orders' => $this->getOrders(),
            'history' => $this->getHistory($dateFrom, $dateTo),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'date' => $this->formatDatePtBr(),
        ]);
    }

    public function history(Request $request): Response
    {
        return $this->index($request);
    }

    public function updateStatus(string $order): RedirectResponse
    {
        // MVP: no persistence yet, just return to the board.
        return redirect()->back();
    }

    private function formatDatePtBr(): string
    {
        $months = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        $now = now();

        return sprintf(
            '%s de %s de %s',
            $now->format('d'),
            $months[(int) $now->format('n')],
            $now->format('Y')
        );
    }

    private function getOrders(): array
    {
        return [
            'pending' => [
                [
                    'id' => 'ped-1221',
                    'mesa' => 'Mesa 12',
                    'items' => [
                        ['qty' => 1, 'name' => 'Bacon salada', 'note' => 'Sem salada'],
                        ['qty' => 2, 'name' => 'Coca-cola', 'note' => 'coca zero'],
                    ],
                    'note_summary' => '- Sem salada e coca zero',
                ],
                [
                    'id' => 'ped-1222',
                    'mesa' => 'Mesa 12',
                    'items' => [
                        ['qty' => 1, 'name' => 'Bacon salada', 'note' => null],
                        ['qty' => 2, 'name' => 'Coca-cola', 'note' => null],
                    ],
                    'note_summary' => null,
                ],
            ],
            'in_progress' => [
                [
                    'id' => 'ped-1220',
                    'mesa' => 'Mesa 8',
                    'items' => [
                        ['qty' => 1, 'name' => 'Bacon salada', 'note' => 'Sem salada'],
                        ['qty' => 2, 'name' => 'Coca-cola', 'note' => 'coca zero'],
                    ],
                    'note_summary' => '- Sem salada e coca zero',
                ],
            ],
            'ready' => [
                [
                    'id' => 'ped-1219',
                    'mesa' => 'Mesa 4',
                    'items' => [
                        ['qty' => 1, 'name' => 'Bacon salada', 'note' => 'Sem salada'],
                        ['qty' => 2, 'name' => 'Coca-cola', 'note' => 'coca zero'],
                    ],
                    'note_summary' => '- Sem salada e coca zero',
                ],
            ],
        ];
    }

    private function getHistory(string $dateFrom, string $dateTo): array
    {
        $history = [
            [
                'id' => 'ped-1219',
                'mesa' => 'Mesa 4',
                'items' => ['Bacon salada', 'Coca-cola'],
                'status' => 'ready',
                'date' => now()->toDateString(),
                'time' => '12:48',
            ],
            [
                'id' => 'ped-1218',
                'mesa' => 'Mesa 2',
                'items' => ['Prato feito', 'Suco de laranja'],
                'status' => 'cancelled',
                'date' => now()->toDateString(),
                'time' => '12:31',
            ],
            [
                'id' => 'ped-1217',
                'mesa' => 'Mesa 9',
                'items' => ['X-burger', 'Batata frita'],
                'status' => 'ready',
                'date' => now()->subDay()->toDateString(),
                'time' => '21:09',
            ],
        ];

        return array_values(array_filter($history, function (array $order) use ($dateFrom, $dateTo): bool {
            return $order['date'] >= $dateFrom && $order['date'] <= $dateTo;
        }));
    }
}

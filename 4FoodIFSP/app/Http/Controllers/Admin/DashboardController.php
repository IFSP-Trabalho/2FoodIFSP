<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->getStats(),
            'date' => $this->formatDatePtBr(),
        ]);
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

    private function getStats(): array
    {
        return [
            'faturamento' => 'R$1.840',
            'pedidos_totais' => 47,
            'mesas_abertas' => 6,
            'ticket_medio' => 'R$39',
            'cozinha' => [
                'em_preparo' => 8,
                'prontos' => 31,
                'tempo_medio' => '14 min',
                'cancelados' => 2,
            ],
            'financeiro' => [
                'mesas_abertas' => 6,
                'mesas_fechadas' => 4,
                'faturamento' => 'R$1.840',
                'pendente' => 'R$412',
            ],
            'garcom' => [
                'mesas_atendidas' => 3,
                'contas_fechadas' => 2,
                'ultimo_fechamento' => '13:42',
                'pedidos_atendidos' => 19,
            ],
            'whatsapp' => [
                'triagem' => 2,
                'em_andamento' => 3,
                'fechados_hoje' => 11,
                'pedidos_delivery' => 6,
            ],
            'ultimos_pedidos' => [
                ['mesa' => 'Mesa 3', 'itens' => 'X-Burger, Batata, Refrigerante', 'status' => 'in_progress', 'total' => 'R$52'],
                ['mesa' => 'Mesa 7', 'itens' => 'Frango grelhado, Suco', 'status' => 'ready', 'total' => 'R$38'],
                ['mesa' => 'Delivery', 'itens' => 'Pizza calabresa, Refrigerante', 'status' => 'in_progress', 'total' => 'R$61'],
                ['mesa' => 'Mesa 1', 'itens' => 'Salada caesar, Agua', 'status' => 'cancelled', 'total' => 'R$29'],
            ],
        ];
    }
}

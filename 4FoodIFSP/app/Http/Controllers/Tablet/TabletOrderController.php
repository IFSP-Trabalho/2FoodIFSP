<?php

namespace App\Http\Controllers\Tablet;

use App\Http\Controllers\Concerns\ResolvesTabletMenu;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TabletOrderController extends Controller
{
    use ResolvesTabletMenu;

    public function index(Request $request)
    {
        $mesa = $request->query('mesa');

        if (!isset($mesa) || !ctype_digit((string) $mesa) || (int) $mesa < 1 || (int) $mesa > 99) {
            return Inertia::render('Tablet/MissingMesa');
        }

        $mesa = (int) $mesa;

        return Inertia::render('Tablet/Order', [
            'mesa'       => $mesa,
            'categories' => $this->getTabletMenuCategories(),
            'dishes'     => $this->getTabletMenuDishes(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mesa'             => ['required', 'integer', 'min:1', 'max:99'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.dish_id'  => ['required', 'uuid', 'exists:dishes,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.note'     => ['nullable', 'string', 'max:200'],
        ]);

        $table = DB::table('tables')->where('number', $validated['mesa'])->first();

        if (!$table) {
            return response()->json([
                'message' => 'Mesa não encontrada',
                'errors'  => ['mesa' => ['Mesa não encontrada']],
            ], 422);
        }

        $dishIds = array_column($validated['items'], 'dish_id');
        $dishes  = DB::table('dishes')->whereIn('id', $dishIds)->get()->keyBy('id');

        foreach ($validated['items'] as $item) {
            $dish = $dishes->get($item['dish_id']);
            if (!$dish || !$dish->active) {
                return response()->json([
                    'message' => 'Prato indisponível',
                    'errors'  => ['items' => ['Prato indisponível']],
                ], 422);
            }
        }

        $orderId = DB::transaction(function () use ($validated, $table, $dishes) {
            $orderId = (string) Str::uuid();

            DB::table('orders')->insert([
                'id'         => $orderId,
                'table_id'   => $table->id,
                'origin'     => 'table',
                'status'     => 'pending',
                'paid'       => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                $dish = $dishes->get($item['dish_id']);
                DB::table('order_items')->insert([
                    'id'         => (string) Str::uuid(),
                    'order_id'   => $orderId,
                    'dish_id'    => $item['dish_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $dish->price,
                    'note'       => isset($item['note']) && trim($item['note']) !== ''
                        ? trim($item['note'])
                        : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $orderId;
        });

        return response()->json([
            'order_id' => $orderId,
            'message'  => 'Pedido enviado para a cozinha',
        ], 201);
    }
}

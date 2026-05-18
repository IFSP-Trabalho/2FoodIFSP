<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait ResolvesTabletMenu
{
    protected function dishPhotoUrl(?string $photoPath): ?string
    {
        if ($photoPath === null || trim($photoPath) === '') {
            return null;
        }

        return Storage::url(ltrim($photoPath, '/'));
    }

    protected function getDishCategories(): array
    {
        $rows = DB::select("
            SELECT dish_categories.id, dish_categories.name, dish_categories.slug,
                   COUNT(dishes.id) AS dishes_count
            FROM dish_categories
            LEFT JOIN dishes
                ON dishes.category_id = dish_categories.id
               AND dishes.active = true
            GROUP BY dish_categories.id, dish_categories.name, dish_categories.slug
            ORDER BY dish_categories.name ASC
        ");

        return array_map(fn($row) => [
            'id'           => (string) $row->id,
            'name'         => (string) $row->name,
            'slug'         => (string) $row->slug,
            'dishes_count' => (int) $row->dishes_count,
        ], $rows);
    }

    protected function getDishes(): array
    {
        $rows = DB::select("
            SELECT
                dishes.id,
                dishes.name,
                dishes.description,
                dishes.price,
                dishes.photo_path,
                dishes.category_id,
                dishes.active,
                dish_categories.name AS category_name
            FROM dishes
            INNER JOIN dish_categories ON dish_categories.id = dishes.category_id
            ORDER BY dish_categories.name ASC, dishes.name ASC
        ");

        return array_map(fn($row) => [
            'id'            => (string) $row->id,
            'name'          => (string) $row->name,
            'description'   => ($row->description !== null && trim((string) $row->description) !== '')
                ? (string) $row->description
                : null,
            'price'         => (float) $row->price,
            'photo_url'     => $this->dishPhotoUrl($row->photo_path),
            'category_id'   => (string) $row->category_id,
            'category_name' => (string) $row->category_name,
            'active'        => (bool) $row->active,
        ], $rows);
    }

    protected function getTabletMenuCategories(): array
    {
        $rows = DB::select("
            SELECT dish_categories.id, dish_categories.name, dish_categories.slug,
                   COUNT(dishes.id) AS dishes_count
            FROM dish_categories
            LEFT JOIN dishes
                ON dishes.category_id = dish_categories.id
               AND dishes.active = true
            GROUP BY dish_categories.id, dish_categories.name, dish_categories.slug
            HAVING COUNT(dishes.id) > 0
            ORDER BY dish_categories.name ASC
        ");

        return array_map(fn($row) => [
            'id'           => (string) $row->id,
            'name'         => (string) $row->name,
            'slug'         => (string) $row->slug,
            'dishes_count' => (int) $row->dishes_count,
        ], $rows);
    }

    protected function getTabletMenuDishes(): array
    {
        $rows = DB::select("
            SELECT
                dishes.id,
                dishes.name,
                dishes.description,
                dishes.price,
                dishes.photo_path,
                dishes.category_id,
                dish_categories.name AS category_name
            FROM dishes
            INNER JOIN dish_categories ON dish_categories.id = dishes.category_id
            WHERE dishes.active = true
            ORDER BY dish_categories.name ASC, dishes.name ASC
        ");

        return array_map(fn($row) => [
            'id'            => (string) $row->id,
            'name'          => (string) $row->name,
            'description'   => ($row->description !== null && trim((string) $row->description) !== '')
                ? (string) $row->description
                : null,
            'price'         => (float) $row->price,
            'photo_url'     => $this->dishPhotoUrl($row->photo_path),
            'category_id'   => (string) $row->category_id,
            'category_name' => (string) $row->category_name,
        ], $rows);
    }
}

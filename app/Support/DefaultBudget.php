<?php

namespace App\Support;

use App\Models\User;

/**
 * Data awal untuk akun baru — disalin dari aplikasi KasRumah di AI Studio.
 */
class DefaultBudget
{
    public const MONTHLY_INCOME = 7500000;

    /** @return array<int, array{name: string, limit: int, color: string}> */
    public static function categories(): array
    {
        return [
            ['name' => 'Pendidikan Anak', 'limit' => 1500000, 'color' => '#0D7A5F'],
            ['name' => 'Transportasi', 'limit' => 850000, 'color' => '#0369A1'],
            ['name' => 'Rumah & Tagihan', 'limit' => 900000, 'color' => '#B45309'],
            ['name' => 'Konsumsi & Dapur', 'limit' => 1600000, 'color' => '#7C3AED'],
            ['name' => 'Kesehatan & Keluarga', 'limit' => 500000, 'color' => '#BE123C'],
            ['name' => 'Lainnya', 'limit' => 450000, 'color' => '#475569'],
        ];
    }

    /** @return array<int, array{name: string, amount: int, category: string, is_paid: bool}> */
    public static function routineItems(): array
    {
        return [
            ['name' => 'Spp sekolah', 'amount' => 600000, 'category' => 'Pendidikan Anak', 'is_paid' => true],
            ['name' => 'Tahfidz', 'amount' => 150000, 'category' => 'Pendidikan Anak', 'is_paid' => true],
            ['name' => 'Kumon', 'amount' => 480000, 'category' => 'Pendidikan Anak', 'is_paid' => false],
            ['name' => 'Bensin Ipa', 'amount' => 250000, 'category' => 'Transportasi', 'is_paid' => true],
            ['name' => 'Listrik iuran warga', 'amount' => 400000, 'category' => 'Rumah & Tagihan', 'is_paid' => true],
            ['name' => 'Tpa', 'amount' => 50000, 'category' => 'Pendidikan Anak', 'is_paid' => false],
            ['name' => 'Katering rayen', 'amount' => 300000, 'category' => 'Konsumsi & Dapur', 'is_paid' => true],
            ['name' => 'Bensin', 'amount' => 350000, 'category' => 'Transportasi', 'is_paid' => false],
        ];
    }

    /** Isi kategori + template rutin untuk user (dipakai saat registrasi). */
    public static function seedFor(User $user): void
    {
        foreach (self::categories() as $cat) {
            $user->categories()->create($cat);
        }

        self::seedRoutineItems($user);
    }

    /** Buat ulang 8 template rutin awal (kategori dicocokkan lewat nama; dibuat bila belum ada). */
    public static function seedRoutineItems(User $user): void
    {
        $byName = $user->categories()->get()->keyBy('name');

        foreach (self::routineItems() as $item) {
            $category = $byName->get($item['category']);

            if (! $category) {
                $default = collect(self::categories())->firstWhere('name', $item['category']);
                $category = $user->categories()->create($default);
                $byName->put($category->name, $category);
            }

            $user->routineItems()->create([
                'budget_category_id' => $category->id,
                'name' => $item['name'],
                'amount' => $item['amount'],
                'is_active' => true,
                'is_paid' => $item['is_paid'],
            ]);
        }
    }
}

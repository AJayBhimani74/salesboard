<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create(['name' => 'Admin', 'email' => 'admin@example.com']);

        $names = ['Aarav', 'Diya', 'Kabir', 'Meera', 'Rohan', 'Sana', 'Vivaan', 'Isha', 'Arjun', 'Tara'];
        $weights = ['Electronics' => 30, 'Furniture' => 15, 'Clothing' => 25, 'Grocery' => 20, 'Toys' => 10];

        mt_srand(2026);
        $now = now();

        foreach (range(1, 20) as $batch) {
            $rows = [];

            foreach (range(1, 1000) as $i) {
                $daysAgo = (int) (mt_rand(0, 540) * mt_rand(60, 100) / 100);
                $category = $this->pick($weights);

                $rows[] = [
                    'customer_name' => $names[array_rand($names)].' '.chr(mt_rand(65, 90)).'.',
                    'region' => Order::REGIONS[array_rand(Order::REGIONS)],
                    'category' => $category,
                    'status' => mt_rand(1, 100) <= 6 ? 'cancelled' : 'completed',
                    'amount' => mt_rand(30000, 900000) * ($category === 'Electronics' ? 3 : 1) / ($category === 'Grocery' ? 5 : 1),
                    'ordered_at' => $now->copy()->subDays($daysAgo)->subMinutes(mt_rand(0, 1439)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('orders')->insert($rows);
        }
    }

    private function pick(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));

        foreach ($weights as $name => $weight) {
            if (($roll -= $weight) <= 0) {
                return $name;
            }
        }
    }
}

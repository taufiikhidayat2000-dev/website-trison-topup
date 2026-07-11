<?php

namespace Database\Factories;

use App\Models\Order\Order;
use App\Models\Review\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Realistic review snippets, mirroring how customers actually talk about
     * a top-up service. Combined randomly to avoid obviously-repeated text.
     */
    protected array $comments = [
        'Top up cepat sekali, langsung masuk!',
        'Harga murah dibanding tempat lain.',
        'Pelayanan bagus, CS-nya ramah dan responsif.',
        'QRIS instan, ga sampai semenit udah masuk.',
        'Admin ramah banget waktu saya tanya-tanya.',
        'Recommended banget buat top up game.',
        'Pasti balik lagi ke sini buat top up berikutnya.',
        'Trusted, sudah beberapa kali order aman semua.',
        'Prosesnya gampang, tinggal pilih nominal langsung bayar.',
        'Diamond langsung masuk ke akun, ga pakai lama.',
        'Sempat ragu awalnya tapi ternyata beneran cepat.',
        'Harga bersaing dan banyak promo menarik.',
        'Website-nya enak dipakai, ga ribet.',
        'Bayar pakai VA gampang banget prosesnya.',
        'Sudah langganan di sini dari lama, ga pernah kecewa.',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => null,
            'customer_name' => fake()->firstName(),
            'game_name' => 'Mobile Legends',
            'product_name' => 'Diamond',
            'rating' => 5,
            'review' => fake()->randomElement($this->comments).' '.fake()->randomElement($this->comments),
            'status' => 'published',
        ];
    }

    /**
     * Weighted rating distribution matching real marketplace review patterns:
     * ~90% 5-star, 7% 4-star, 2% 3-star, 1% 2-star, <1% 1-star.
     */
    public function weightedRating(): static
    {
        return $this->state(function () {
            $roll = fake()->numberBetween(1, 1000);

            $rating = match (true) {
                $roll <= 900 => 5,
                $roll <= 970 => 4,
                $roll <= 990 => 3,
                $roll <= 998 => 2,
                default => 1,
            };

            return ['rating' => $rating];
        });
    }
}

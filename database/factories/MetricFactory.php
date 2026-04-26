<?php

namespace Database\Factories;

use App\Models\Metric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Metric>
 */
class MetricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => 1,
            'load' => $this->faker->randomFloat(2, 0, 100),
            'cpu_usage' => $this->faker->randomFloat(2, 0, 100),
            'cpu_cores' => $this->faker->numberBetween(1, 32),
            'memory_total' => $this->faker->randomFloat(0, 0, 100),
            'memory_used' => $this->faker->randomFloat(0, 0, 100),
            'memory_free' => $this->faker->randomFloat(0, 0, 100),
            'disk_total' => $this->faker->randomFloat(0, 0, 100),
            'disk_used' => $this->faker->randomFloat(0, 0, 100),
            'disk_free' => $this->faker->randomFloat(0, 0, 100),
            'network_upstream' => $this->faker->randomFloat(2, 0, 1000000),
            'network_downstream' => $this->faker->randomFloat(2, 0, 1000000),
            'network_total_sent' => $this->faker->randomFloat(2, 0, 1000000000),
            'network_total_received' => $this->faker->randomFloat(2, 0, 1000000000),
            'disk_read' => $this->faker->randomFloat(2, 0, 1000000),
            'disk_write' => $this->faker->randomFloat(2, 0, 1000000),
            'disk_tps' => $this->faker->randomFloat(2, 0, 5000),
            'io_wait' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}

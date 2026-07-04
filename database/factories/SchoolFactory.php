<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition()
    {
        $name = $this->faker->company();

        // code and slug are both NOT NULL and uniquely indexed.
        return [
            'school_name' => $name,
            'code'        => 'SCH-'.strtoupper($this->faker->unique()->bothify('??###')),
            'slug'        => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 999999),
        ];
    }
}

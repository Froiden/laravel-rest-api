<?php

$factory->define(
    \Froiden\RestAPI\Tests\Models\DummyUser::class,
    function(Faker\Generator $faker){
        return [
            'name' => $faker->name,
            'email' => $faker->email,
            'age' => $faker->randomDigitNotNull,

        ];
    }
);

$factory->define(
    \Froiden\RestAPI\Tests\Models\DummyPhone::class,
    function(Faker\Generator $faker){

        return [
            'name' => $faker->name,
            'modal_no' => $faker->swiftBicNumber,
            'user_id' => \Froiden\RestAPI\Tests\Models\DummyUser::all()->random()->id,
        ];
    }
);

$factory->define(\Froiden\RestAPI\Tests\Models\DummyPost::class,
    function(Faker\Generator $faker)
    {
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');
        return [
            'post' => $faker->company,
            'user_id' => \Froiden\RestAPI\Tests\Models\DummyUser::all()->random()->id,
        ];
    }
);

$factory->define(\Froiden\RestAPI\Tests\Models\DummyComment::class,
    function(Faker\Generator $faker)
    {
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');
        return [
            'comment' => $faker->text,
            'user_id' => \Froiden\RestAPI\Tests\Models\DummyUser::all()->random()->id,
            'post_id' => \Froiden\RestAPI\Tests\Models\DummyPost::all()->random()->id,
        ];
    }
);







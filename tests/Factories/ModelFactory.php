<?php

$factory->define(
    \Froiden\RestAPI\Tests\Models\DummyPhone::class,
    function(Faker\Generator $faker){
        return [
            'name' => $faker->name,
            'modal_no' => $faker->swiftBicNumber,
        ];
    }
);


$factory->define(
    \Froiden\RestAPI\Tests\Models\DummyUser::class,
    function(Faker\Generator $faker){
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');
        return [
            'name' => $faker->name,
            'email' => $faker->email,
            'age' =>  $faker->randomDigitNotNull,
            'phone_id' => $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyPhone::class)->create()->id,
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
            'user_id' => $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyUser::class)->create()->id,
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
            'user_id' => $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyUser::class)->create()->id,
            'post_id' => $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyPost::class)->create()->id,
        ];
    }
);







#!/usr/bin/env bash

if [ ! -f "laravel/composer.json" ] ; then
    rm -rf laravel
    git clone https://github.com/laravel/laravel
    cd laravel || exit
#    git checkout 5.2
    composer install --no-interaction
    cp .env.example .env
    php artisan key:generate

    if [[ -v PACKAGE_PROVIDER ]]; then
        echo "$(awk '/'\''providers'\''[^\n]*?\[/ { print; print "'$(sed -e 's/\s*//g' <<<${PACKAGE_PROVIDER})',"; next }1' config/app.php)" > config/app.php
    fi

    if [[ -v FACADES ]]; then
        echo "$(awk '/'\''aliases'\''[^\n]*?\[/ { print; print "'$(sed -e 's/\s*//g' <<<${FACADES})',"; next }1' config/app.php)" > config/app.php
    fi

    sed -i "s|'strict' => true|'strict' => false|g" ./config/database.php

    php -r "
        \$arr = json_decode(file_get_contents(\"composer.json\"), true);
        \$arr[\"autoload\"][\"psr-4\"][\"Froiden\\\\RestAPI\\\\\"] = \"laravel-rest-api/src\";
        \$arr[\"autoload\"][\"psr-4\"][\"Froiden\\\\RestAPI\\\\Tests\\\\\"] = \"laravel-rest-api/tests\";
        file_put_contents(\"composer.json\", json_encode(\$arr));
    "
else
    cd laravel || exit
fi

rm -rf laravel-rest-api
git clone https://github.com/Froiden/laravel-rest-api
git checkout master
composer du
cd .. || exit
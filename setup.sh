#!/usr/bin/env bash

if [ ! -f "laravel/composer.json" ] ; then
    rm -rf laravel
    git clone https://github.com/laravel/laravel
    cd laravel || exit
    git checkout 5.2
    composer install --no-interaction
    cp .env.example .env
    php artisan key:generate
    git clone https://github.com/Froiden/laravel-rest-api

    if [[ -v PACKAGE_PROVIDER ]]; then
        echo "$(awk '/'\''providers'\''[^\n]*?\[/ { print; print "'$(sed -e 's/\s*//g' <<<${PACKAGE_PROVIDER})',"; next }1' config/app.php)" > config/app.php
    fi

    if [[ -v FACADES ]]; then
        echo "$(awk '/'\''aliases'\''[^\n]*?\[/ { print; print "'$(sed -e 's/\s*//g' <<<${FACADES})',"; next }1' config/app.php)" > config/app.php
    fi

    php -r "
        \$arr = json_decode(file_get_contents(\"composer.json\"), true);
        \$arr[\"autoload\"][\"psr-4\"][\"Froiden\\\\RestAPI\\\\\"] = \"laravel-rest-api/src\";
        \$arr[\"autoload\"][\"psr-4\"][\"Froiden\\\\RestAPI\\\\Tests\\\\\"] = \"laravel-rest-api/tests\";
        file_put_contents(\"composer.json\", json_encode(\$arr));
    "
    composer du
    cd .. || exit
fi
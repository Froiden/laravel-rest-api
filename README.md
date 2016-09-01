# Laravel Rest API
This package provides a powerfull Rest API functionality for your Laravel project, with minimalist code required for you to write.

**Note**: This package is under development and not recommended for use in production.

## Setup
1. Add this package to yor composer.json
```
"require": {
        "froiden/laravel-rest-api": "dev-master"
}
```

2. Run, `composer update`
3. Add service provider and alias in `app.php`

```
'providers' => [
		...
        \Froiden\RestAPI\Providers\ApiServiceProvider::class
		...
];

'alias' => [
		...
        "ApiRoute" => \Froiden\RestAPI\Facades\ApiRoute::class
		...
];
```

## Usage
1. Extend your eloquent model from `ApiModel` class instead of `Model` class
```
class User extends ApiModel
{
   ...
}
```
2. Extend your controller from `ApiController` class and define the property $model to contain the class name of the User model

```
class UserController extends ApiController
{
   protected $mode = User::class;
}
```

3. All the routes that will service the api should be defined though `ApiRoute` class. You can continue to use 
regular `Route` class for all other routes.
```
ApiRoute::group(['middleware' => ['web', 'auth'], 'prefix' => 'api', 'namespace' => 'App\Http\Controllers'], function () {
    ApiRoute::resource('user', 'UserController');
});
```

Thats it! Your api endpoint `/api/user` will now work. All the REST methods - `index`, `store`, `show`, `put`, `delete` 
- work out of the box.

# Laravel Rest API
This package provides a powerful Rest API functionality for your Laravel project, with minimum code required for you to write.

**Note**: This package is under development and not recommended for use in production.

## Setup
1) Add this package to yor composer.json
```
"require": {
        "froiden/laravel-rest-api": "dev-master"
}
```

2) Run, `composer update`.

3) Add service provider and alias in `app.php`

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
1) Extend your eloquent model from `ApiModel` class instead of `Model` class
```
class User extends ApiModel
{
   ...
}
```
2) Extend your controller from `ApiController` class and define the property $model to contain the class name of the User model

```
class UserController extends ApiController
{
   protected $mode = User::class;
}
```

3) All the routes that will service the api should be defined though `ApiRoute` class. You can continue to use 
regular `Route` class for all other routes.
```
ApiRoute::group(['middleware' => ['web', 'auth'], 'prefix' => 'api', 'namespace' => 'App\Http\Controllers'], function () {
    ApiRoute::resource('user', 'UserController');
});
```

Thats it! Your api endpoint `/api/user` will now work. All the REST methods - `index`, `store`, `show`, `put`, `delete` - work out of the box.

## Features

This package follows [Microsoft RestAPI Guidelines](https://github.com/Microsoft/api-guidelines/blob/master/Guidelines.md) - except the fields definition - which is inspired from Facebook Graph API.

### Parameters
You can modify the results you get from `index` and `show` methods using various paramters as follows:

* **fields**: A comma separated list of fields you want to get in results, in following format:
```
   fields=id,name,comments.limit(10).order(chronological){id,text}
```
Here, `id` and `name` are normal database columns, and comments is a **relation**. If no fields are specified, results contain list of fields in` $defaults` array. By default, this array only has `id` field. You can override it in your model with your own default fields list.

* **filters**: A filter query with defined in [Microsoft RestAPI Guidelines - Filters](https://github.com/Microsoft/api-guidelines/blob/master/Guidelines.md#97-filtering). **Note:** for security reasons, filtering on all columns is disabled. You need to specify a list of columns in $filterable property in your model to allow the columns on which you want to allow filtering.

```
	filters=status eq "active" or (status eq "suspended" and deleted_at eq null)
```
Apart from operators in the guidelines, one more operator - `lk` - is supported which corresponds to like query in MySQL.

* **order**: An order query similar to definition in the guideline's Ordering section.
* **limit**: Number of results to return
* **page**: Page from which results should start

**Note**: Only fields parameter is usable in `show` request. Others have no use.

### Saving and Updating

Saving and updating works out of the box, including relations. But, the fields received will be saved as it is. If you want to modify a particular query before its saved, define a setter in controller, just as you define in Eloquent Models: `setStatusAttribute`.

### Form request

If you use form requests for validation, simple store the request class's reference in the form request parameters in your controller:
```
	$indexRequest = UserIndexRequest::class;
	$storeRequest = UserStoreRequest::class;
```

### Modifying query

You can modify the main query just before execution, by defining the corresponding request's modify function. For example, to modify index request query:

```
public function modifyIndex($query) {
	return $query->where("status", "active");
}
```

### Relations endpoint

You can call relations endpoint to get only the relations. For example, to get a particular user's comments, you can call:
```
	GET /api/user/123/comments
```

## Contribution
This package is in its very early phase. We would love your feedback, issue reports and contributions. Many things are missing, like, tests, many bugs are there and many new features need to be implemented. So, if you find this useful, please contribute.

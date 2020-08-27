# dlpwd-router
Lightweight router library with named url parameters

## Basic Usage

```php
$router = new \dlpwd\router\Router();

$router->get('/', '\\dlpwd\\sample\\controller\\dashboard::renderDashboard');
$router->get('/login', '\\dlpwd\\sample\\controller\\login::showLoginForm');
$router->post('/login', '\\dlpwd\sample\\controller\\login::processLogin');
$router->get('/page/{friendlyUrl}', '\\dlpwd\\sample\\controller\\page::showPage');

$router->resolve();
```

## Configuring Routes

The router supports three request methods, GET, POST and DELETE. There are corresponding methods in the router class to add routes for each request method

```php
Router::get($_url, $_handler);
Router::post($_url, $_handler);
Router::delete($_url, $_handler);
Router::addRoute($_method, $_url, $_handler);
```

### $_method
The request method can be specified when using the addRoute method on the router to create a new route. This method is useful when adding routes in bulk, for example after loading from a database or file, rather than using hard-coded routes.

### $_url
The URL for the route should be specified with a leading forward-slash. Trailing forward-slashes are optional. The router will match requests with and without a trailing forward-slash to the same route. Parameters are specified within curly-braces. The name of the parameters must match the name of the parameters on the handler function, otherwise they will not be passed through

### $_handler
The handler argument specifies the function to call if the route matches. The function can be specified in as any valid PHP callable

## Resolving Routes

To have the router examine a URL and call the relevant controller, execute the resolve method. The resolve method will return any value returned by the controller function

```php
Router::resolve($_url, $_method);
```

### $_url
Omitting or passing null to the $_url parameter will cause the router to use the value of $_SERVER['REQUEST_URI']. Pass a url to this argument to manually run the router for a different URL than the one being requested

### $_method
Omitting or passing null to the $_method parameter will cause the router to use the value of $_SERVER['REQUEST_METHOD']. Pass a method to this argument to override that value. Most useful when manually running the router with a different URL as above

## Laravel-IndieAuth-Client

The Laravel-IndieAuth-Client package offers a simple form field that will allow users to log in to your site via indieauth.

### Installation

first run `composer require inlings.io/laravel-indieauth-client` to fetch the vendor libraries.

Next add it to your providers and aliases in `config/app.php`.


```PHP
<?php 

    'providers' => [
    
    ...

        Inklings\IndieAuth\IndieAuthClientServiceProvider::class,
    
    ...

    'aliases' => [

    ...

        'IndieAuth' => Inklings\IndieAuth\Helpers::class,

    ...

    ],

?>
```

### Adding to templates

Now you can easily add a login / logout form directly in your template

`{!! IndieAuth::login_logout_form() !!}`

There are also `login_form()` and `logout_form()` functions.


You can add a logged in line, if the user is currently logged in.

```PHP
    @if (IndieAuth::is_logged_in())
        <div>Logged In As: {!! IndieAuth::user() !!}</div>
    @endif
```

You can customize the templates by using the `vendor:publish` command


Any results will be in session('error') or session('success');

Look at src/Helpers.php to see all functions available under IndieAuth::

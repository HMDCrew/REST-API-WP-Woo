# REST API WP/Woo

## Description
customized paths for wordpress and woocommerce to use only the essential information in order to reduce the length of the response


## Usage
before starting you need to install the dependencies run in the terminal the command in the project folder:

    composer install

## Wordpress Routes
### POST
    registration route: /wp-json/wpr-register
    required information:
    {
        username: 'my-username',
        email: 'my.email@gmail.com',
        password: '1234',
        repeat_password: '1234',
        plugin_token: 'MySuperSecretToken'
    }
    optional name incormation:
    {
        name: 'my name',
        username: 'my-username',
        email: 'my.email@gmail.com',
        password: '1234',
        repeat_password: '1234',
        plugin_token: 'MySuperSecretToken'
    }
    NOTE: plugin_token is a constant defined REST_API_WORDPRESS_PLUGIN_TOKEN on file 'class-rest-api-wordpress.php'

### GET

## Woocommerce Routes

## Changelog

### 0.0.1

* Initial commit.

## Contributors

* Andrei Leca <> ()

## License

Licensed under [GPL v2](http://www.opensource.org/licenses/gpl-license.php).

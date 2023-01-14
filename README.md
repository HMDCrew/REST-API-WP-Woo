# REST API WP/Woo

## Description
customized paths for wordpress and woocommerce to use only the essential information in order to reduce the length of the response
on plugin is defined "pending" user role is used for new registered users from api route "/wp-json/wpr-register"


## Usage
before starting you need to install the dependencies run in the terminal the command in the project folder:

    composer install

## Wordpress Routes

### User registration:

    route: /wp-json/wpr-register
    method: POST
    required information:
```JSON
{
   "username": "my-username",
   "email": "my.email@gmail.com",
   "password": "1234",
   "repeat_password": "1234",
   "plugin_token": "MySuperSecretToken"
}
```
<br />

optional 'name' information ex:
```JSON
{
   "name": "my name",
   "username": "my-username",
   "email": "my.email@gmail.com",
   "password": "1234",
   "repeat_password": "1234",
   "plugin_token": "MySuperSecretToken"
}
```
    NOTE: plugin_token is a constant defined REST_API_WORDPRESS_PLUGIN_TOKEN on file 'class-rest-api-wordpress.php'

### Pasts management:
    route: /wp-json/wpr-get-posts
    method: GET
    informations for get posts:
```JSON
{
   "post_type": "my_custom_post_type",
   "numberposts": 5,
   "include_metas": "[my_meta_key, acf_key, color_title]",
   "page": 0,
   "search": "any keywords"
}
```
    NOTE: all of this informations is optional and for "include_metas" use meta_key defined in your site
<br />

    route: /wp-json/wpr-get-post
    method: GET
    informations for get post:
```JSON
{
   "post_id": 10,
}
```
<br />

    route: /wp-json/wpr-get-post-meta
    method: GET
    informations for get post meta:
```JSON
{
   "post_id": 10,
   "meta_key": "my_meta_key"
}
```
<br />

    route: /wp-json/wpr-get-taxonomy
    method: POST
    informations get taxonomy:
```JSON
{
   "taxonomy": "my_taxonomy_slug",
   "hide_empty": "1"
}
```
    NOTE: "hide_empty" can have "0" or "1" equivalent false or true

## Woocommerce Routes
### Products management:
    route: /wp-json/wpr-get-products
    method: POST
    informations get woocommerce products:
```JSON
{
   "numberposts": 5,
   "category": "my_category_slug",
   "page": 0,
   "search": "any keywords"
}
```
<br />

    route: /wp-json/wpr-get-product
    method: POST
    informations get woocommerce product:
```JSON
{
   "product_id": 10,
}
```
### Chackout utility:

    route: /wp-json/wpr-chackout-fields
    method: GET
    additional information is not required for this request 

## Future routes:
    
    route: /wp-json/wpr-update-cart
    method: GET
    
    route: /wp-json/wpr-payment-gateway
    method: GET
    
    route: /wp-json/wpr-create-order
    method: POST
    
    route: /wp-json/wpr-stripe-payment
    method: POST



# ticket
This is a Test Driven Development approach to a Concert Ticket purchasing system in Laravel 5.8

### Prerequisites

```
PHP >=7.1
```

### Installation

Clone or fork the repository on Github, or download a zip file. Once you have done so you will need to use composer to install all of the dependancies that comes with Laravel. You can do this with the following command, as long as you have sufficient permissions.

`composer install`

Once installed you will need to change the permissions on the `/storage` and `/bootstrap/cache` folders. This will be different depending on your server. Laravel requires that those two folders and the folders underneath it are writable.

#### Environment File

If composer did not create a `.env` file you can copy the `.env.example` file. Please ensure that the composer install created a `APP_KEY`. if it did not you can use the following artisan command to generate one.

```
php artisan key:generate
```
#### Database

Laravel requires this file in order to run. You will need to configure the following settings in that file.

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret
```

Once the settings have been saved you can run the migrations with the following command

```
php artisan migrate

php artisan db:seed
```

#### Mail
The project uses mailtrap.io for email demos. Add the following to the .env file

```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=

```

#### Stripe Connect

The project requires a stripe account to manage ticket payments. Create a Stripe account here: https://dashboard.stripe.com/register then add the following to the .env file

```
STRIPE_KEY=pk_test_key
STRIPE_SECRET=sk_test_secret
STRIPE_CLIENT_ID=ca_client_id
```

#### Stripe promoter test account 
Create a stripe test account and add the following to the .env file

```
STRIPE_TEST_PROMOTER_ID=acct_id
STRIPE_TEST_PROMOTER_TOKEN=sk_test_promoter_token
```
#### Ticket Code salt

Add a ticket code salt that will be used in generation of ticket codes. This should be a random string that is unique to a project.

```
TICKET_CODE_SALT="RANDOM STRING"
```

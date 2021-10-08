![Toku](https://cdn-bkcnk.nitrocdn.com/GUXtPIoDRfmANuZRyGQQSfLadxWYqbOq/assets/static/optimized/rev-1f548d6/wp-content/uploads/2019/08/Toku-New-High-Res-Logo-2019-Small.png)

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/cheefoong-toku/toku-caas-sdk/blob/main/LICENSE)

## Documentation
You can find the oneline document [here](https://apidocs.toku.co/).


## Installation
You can install the SDK using composer:

    composer require toku/caas_sdk
    
## Quick Start - Call Handle
#### Autoloading
Require/include autoloading if your PHP framework does not handle autoloading.

```php
require __DIR__ . '/vendor/autoload.php';
```

#### Class
Use the call controller class to form call handle command
```php
use \Toku\CaaS\V1\CallHandle\CallController;
```

#### Instance
Create a new call controller instance
```php
$ctrl = new CallController();
```

#### Call Handle Command
Use the call controller to send call handle command such as PlayTTS command.
```php
$ctrl->PlayTTS("This is a test message", "en", "f")->Response();
```
You can also send multiple command.
```php
$ctrl->PlayTTS("This is a test message one", "en", "f")->
       PlayTTS("This is a test message two", "en", "f")->
       Response();
```

#### Sample Code

```php
<?php

	//Require autoloading file if your framework does not handle autoloading
	require __DIR__ . '/vendor/autoload.php';
	
	//Use the call controller class to form call handle command
	use \Toku\CaaS\V1\CallHandle\CallController;

	//Create a new call controller instance
	$ctrl = new CallController();
	
	//Optional
	header('Content-Type: application/json');

	//Use the call controller to send call handle command such as PlayTTS command
	$ctrl->PlayTTS("This is a test message", "en", "f")->
	  Response();

?>
```

# Log

## What is it
Class for logging with multiple levels and support multi-thread working

## Installation
```
composer require lightsource/log
```

## Example of usage

```
use LightSource\DataTypes\DATA_TYPES;
use LightSource\StdResponse\STD_RESPONSE;

require_once __DIR__ . '/vendor/autoload.php';

LOG::$PathToLogDir = __DIR__ . DIRECTORY_SEPARATOR . 'Logs';

abstract class CRON {

	static function Run() {
		LOG::Write( LOG::DEBUG, 'The color is', [ 'color' => 'red' ] );
	}

}

CRON::Run();
```
## Example of output
```
level_debug : CRON : The color is
CRON::Run
info : <!-- Array
(
    [color] => red
) -->
2020-06-07 00:00:00
```
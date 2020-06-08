# Log
[![Latest Stable Version](https://poser.pugx.org/lightsource/log/v)](//packagist.org/packages/lightsource/log)
[![Total Downloads](https://poser.pugx.org/lightsource/log/downloads)](//packagist.org/packages/lightsource/log)
[![Monthly Downloads](https://poser.pugx.org/lightsource/log/d/monthly)](//packagist.org/packages/lightsource/log)
[![Daily Downloads](https://poser.pugx.org/lightsource/log/d/daily)](//packagist.org/packages/lightsource/log)
[![License](https://poser.pugx.org/lightsource/log/license)](//packagist.org/packages/lightsource/log)

## What is it
Class for logging with multiple levels and support multi-thread working

## Installation
```
composer require lightsource/log
```

## Example of usage

```
use LightSource\Log\LOG;

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

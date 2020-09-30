# Lord of the Rings Trivia: Who Doth Sayeth That?!

Lord of the Rings Trivia: Who Doth Sayeth That?! is trivia game where you try to guess the character who said the quote.

## Requirements

```shell
PHP
PHP's cURL
```

## Installation

Clone the package from github onto your webserver:
```shell
git clone https://github.com/mbarlund/lotrt.git
```
Point a browser to the installation directory: http://yoursite.com/lotrt/

## Documentation

This game makes use of The One API found at [https://the-one-api.dev/](https://the-one-api.dev/). All Quotes and Character information was provided thanks to them.

### Options

The `Trivia.php` class contains a few properties that can alter the challenge and let you control where the results get stored.

**Number of questions:**
Change the number of questions the trivia quiz displays.
```php
$number_of_questions = 5
```
**Number of answer options:**
Change the number of answers the player has to choose from.
```php
$number_of_answer_options = 3
```
**Storage API endpoint**
Change the API endpoint where you want the results to be sent (see below)
```php
$storage_api = 'https://lotrt.free.beeceptor.com/results'
```
### Useage

The Trivia object can be instantiated with an existing user or game ID. If none are provided new IDs will be generated for each and stored in SESSION. 
```php
$trivia = new \LOTRT\Trivia($_SESSION['lotrt_user'], $_SESSION['lotrt_game'])
```

**Start a new game**
To start a new game simply call the `startGame()` method:
```php
$trivia->startGame()
```
This will generate an array of questions containing the quote and answer options.
```php
[
    [
        'quote' => ['_id' => quote_id, 'text' => quote_text],
        'characters' => [
            ['_id' => character_id, 'text' => character_text]
            ...
        ]
    ]
    ...
]
```

**Checking the results**
To see how the player did you need to send the results to the `getResults()` method
```php
$trivia->getResults($results)
```
This will check the player's answers, tally their score, and return an array.
The `$results` data should be an array containing an `answers` index containing an array of `quote_id` => `character_id` key value pairs.
```php
[
    'answers' => [
        quote_id => character_id,
        ...
    ]
]
```
The `getResults()` method response:
```php
[
    'success' => boolean,
    'message' => string
    'score' => int
    'total_questions' => int,
    'user_id' => string
    'game_id' => string
    'date' => DateTime string
]
```

**Storing the results**
The `getResults()` method will invoke the `storeResults()` method which sends the `$results` data (see above) to an API endpoint.
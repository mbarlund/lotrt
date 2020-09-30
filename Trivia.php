<?php
namespace LOTRT;

/**
 * Class Trivia
 * @package LOTRT
 */
class Trivia
{
    /**
     * Number of trivia questions
     * @var int
     */
    public $number_of_questions = 5;

    /**
     * Number of choices for the answer
     * @var int
     */
    public $number_of_answer_options = 3;

    /**
     * API url to store results
     * @var string
     */
    private $storage_api = 'https://lotrt.free.beeceptor.com/results';

    /**
     * Storage API token
     * @var string
     */
    private $storage_token = null;

    /**
     * the-one-api.dev API token
     * @var string
     */
    private $data_token = 'luflZsVv2PxN-20V4Whm';

    /**
     * the-one-api.dev API url
     * @var string
     */
    private $data_api = 'https://the-one-api.dev/v2';

    /**
     * curl connection
     * @var resource
     */
    private $connection;

    /**
     * User ID
     * @var string
     */
    private $user_id;

    /**
     * Game ID
     * @var string
     */
    private $game_id;

    /**
     * Quotes
     * @var array
     */
    private $quotes = [];

    /**
     * Characters
     * @var array
     */
    private $characters = [];

    /**
     * Selected Quote keys
     * @var array
     */
    private $selected_quotes = [];

    /**
     * Game questions and answers
     * @var array
     */
    private $game = [];

    /**
     * Player's score
     * @var int
     */
    public $score = 0;

    /**
     * Init a new Trivia game
     */
    public function __construct($user_id = null, $game_id = null)
    {
        // Set user and game id
        $this->setUserId($user_id);
        $this->setGameId($game_id);

        // Load the game
        $this->loadGame();
    }

    /**
     * Start a new game
     * @return array
     */
    public function startGame()
    {
        // Generate a new game ID
        $this->setGameId();

        // Select Questions
        $this->pickQuestions();

        // Return the questions
        return $this->getGame();
    }

    /**
     * Set User ID
     * @param string $string
     * @return void
     */
    public function setUserId(string $string)
    {
        // Check for user id or generate a new one
        if (!$string)
            $string = uniqid('lotrtu', true);

        // Set property
        $this->user_id = $string;

        // Add to SESSION
        $_SESSION['lotrt_user'] = $string;
    }

    /**
     * Set Game ID
     * @param string $string
     * @return void
     */
    public function setGameId(string $string = null)
    {
        // Check for game id or generate a new one
        if (!$string)
            $string = uniqid('lotrtg', true);

        // Set property
        $this->game_id = $string;

        // Add to SESSION
        $_SESSION['lotrt_game'] = $string;
    }

    /**
     * Get the User ID
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Get the Game ID
     * @return string
     */
    public function getGameId()
    {
        return $this->game_id;
    }

    /**
     * Get the game questions
     * @return array
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * Create a cUrl resource
     * @return void
     */
    private function initCurl()
    {
        // Start cUrl
        $this->connection = curl_init();
    }

    /**
     * Set cUrl option
     * @param int $opt
     * @param $values
     * @return void
     */
    private function setCurlOption(int $opt, $values)
    {
        curl_setopt($this->connection, $opt, $values);
    }

    /**
     * Execute cUrl request
     * @return mixed
     */
    private  function getCurlExec()
    {
        return curl_exec($this->connection);
    }

    /**
     * Close cUrl resource
     * @return void
     */
    private function closeCurl()
    {
        curl_close ($this->connection);
    }

    /**
     * Get the game content (characters and quotes)
     * @return void
     */
    private function loadGame()
    {
        // Init curl resource
        $this->initCurl();

        // Set auth token for API
        $this->setCurlOption(CURLOPT_HTTPHEADER, array(sprintf('Authorization: Bearer %s', $this->data_token)));

        // Get characters from api
        $this->fetchCharacters();

        // Get quotes from api
        $this->fetchQuotes();

        // Close curl
        $this->closeCurl();
    }

    /**
     * Load characters from api
     * /character
     *
     * Only fetching 500 results and filtering out bad data
     * @return void
     */
    private function fetchCharacters()
    {
        // Set the URL
        $this->setCurlOption(CURLOPT_URL, $this->data_api . '/character?limit=500');

        // Receive server response ...
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);

        // Get the output
        $server_output = $this->getCurlExec();

        // Decode json
        $server_output = json_decode($server_output, true);

        // Save the characters
        foreach ($server_output['docs'] as $character)
        {
            /*
             * Only saving characters that have an id, name, and race
             * There are lot's of incomplete characters
             */
            if ($character['_id'] && $character['name'] && $character['race'])
                $this->characters[] = $character;
        }

    }

    /**
     * Load quotes from api
     * /quote
     *
     * Filtering quotes where we do not have a valid character for the answer
     * @return void
     */
    private function fetchQuotes()
    {
        // Set the URL
        $this->setCurlOption(CURLOPT_URL, $this->data_api . '/quote');

        // Receive server response ...
        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);

        // Get the output
        $server_output = $this->getCurlExec();

        // Decode json
        $server_output = json_decode($server_output, true);

        // Save the quotes
        foreach ($server_output['docs'] as $quote)
        {
            /*
             * Only saving quotes that have more than 9 characters
             * And only if we saved the character (so we have a valid answer)
             */
            if (strlen($quote['dialog']) > 9 && $this->findCharacter($quote['character']))
                $this->quotes[] = $quote;
        }
    }

    /**
     * Get a random number of quotes for the game
     * @return void
     */
    private function pickQuestions()
    {
        // Shuffle the quotes
        shuffle($this->quotes);

        // Pick a random number of quote keys
        $this->selected_quotes = array_rand($this->quotes, $this->number_of_questions);

        // Build the game array
        $this->buildGame();
    }

    /**
     * Build out the game data: quote and possible answers
     * Creates an array of [quote, characters]
     * @return void
     */
    public function buildGame()
    {
        // Loop through the quote keys
        foreach ($this->selected_quotes as $quotes_key)
        {
            // Get a list of possible answers, which includes the correct answer
            $answerOptions = $this->getAnswerOptions($this->quotes[$quotes_key]['character']);

            // Shuffle the answers
            shuffle($answerOptions);

            // Build a question array
            $question = [
                'quote' => ['_id' => $this->quotes[$quotes_key]['_id'], 'text' => $this->quotes[$quotes_key]['dialog']],
                'characters' => $answerOptions
            ];

            // Add the question to the game array
            $this->game[] = $question;
        }
    }

    /**
     * Get a list of characters to use as answers
     * The provided character_id is the correct answer, which should be included in the results
     * @param string $character_id
     * @return array
     */
    private function getAnswerOptions(string $character_id)
    {
        // Results array
        $options = [];

        // Get the character of the correct answer
        $answerCharacter = $this->findCharacter($character_id);

        // Add the answer to the options
        $options[] = ['_id' => $answerCharacter['_id'], 'text' => sprintf('%s, a %s %s', $answerCharacter['name'], $answerCharacter['gender'], $answerCharacter['race'])];

        /*
         * Get other character options
         * We ask for one extra in the event we get the answer character again
         */
        $characters = $this->getCharacters(($this->number_of_answer_options + 1));

        // Loop through characters and ad to options
        foreach ($characters as $character)
        {
            // Only add this character if it is not the answer character
            if ($character['_id'] !== $answerCharacter['_id'])
                $options[] = ['_id' => $character['_id'], 'text' => sprintf('%s, a %s %s', $character['name'], $character['gender'], $character['race'])];
        }

        /*
         * We asked for an extra character, so make sure we slice the array down to the desired number
         * Note: the correct answer is always at index 0
         */
        $options = array_slice($options, 0, $this->number_of_answer_options);

        return $options;
    }

    /**
     * Get int number of characters
     * @param int $qty
     * @return array
     */
    private function getCharacters(int $qty)
    {
        // Result array
        $characters = [];

        // Shuffle the characters
        shuffle($this->characters);

        // Get random keys
        $keys = array_rand($this->characters, $qty);

        // Get the character associated to the key
        foreach ($keys as $key)
            $characters[] = $this->characters[$key];

        return $characters;
    }

    /**
     * Find a character by ID from the array
     * @param string $character_id
     * @return array|null
     */
    private function findCharacter(string $character_id)
    {
        // Loop characters
        foreach ($this->characters as $key => $character)
        {
            // return it if the ID matches
            if ($character['_id'] === $character_id)
                return $character;
        }

        return null;
    }

    /**
     * Find a quote by ID from the array
     * @param string $quote_id
     * @return array|null
     */
    private function findQuote(string $quote_id)
    {
        // Loop quotes
        foreach ($this->quotes as $key => $quote)
        {
            // return it if the ID matches
            if ($quote['_id'] === $quote_id)
                return $quote;
        }

        return null;
    }

    /**
     * Get the game results, check the answers, and store them
     * Return the json results
     * @param array $data
     * @return string
     */
    public function getResults(array $data)
    {
        // Init the response
        $response = ['success' => false, 'message' => '', 'score' => 0, 'total_questions' => $this->number_of_questions, 'game_id' => null, 'user_id' => null, 'date' => date('Y-m-d h:i:s')];

        // Make sure we have a user
        if (!$this->user_id)
            $response['message'] = 'Invalid user';
        // Make sure we have a game and answers
        elseif (!$this->game_id || !isset($data['answers']))
            $response['message'] = 'Invalid game';
        else
        {
            // Update the user
            $response['user_id'] = $this->user_id;

            // Update the game
            $response['game_id'] = $this->game_id;

            // See how the user did on the quiz
            $this->checkAnswers($data['answers']);

            // Update the response
            $response['success'] = true;

            // Add score to response
            $response['score'] = $this->score;

            // Add response message based on success
            switch($this->score)
            {
                case 0:
                    $response['message'] = 'Hmm. Maybe you can answer this: What have I got in my pocket?';
                    break;
                case 1:
                    $response['message'] = 'Not all those who wander are lost';
                    break;
                case 2:
                    $response['message'] = 'If more of us valued food and cheer and song above hoarded gold, it would be a merrier world.';
                    break;
                case 3:
                    $response['message'] = 'Victory after all, I suppose! Well, it seems a very gloomy business.';
                    break;
                case 4:
                    $response['message'] = 'There is some good in this world, and it\'s worth fighting for.';
                    break;
                default:
                    $response['message'] = 'Even the smallest person can change the course of the future.';
            }

            // Send results to storage
            $this->storeResults($response);
        }

        return $response;
    }

    /**
     * Check submitted answers
     * @param array $answers
     */
    private function checkAnswers(array $answers)
    {
        // Loop each answer
        foreach ($answers as $quote_id => $character_id)
        {
            /*
             * Get the quote
             * See if the quote's character matches the answer character
             * Increment the score
             */
            if ($quote = $this->findQuote($quote_id))
                if ($quote['character'] == $character_id)
                    $this->score++;
        }
    }

    /**
     * Store game results
     * @param array $response
     * @return void
     */
    private function storeResults(array $response)
    {
        try
        {
            // Init curl
            $this->initCurl();

            // Set url
            $this->setCurlOption(CURLOPT_URL, $this->storage_api);

            // Set auth token for storage API
            if ($this->storage_token)
                $this->setCurlOption(CURLOPT_HTTPHEADER, array(sprintf('Authorization: Bearer %s', $this->storage_token)));

            // Set content type
            $this->setCurlOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            // Add results data
            $this->setCurlOption(CURLOPT_POSTFIELDS, http_build_query($response));

            // Receive server response ...
            $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);

            // Execute cUrl
            $this->getCurlExec();

            // Close connections
            $this->closeCurl();
        }
        catch(\Exception $e)
        {
            // Log error
            var_dump($e->getMessage());
        }
    }

}

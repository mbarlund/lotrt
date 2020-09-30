<?php
@session_start();

// require the Trivia class
require_once('Trivia.php');

try
{
    // Init the Game
    $trivia = new \LOTRT\Trivia($_SESSION['lotrt_user'], $_SESSION['lotrt_game']);

    /*
     * Check for form submission or Start new game
     */
    switch($_SERVER['REQUEST_METHOD'])
    {
        case 'POST':
            $response = $trivia->getResults($_POST);
            break;

        default:
            $questions = $trivia->startGame();
    }
}
catch(\Exception $e)
{
    // Log error
    var_dump($e->getMessage());
}

// Rendering the UI below...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lord of the Rings Trivia: Who Doth Sayeth That?!</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Lord of the Rings Trivia: Who Doth Sayeth That?!</h1>

        <?php if(isset($questions) && $questions && count($questions) > 0): ?>
            <p class="alert alert-primary" role="alert">Instructions: Guess which character the quote belongs to.</p>
            <form method="POST">
                <?php foreach ($questions as $question):?>
                    <div class="card m-3">
                        <div class="card-body">
                            <h5 class="card-title">"<?php print(trim($question['quote']['text'])) ?>"</h5>
                            <h6 class="card-subtitle mb-2 text-muted">Who sayeth this?</h6>


                            <?php foreach ($question['characters'] as $character):?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answers[<?php print($question['quote']['_id']) ?>]" id="<?php print($character['_id']) ?>" value="<?php print($character['_id']) ?>">
                                    <label class="form-check-label" for="<?php print($character['_id']) ?>"><?php print($character['text']) ?></label>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="form-group">
                    <button type="submit" class="btn btn-large btn-primary">Submit Answers</button> <a href="" class="btn btn-link">Try Different Quotes</a>
                </div>
            </form>
        <?php elseif(isset($response)): ?>
            <?php if ($response['success'] == true): ?>
                <div class="card mb-3 text-white bg-secondary">
            <?php else: ?>
                    <div class="card mb-3 bg-warning">
            <?php endif; ?>

                <div class="row no-gutters">
                    <div class="col-md-4">
                        <img src="https://i.pinimg.com/originals/b5/56/f2/b556f266c77f0c6c011a5dfc15e53dbf.jpg" class="card-img" alt="The One Ring">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <?php if ($response['success'] == true): ?>
                                <h5 class="card-title"><?php print(sprintf('Your score is %d out of %d!', $response['score'], $response['total_questions'])); ?></h5>
                            <?php else: ?>
                                <h5 class="card-title">Oh oh!</h5>
                            <?php endif; ?>

                            <p class="card-text"><?php print($response['message']); ?></p>

                            <a href="" class="btn btn-lg btn-dark">Try Again</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p class="alert alert-danger" role="alert">Sorry, something's wrong. Try again later.</p>
        <?php endif; ?>
    </div>
</body>
</html>
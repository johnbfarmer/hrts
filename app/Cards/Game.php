<?php

namespace App\Cards;
use Illuminate\Support\Facades\Config;

class Game extends BaseProcess {
    protected $numberOfPlayers = 4;
    protected $numberOfCardsToDeal = 13;
    protected $maxRounds = 30;
    protected $maxScore = 100;
    protected $scores = [];
    protected $players = [];
    protected $round;
    protected $roundCount = 0;
    protected $gameOver = false;
    protected $winner = 'nobody';
    protected $inputFileHandler;
    protected $file;
    protected $playerData = [];

    public function __construct($params)
    {
        $this->numberOfPlayers = 4;
        $this->numberOfCardsToDeal = 13;
        $this->file = $params['file'];
        $this->inputFileHandler = new InputFileHandler(['file' => $this->file]);
        $this->createPlayers();
    }

    public function createPlayers()
    {
        $this->writeln('x '.date('Y-m-d H:i:s'));
        $this->playerData = Config::get('commands.players');
        for ($i = 1; $i <= $this->numberOfPlayers; $i++) {
            $this->players[$i] = new Player($i, $this->playerData[$i-1]);
            $this->scores[$i] = 0;
        }
    }

    public function play()
    {
        while ($this->roundCount++ < $this->maxRounds) {
            $this->writeln('');
            $this->writeln('ROUND '.($this->roundCount));
            $this->writeln('');
            $this->round = new Round([
                'numberOfPlayers' => $this->numberOfPlayers,
                'numberOfCardsToDeal' => $this->numberOfCardsToDeal,
                'players' => $this->players,
                'scores' => $this->scores,
                'roundCount' => $this->roundCount,
                'preShuffledDeck' =>$this->inputFileHandler->getHand($this->roundCount - 1),
            ]);

            $this->round->start();

            while ($this->round->play());

            $this->scores = $this->round->getScores();
            $this->round->report();

            if ($this->checkGameOver()) {
                $this->endGame();
                break;
            }
        }
    }

    public function checkGameOver()
    {
        $maxScore = 0;
        $minScore = 1000;
        $winners = [];

        foreach($this->scores as $score) {
            if ($score > $maxScore) {
                $maxScore = $score;
            }
            if ($score < $minScore) {
                $minScore = $score;
            }
        }

        if ($maxScore >= $this->maxScore) {
            foreach ($this->scores as $playerId => $score) {
                if ($score === $minScore) {
                    $winners[] = $this->players[$playerId]->getName();
                }
            }

            if (count($winners) === 1) {
                $this->winner = $winners[0];
                return true;
            }
        }

        return false;
    }

    public function endGame()
    {
        $this->writeln("\nWinner: " . $this->winner);
        $this->writeln('x '.date('Y-m-d H:i:s'));
        $this->gameOver = true;
    }
}

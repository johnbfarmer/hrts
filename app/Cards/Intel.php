<?php

namespace App\Cards;

class Intel extends BaseProcess {
    protected $hand;
    protected $id;
    protected $gameScores = [];
    protected $roundScores = [];
    protected $cardsPlayedThisRound = [];
    protected $unplayedCards = [[],[],[],[]];
    protected $playersVoidInSuit = [[],[],[],[]]; //playersVoidInSuit[0][2] means p2 void in suit 0
    protected $opponents = [];

    public function __construct($data)
    {
        $this->data = $data;
        $this->id = $data['id'];
        $this->hand = $data['hand'];
        $this->calculateUnplayedCards();
    }

    public function gatherInfo($info)
    {
        $leadSuit = null;
        $topValue = null;
        $this->gameScores = !empty($info['gameScores']) ? $info['gameScores'] : $this->gameScores;
        if (!empty($info['gameScores'])) {
            foreach ($this->gameScores as $id => $score) {
                $this->roundScores[$id] = 0;
            }
        }

        $this->unplayedCards = $this->removeFromUnplayed($info['cardsPlayed']);

        foreach ($info['cardsPlayed'] as $id => $c) {
            if (empty($this->cardsPlayedThisRound[$id])) {
                $this->cardsPlayedThisRound[$id] = [];
            }
            $this->cardsPlayedThisRound[$id][] = $c;
            if (is_null($leadSuit)) {
                $leadSuit = $c->getSuit();
                $topValue = $c->getValue();
                $takesTrick = $id;
                $points = 0;
            }
            $suit = $c->getSuit();
            $value = $c->getValue();
            if ($suit === 2) {
                $points++;
            }
            if ($suit === 3 && $value == 10) {
                $points += 13;
            }
            if ($suit === $leadSuit && $value > $topValue) {
                $takesTrick = $id;
                $topValue = $value;
            }
            if ($suit !== $leadSuit && !in_array($id, $this->playersVoidInSuit[$leadSuit])) {
                $this->playersVoidInSuit[$leadSuit][] = $id;
            }
        }
        $this->gameScores[$takesTrick] += $points;
        $this->roundScores[$takesTrick] += $points;

        return [
            'unplayedCards' => $this->unplayedCards,
            'gameScores' => $this->gameScores,
            'roundScores' => $this->roundScores,
            'cardsPlayedThisRound' => $this->cardsPlayedThisRound,
            'playersVoidInSuit' => $this->playersVoidInSuit, // 2BE UPGRADED
        ];
    }

    protected function calculateUnplayedCards()
    {
        $allCards = [
            [0,1,2,3,4,5,6,7,8,9,10,11,12],
            [0,1,2,3,4,5,6,7,8,9,10,11,12],
            [0,1,2,3,4,5,6,7,8,9,10,11,12],
            [0,1,2,3,4,5,6,7,8,9,10,11,12],
        ];
        $playedCards = [[],[],[],[],];
        foreach ($this->hand->getCards() as $c) {
            $playedCards[$c->getSuit()][] = $c->getValue();
        }

        $unplayedCards = [];

        for ($suit=0; $suit<4; $suit++) {
            $unplayedCards[$suit] = array_values(array_diff($allCards[$suit], $playedCards[$suit]));
        }

        $this->unplayedCards = $unplayedCards;
    }

    public function getUnplayedCards()
    {
        return $this->unplayedCards;
    }

    public function removeFromUnplayed($cardsPlayedThisTrick)
    {
        $playedCards = [[],[],[],[],];
        foreach ($cardsPlayedThisTrick as $c) {
            $playedCards[$c->getSuit()][] = $c->getValue();
        }

        $unplayedCards = [];

        for ($suit=0; $suit<4; $suit++) {
            $unplayedCards[$suit] = array_values(array_diff($this->unplayedCards[$suit], $playedCards[$suit]));
        }

        $this->unplayedCards = $unplayedCards;

        return $this->unplayedCards;
    }
}

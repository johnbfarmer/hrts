<?php

namespace App\Cards;

class Player extends BaseProcess {
    protected $hand;
    protected $id;
    protected $name;
    protected $riskTolerance = 0;
    protected $myScore = 0;
    protected $gameScores = [];
    protected $roundScores = [];
    protected $cardPlayed;
    protected $cardsPlayedThisRound = [];
    protected $unplayedCards = [[],[],[],[]];
    protected $playersVoidInSuit = [[],[],[],[]]; //playersVoidInSuit[0][2] means p2 void in suit 0
    protected $isHoldHand;
    protected $handStrategy = 'avoidPoints';
    protected $handAnalysis = ['AVP' => [], 'STM' => []];
    protected $trickStrategy;
    protected $selector;
    protected $intel;

    public function __construct($id, $data = null)
    {
        $name = $data['name'];
        if (is_null($name)) {
            $name = 'Player ' . $id;
        }

        $this->id = $id;
        $this->name = $name;
        $this->selector = new DefaultSelector(['handAnalysis' => $this->handAnalysis]);
        $this->riskTolerance = !empty($data['riskTolerance']) ? $data['riskTolerance'] : rand(1,40)/100;
        $this->writeln($this->name . ' has riskTolerance ' . $this->riskTolerance);
    }

    public function addHand($hand, $isHoldHand)
    {
        $this->hand = $hand;
        $this->isHoldHand = $isHoldHand;
        $this->intel = new Intel([
            'id' => $this->id,
            'hand' => $this->hand,
        ]);
        $this->unplayedCards = $this->intel->getUnplayedCards();
        $this->cardsPlayedThisRound = [];
        $this->cardPlayed = null;
        $this->playersVoidInSuit = [[],[],[],[]];
        $this->roundScores = [0,0,0,0];
        $this->analyzeHand(!$isHoldHand);
        if ($this->handStrategy === 'shootTheMoon') {
            print $this->name . ' says I shall try to shoot the moon'."\n";
            $this->selector = new ShootTheMoonSelector(['handAnalysis' => $this->handAnalysis]);
        } else {
            // $this->selector = new DefaultSelector([]);
        }
    }

    public function showHand($showHand = true)
    {
        $s = $this->name;
        if ($this->cardPlayed) {
            $s .= ' plays the ' . $this->cardPlayed->getDisplay();
            $this->writeln($s);
            $this->writeln('');
        }
        if ($showHand) {
            $this->hand->show("x Cards for $s:");
        }
    }

    public function showAnalysis()
    {
        $this->selector->showAnalysis();
    }

    public function report()
    {
        $this->writeln($this->name . ' has ' . $this->myScore . ' points.');
    }

    public function playCard($cardsPlayedThisTrick, $isBrokenHearts, $isFirstTrick)
    {
        $this->trickStrategy = 'highestNoTake';
        if (empty($cardsPlayedThisTrick)) {
            if ($isFirstTrick && $this->hasCard(0)) {
                $cardToPlayIdx = 0;
            } else {
                $eligibleCards = $this->hand->getEligibleLeadCards($isBrokenHearts);
                $cardToPlayIdx = $this->selectLeadCard($eligibleCards, $isFirstTrick);
            }
        } else {
            $this->unplayedCards = $this->intel->removeFromUnplayed($cardsPlayedThisTrick);
            $suit = array_values($cardsPlayedThisTrick)[0]->getSuit();
            $eligibleCards = $this->hand->getEligibleCards($suit, $isFirstTrick);
            $cardToPlayIdx = $this->selectCard($eligibleCards, $isFirstTrick, $cardsPlayedThisTrick);
        }

        return $this->cardPlayed = $this->hand->getCard($cardToPlayIdx);
    }

    public function getCardsToPass($dirLabel)
    {
        return $this->hand->getCardsToPass($this->selector->selectCardsToPass(['hand' => $this->hand->getCards(), 'gameScores' => $this->gameScores, 'strategy' => $this->handStrategy]));
    }

    public function receivePassedCards($c)
    {
        $this->hand->addCards($c);
    }

    public function analyzeHand($passingWillHappen = false)
    {
        if (array_sum($this->roundScores) > 25 || count($this->hand->getCards()) < 2) {
            return;
        }
        $newHandStrategy = null;
        $numPlayersWithPoints = 0;
        $iHavePoints = false;
        $totalPoints = 0;
        foreach ($this->roundScores as $idx => $roundScore) {
            if ($roundScore) {
                $numPlayersWithPoints++;
                $totalPoints += $roundScore;
                if ($idx === $this->id) {
                    $iHavePoints = true;
                }
            }
        }
        if ($numPlayersWithPoints > 1) {
            $newHandStrategy = 'avoidPoints';
        }
        if (is_null($newHandStrategy)) {
            $newHandStrategy = $this->selector->getRoundStrategy([
                'cards' => $this->hand->getCards(),
                'noPassing' => !$passingWillHappen,
                'gameScores' => $this->gameScores,
                'roundScores' => $this->roundScores,
                'riskTolerance' => $this->riskTolerance,
                'unplayedCards' => $this->unplayedCards,
                'myId' => $this->id,
                'iHavePoints' => $iHavePoints,
                'numPlayersWithPoints' => $numPlayersWithPoints,
                'totalPoints' => $totalPoints,
            ]);
            $this->writeln($this->name . ' strategy: ' . $newHandStrategy);
        }
        $this->handAnalysis = $this->selector->getAnalysis();
        if ($this->handStrategy !== $newHandStrategy) {
            if (!is_null($this->handStrategy)) {
                $this->writeln($this->name . ' says I shall change my strategy to ' . $newHandStrategy);
            }
            $this->handStrategy = $newHandStrategy;
            $this->selector = $this->getSelector();
        }
    }

    protected function getSelector()
    {
        switch ($this->handStrategy) {
            case 'shootTheMoon':
                return new ShootTheMoonSelector(['handAnalysis' => $this->handAnalysis]);
            case 'defendAgainstShoot':
                return new DefendAgainstShootSelector(['handAnalysis' => $this->handAnalysis]);
            default:
                return new DefaultSelector(['handAnalysis' => $this->handAnalysis]);
        }
    }

    protected function selectCard($eligibleCards, $isFirstTrick, $cardsPlayedThisTrick)
    {
        return $this->selector->selectCard([
            'riskTolerance' => $this->riskTolerance,
            'eligibleCards' => $eligibleCards,
            'allCards' => $this->hand->getCards(),
            'unplayedCards' => $this->unplayedCards,
            'isFirstTrick' => $isFirstTrick,
            'cardsPlayedThisTrick' => $cardsPlayedThisTrick,
            'handStrategy' => $this->handStrategy,
            'trickStrategy' => $this->trickStrategy,
            'cardsPlayedThisRound' => $this->cardsPlayedThisRound,
            'playersVoidInSuit' => $this->playersVoidInSuit,
            'yetToPlay' => array_values(array_diff(array_diff([1,2,3,4], array_keys($cardsPlayedThisTrick)), [$this->id])),
        ]);
    }

    protected function selectLeadCard($eligibleCards, $isFirstTrick)
    {
        return $this->selector->selectLeadCard([
            'riskTolerance' => $this->riskTolerance,
            'eligibleCards' => $eligibleCards,
            'allCards' => $this->hand->getCards(),
            'unplayedCards' => $this->unplayedCards,
            'isFirstTrick' => $isFirstTrick,
            'handStrategy' => $this->handStrategy,
            'trickStrategy' => $this->trickStrategy,
            'cardsPlayedThisTrick' => [],
            'cardsPlayedThisRound' => $this->cardsPlayedThisRound,
            'playersVoidInSuit' => $this->playersVoidInSuit,
            'yetToPlay' => array_values(array_diff([1,2,3,4], [$this->id])),
        ]);
    }

    public function gatherInfo($info)
    {
        // most of the info beloe comes back in $data and will need to be unpacked
        // note analyzeHand also selects strategy
        $data = $this->intel->gatherInfo($info);
        $this->unplayedCards = $data['unplayedCards'];
        $this->gameScores = $data['gameScores'];
        $this->roundScores = $data['roundScores'];
        $this->playersVoidInSuit = $data['playersVoidInSuit'];
        $this->cardsPlayedThisRound = $data['cardsPlayedThisRound'];
        $this->analyzeHand();
        $this->cardPlayed = null;
        $this->showHand();

        // $leadSuit = null;
        // $topValue = null;
        // $this->gameScores = !empty($info['gameScores']) ? $info['gameScores'] : $this->gameScores;
        // if (!empty($info['gameScores'])) {
        //     foreach ($this->gameScores as $id => $score) {
        //         $this->roundScores[$id] = 0;
        //     }
        // }

        // $this->unplayedCards = $this->removeFromUnplayed($info['cardsPlayed']);

        // foreach ($info['cardsPlayed'] as $id => $c) {
        //     if (empty($this->cardsPlayedThisRound[$id])) {
        //         $this->cardsPlayedThisRound[$id] = [];
        //     }
        //     $this->cardsPlayedThisRound[$id][] = $c;
        //     if (is_null($leadSuit)) {
        //         $leadSuit = $c->getSuit();
        //         $topValue = $c->getValue();
        //         $takesTrick = $id;
        //         $points = 0;
        //     }
        //     $suit = $c->getSuit();
        //     $value = $c->getValue();
        //     if ($suit === 2) {
        //         $points++;
        //     }
        //     if ($suit === 3 && $value == 10) {
        //         $points += 13;
        //     }
        //     if ($suit === $leadSuit && $value > $topValue) {
        //         $takesTrick = $id;
        //         $topValue = $value;
        //     }
        //     if ($suit !== $leadSuit && !in_array($id, $this->playersVoidInSuit[$leadSuit])) {
        //         $this->playersVoidInSuit[$leadSuit][] = $id;
        //     }
        // }
        // $this->gameScores[$takesTrick] += $points;
        // $this->roundScores[$takesTrick] += $points;
        // $this->analyzeHand();
        // if ($this->handStrategy === 'shootTheMoon' && $points && $takesTrick !== $this->id) {
        //     $this->handStrategy = 'avoidPoints';
        //     print $this->name . ' says I shall no longer shoot the moon'."\n";
        //     $this->selector = new DefaultSelector(['handAnalysis' => $this->handAnalysis]);
        // }
        // $this->cardPlayed = null;
        // $this->showHand();
    }

    // protected function getUnplayedCards()
    // {
    //     $allCards = [
    //         [0,1,2,3,4,5,6,7,8,9,10,11,12],
    //         [0,1,2,3,4,5,6,7,8,9,10,11,12],
    //         [0,1,2,3,4,5,6,7,8,9,10,11,12],
    //         [0,1,2,3,4,5,6,7,8,9,10,11,12],
    //     ];
    //     $playedCards = [[],[],[],[],];
    //     foreach ($this->hand->getCards() as $c) {
    //         $playedCards[$c->getSuit()][] = $c->getValue();
    //     }

    //     $unplayedCards = [];

    //     for ($suit=0; $suit<4; $suit++) {
    //         $unplayedCards[$suit] = array_values(array_diff($allCards[$suit], $playedCards[$suit]));
    //     }

    //     return $unplayedCards;
    // }

    // protected function removeFromUnplayed($cardsPlayedThisTrick)
    // {
    //     $playedCards = [[],[],[],[],];
    //     foreach ($cardsPlayedThisTrick as $c) {
    //         $playedCards[$c->getSuit()][] = $c->getValue();
    //     }

    //     $unplayedCards = [];

    //     for ($suit=0; $suit<4; $suit++) {
    //         $unplayedCards[$suit] = array_values(array_diff($this->unplayedCards[$suit], $playedCards[$suit]));
    //     }

    //     return $unplayedCards;
    // }

    public function hasCards()
    {
        return $this->hand->hasCards();
    }

    public function hasCard($cardIdx)
    {
        return $this->hand->hasCard($cardIdx);
    }

    public function addPoints($pts)
    {
        $this->myScore += $pts;
    }

    public function getMyScore()
    {
        return $this->myScore;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}

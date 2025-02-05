<?php

namespace App\Cards;

class Deck extends BaseProcess {
    protected $cards = [],
    $deck,
    $card,
    $suits = ['♣', '♦', '♥', '♠'],
    $faces = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'],
    $preShuffledDeck;

    public function __construct($parameters = [])
    {
        $this->preShuffledDeck = empty($parameters['preShuffledDeck']) ? null : $parameters['preShuffledDeck'];
        if (!empty($this->preShuffledDeck)) {
            foreach ($this->preShuffledDeck as $playerId => $arr) {
                foreach ($arr as $cardIdx) {
                    $this->cards[] = new Card($cardIdx);
                }
            }
            $this->shuffle();
        } else {
            for ($i = 0; $i < 52; $i++) {
                $this->cards[] = new Card($i);
            }
            $this->shuffle();
        }
    }

    public function shuffle()
    {
        $this->deck = $this->cards;
    }

    protected function draw()
    {
        if (!empty($this->preShuffledDeck)) {
            $this->card = array_shift($this->deck);
        } else {
            $cardIdx = rand(0, count($this->deck) - 1);
            $this->card = array_splice($this->deck, $cardIdx, 1)[0];
        }
    }

    public function deal($num = 1)
    {
        $hand = [];
        for ($i = 1; $i <= $num; $i++) {
            $this->draw();
            $hand[] = $this->card;
        }
        return $hand;
    }
}

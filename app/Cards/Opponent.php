<?php

namespace App\Cards;

class Opponent extends BaseProcess {
    protected $id;
    protected $score;
    protected $voidSuits = [];
    protected $cardsHeldProbablility = [];

    public function __construct($data = null)
    {
        $this->id = $data['id'];
        $this->score = 0;
    }

    public function addPoints($pts)
    {
        $this->score += $pts;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function getId()
    {
        return $this->id;
    }
}

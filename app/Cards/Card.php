<?php

namespace App\Cards;

class Card  extends BaseProcess{
    protected $suit;
    protected $value;
    protected $sortOrder;
    protected $idx;
    protected static $suits = ['♣', '♦', '♥', '♠'];
    protected static $faces = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];

    public function __construct($idx)
    {
        $this->idx = $idx;
        $this->suit = $idx % 4;
        $this->value = floor($idx / 4);
        $this->sortOrder = 13 * $this->suit + $this->value;
    }

    public function getDisplay()
    {
        return self::$faces[$this->value] . self::$suits[$this->suit];
    }

    public function getSuit()
    {
        return $this->suit;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getIdx()
    {
        return $this->idx;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function getDanger()
    {
        switch ($this->suit) {
            case 2:
                return 13 + $this->value;
            case 3:
                if ($this->value == 10) {
                    return 100;
                }
                if ($this->value > 10) {
                    return 90;
                }
                return 0.1 * $this->value;
            default:
                return $this->value;
        }
    }

    public static function getIdxFromDisplay($display)
    {
        $suit = mb_substr($display, -1);
        $suitIdx = array_search($suit, self::$suits);
        $value = mb_substr($display, 0, -1);
        $valueIdx = array_search($value, self::$faces);

        return $suitIdx + 4 * $valueIdx;
    }

    public static function getDisplayFromIdx($idx)
    {
        return self::$faces[floor($idx / 4)] . self::$suits[$idx % 4];
    }
}

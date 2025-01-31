<?php

namespace App\Cards;

class ReadPreShuffledFile extends BaseProcess {
    protected $file;
    protected $outputData = [];

    public function __construct($params)
    {
        $this->file = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['..', '..']) . DIRECTORY_SEPARATOR . $params['file'];
    }

    public function execute()
    {
        print "\n\n";
        $fileContents = json_decode(file_get_contents($this->file), true);
        foreach ($fileContents as $round) {
            foreach ($round as $hand) {
                foreach ($hand as $cardIdx) {
                    $dsp = Card::getDisplayFromIdx($cardIdx);
                    print "$dsp ";
                }
                print "\n";
            }
            print "\n\n";
        }
    }
}

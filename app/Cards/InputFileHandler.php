<?php

namespace App\Cards;

class InputFileHandler extends BaseProcess {
    protected $file;
    protected $fileContents = [];
    protected $handCount = 0;

    public function __construct($params)
    {
        if (!empty($params['file'])) {
            $this->file = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['..', '..']) . DIRECTORY_SEPARATOR . $params['file'];
            $this->fileContents = json_decode(file_get_contents($this->file), true);
        }
    }

    public function getHand($handCount)
    {
        $fileContents = $this->fileContents;
        if (empty($fileContents) || empty($fileContents[$handCount])) {
            $this->writeln('no deck for ' . $handCount);
            return null;
        }

        $this->writeln("deck for $handCount:");
        $this->writeln($fileContents[$handCount]);
        return $fileContents[$handCount];
    }
}

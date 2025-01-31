<?php

namespace App\Cards;

class CreatePreShuffledFile extends BaseProcess {
    protected $file;
    protected $outputFile;
    protected $outputData = [];

    public function __construct($params)
    {
        $this->file = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['..', '..']) . DIRECTORY_SEPARATOR . $params['file'];
        $this->outputFile =  __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['..', '..']) . DIRECTORY_SEPARATOR . $params['output-file'];
    }

    public function execute()
    {
        $fileContents = file($this->file);
        $hand = [];
        foreach ($fileContents as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $processedLine = $this->processLine($line);
            if (empty($processedLine)) {
                continue;
            }
            $hand[] = $processedLine;
            if (count($hand) > 3) {
                if (!$this->check($hand)) {
                    throw new \Exception('line '.$line.' fails the check');
                }
                $this->outputData[] = $hand;
                $hand = [];
            }
        }

        file_put_contents($this->outputFile, json_encode($this->outputData));
    }

    protected function processLine($line)
    {
        $this->writeln('now processing'. $line);
        // does the string contain a colon? throw it away with everything before it
        $pos = strpos($line, ':');
        if ($pos !== false) {
            $originalLine = $line;
            $line = substr($line, $pos + 1);
        }
        $line = str_replace("|", "", $line);
        $a = explode(" ", $line);
        $ret = [];
        foreach ($a as $b) {
            $c = trim($b);
            if (!empty($c)) {
                $this->writeln($c);
                $ret[] = Card::getIdxFromDisplay($c);
            }
        }

        if (count($ret) !== 13) {
            return null;
        }
        return $ret;
    }

    protected function check($deal)
    {
        $cIdxs = [];
        foreach($deal as $hand) {
            foreach ($hand as $cIdx) {
                if (in_array($cIdx, $cIdxs)) {
                    print "$cIdx appears more than once\n";
                    return false;
                }
                $cIdxs[] = $cIdx;
            }
        }

        return true;
    }
}

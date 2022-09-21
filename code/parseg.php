#!/usr/bin/php
<?php

# http://www.perseus.tufts.edu/hopper/text?doc=Perseus%3atext%3a1999.01.0155%3abook%3dMatthew

require "betaGr.inc.php";
$cnv = new CBetaGr;

class CFile
{
    private $of = false;

    function __destruct() {
        $this->close();
    }
    private function close()
    {
         if($this->of)
            fclose($this->of);
    }
    public function openFile($ofn)
    {
        print "$ofn\n";
        $this->close();
        $this->of = fopen($ofn, "w") or die("Unable to open file!");;
    }
    public function write($text)
    {
        if($this->of)
            fprintf($this->of, '%s', $text);
    }
}

class CEventHandler
{
    private $of;
    private $fortune;
    private $num = 0;
    private $head;
    private $book;
    private $chapter;
    private $verse;
    private $line = '';
    function __construct()
    {
        $this->of = new CFile;
        $this->fortune = new CFile;
        $this->fortune->openFile('fortune');
    }
    public function addLine($text)
    {
        $this->line .= $text;
    }
    public function newHead($text)
    {
        $this->head = $text;
    }
    public function newBook($text)
    {
        $this->writeVerse();
        $ofn = sprintf("%02d_", ++$this->num) . strtr($text, " ", "_") . '.txt';
#        $this->of->openFile($ofn);
    }
    public function newChapter($text)
    {
        $this->writeVerse();
        $this->chapter = $text;
    }
    public function newVerse($text)
    {
        $this->writeVerse();
        if($text === '1' && $this->chapter !== '1')
            $this->of->write("\n");
        $this->verse = $text;
    }
    public function writeVerse()
    {
        $this->line = preg_replace('/[\s]+/', ' ', $this->line);
        $this->line = trim($this->line);
        if($this->line === '') return;
        $this->of->write("$this->chapter $this->verse\t $this->line\n");
        $this->fortune->write("$this->line\n\t$this->head $this->chapter $this->verse\n%\n");
        $this->line = '';
    }
}

function doFile($filename)
{
#    echo "$filename\n";
    global $cnv;
    $x = new XMLReader;
    $x->open($filename);
    $of = new CEventHandler;

    while($x->read()) {
        if($x->name === 'body')
            break;
    }
    while($x->read()) {
        $n = $x->name;
        if($n === 'pb' || $n === 'p' || $n === 'l' || $n === 'q') continue;
        if($n === 'quote') {
            $of->addLine('"'); continue;
        }
        if($x->nodeType === XMLReader::END_ELEMENT) continue;
        if($n === 'head') {
            $x->read();
            $s = $x->readString();
            $of->newHead($cnv->decode($s));
        } elseif($n === 'div1' && $x->getAttribute('type') === 'Book') {
            $of->newBook($x->getAttribute('n'));
        } elseif($n === 'milestone') {
            $unit = $x->getAttribute('unit');
            $number = $x->getAttribute('n');
            if($unit === 'chapter') {
                $of->newChapter($number);
            } elseif($unit === 'verse') {
                $of->newVerse($number);
            }
        } elseif($n === '#text') {
            $s = $x->readString();
            $of->addLine($cnv->decode($s));
        }
        else var_dump($n);
    }
    $x->close();
    $of->writeVerse();
}

if($argc < 2) {
    doFile('php://stdin');
} else {
    for($i=1; $i<$argc; $i++)
        doFile($argv[$i]);
}

#system("fold -s -w68 fortune > ~/fortune/bible_greek", $r);
#system("strfile ~/fortune/bible_greek", $r);

?>

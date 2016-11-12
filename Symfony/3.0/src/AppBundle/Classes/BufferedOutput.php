<?php
namespace AppBundle\Classes;

use Symfony\Component\Console\Output\Output;

class BufferedOutput extends Output
{
    private $buffer;

    public function doWrite($message, $newline)
    {
        $this->buffer .= $message. ($newline? PHP_EOL: '');
    }

    public function getBuffer()
    {
        return $this->buffer;
    }
}

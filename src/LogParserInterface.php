<?php

namespace CloudReader;

interface LogParserInterface {

    /**
     * Parse the give log message
     *
     * @param string $message
     * @access public
     * @return mixed
     */
    public function parse(string $message);
}

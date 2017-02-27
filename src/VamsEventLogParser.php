<?php

namespace CloudReader;

class VamsEventLogParser implements LogParserInterface {

    public function parse(string $message) {
        return json_decode($message);
    }
}

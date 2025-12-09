<?php
namespace Sync\Utils;

class Logger {
    public function __construct(private string $path) {}

    public function info(string $msg, array $ctx = []): void {
        $this->write("INFO", $msg, $ctx);
    }

    public function warn(string $msg, array $ctx = []): void {
        $this->write("WARN", $msg, $ctx);
    }

    public function error(string $msg, array $ctx = []): void {
        $this->write("ERROR", $msg, $ctx);
    }

    private function write(string $level, string $msg, array $ctx): void {
        $line = sprintf(
            "[%s] %s %s %s\n",
            date('c'),
            $level,
            $msg,
            $ctx ? json_encode($ctx) : ""
        );
        file_put_contents($this->path, $line, FILE_APPEND);
    }
}

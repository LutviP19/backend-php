<?php

$ffi = FFI::cdef(
    "extern void HelloWorld(char* name);",
    __DIR__ . '/../bin/ffi/lib/hello.so',
);

function HelloWorld(string $name): void
{
    echo "Hello {$name}!", PHP_EOL;
}

$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $ffi->HelloWorld("Dominik");
}
$end = microtime(true);

$timeGo = $end - $start;

$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    HelloWorld("Dominik");
}
$end = microtime(true);
$timePhp =  $end - $start;

echo "Go version took {$timeGo} seconds.", PHP_EOL;
echo "PHP version took {$timePhp} seconds.", PHP_EOL;

// $ffi = FFI::cdef("int MyGoFunction(int a, int b);", __DIR__ . "/../bin/ffi/lib/mylib.so");
//     $result = $ffi->MyGoFunction(10, 20);
//     echo "Result from Go function: " . $result . PHP_EOL;; // Output: 30

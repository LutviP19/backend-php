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


// Fobonacci
$ffi1 = FFI::cdef(
    "int Fibonacci(int n);",
    __DIR__ . '/../bin/ffi/lib/fibonacci.so',
);

function fibonacci(int $n): int
{
    if ($n <= 1) {
        return $n;
    }

    return fibonacci($n - 1) + fibonacci($n - 2);
}

$start = microtime(true);
$result = $ffi1->Fibonacci(35);
$end = microtime(true);
$time = $end - $start;

echo "Go Fibonacci result: {$result}. It took {$time} seconds to compute.", PHP_EOL;


$start = microtime(true);
$result = fibonacci(35);
$end = microtime(true);
$time = $end - $start;

echo "PHP Fibonacci result: {$result}. It took {$time} seconds to compute.", PHP_EOL;


// // Simple function
// // unset($result);
// $ffi2 = FFI::cdef("int MyGoFunction(int a, int b);", __DIR__ . "/../bin/ffi/lib/mylib.so");
// $result = $ffi2->MyGoFunction(10, 80);
// echo "Result from Go function: " . $result . PHP_EOL;; // Output: 30

// Concurrent process
$ffi3 = FFI::cdef(
    // "extern void ResizeImages(char** input, int count, char*** failedOut, int* failedCount);",
    file_get_contents(__DIR__ . '/../bin/ffi/lib/concurrency.h'),
    __DIR__ . '/../bin/ffi/lib/concurrency.so',
);

$imagePaths = [
    "pathA",
    "pathB",
    "pathC",
    "pathD",
];
$imagesCount = count($imagePaths);

$cArray = FFI::new("char*[" . count($imagePaths) . "]"); // create a new array with fixed size
$buffers = []; // this will just hold variables to prevent PHP's garbage collection

foreach ($imagePaths as $i => $path) {
    $size = strlen($path); // the size to allocate in bytes
    $buffer = FFI::new("char[" . ($size + 1) . "]"); // create a new C string of length +1 to add space for null terminator
    FFI::memcpy($buffer, $path, $size); // copy the content of $path to memory at $buffer with size $size
    $cArray[$i] = FFI::cast("char*", $buffer); // cast it to a C char*, aka a string
    $buffers[] = $buffer; // assigning it to the $buffers array ensures it doesn't go out of scope and PHP cannot garbage collect it
}

$failedOut = FFI::new("char**"); // create a string array in C, this will be passed as reference
$failedCount = FFI::new("int"); // create an integer which will be passed as reference

$start = microtime(true);
$ffi3->ResizeImages(
    $cArray,
    count($imagePaths),
    FFI::addr($failedOut),
    FFI::addr($failedCount),
);
$end = microtime(true);
$time = $end - $start;

$count = $failedCount->cdata; // fetch the count of failed items

echo "Failed items: {$count}", PHP_EOL;
for ($i = 0; $i < $count; $i++) {
    echo " - ", FFI::string($failedOut[$i]), PHP_EOL; // cast each item to a php string and print it
    // FFI::free($failedOut[$i]); // free each string after use
}
// FFI::free($failedOut); // finally free the array itself

echo "Processing took: {$time} seconds", PHP_EOL;



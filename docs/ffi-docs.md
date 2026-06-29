
## Sample FFI:
---
```bash
# Build shared lib
go build -buildmode=c-shared -o ./bin/ffi/lib/concurrency.so ./bin/ffi/source/concurrency.go
go build -buildmode=c-shared -o ./bin/ffi/lib/fibonacci.so ./bin/ffi/source/fibonacci.go 
go build -buildmode=c-shared -o ./bin/ffi/lib/hello.so ./bin/ffi/source/hello.go 

# Modify header
file : bin/ffi/lib/concurrency.h
# change to this code
extern void ResizeImages(char** input, int count, char*** failedOut, int* failedCount);

# Run test
php cron/test.php 
```
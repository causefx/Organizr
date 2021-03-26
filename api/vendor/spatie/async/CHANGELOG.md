# Changelog

All notable changes to `async` will be documented in this file

## 1.5.2 - 2020-11-20

- Configure task in synchronous process
- Add Pool::forceSynchronous function

## 1.5.1 - 2020-11-20

- Support for PHP 8

## 1.5.0 - 2020-09-18

- Add fallback to SerializableException to handle "complex" exceptions (#119)

## 1.4.1 - 2020-08-19

- Properly stop process on timeout (#105)

## 1.4.0 - 2020-04-15

- Make binary configurable (#111 and #112)

## 1.3.0 - 2020-03-17

- Support microsecond timeouts (#109)

## 1.2.0 - 2020-02-14

- Add ability to stop the pool early (#56)

## 1.1.1 - 2019-12-24

- allow Symfony 5 components

## 1.1.0 - 2019-09-30

- Make output length configurable (#86)

## 1.0.4 - 2019-08-02

- Fix for `SynchronousProcess::resolveErrorOutput` (#73)

## 1.0.3 - 2019-07-22

- Fix for Symfony Process argument deprecation

## 1.0.1 - 2019-05-17

- Synchronous execution time bugfix

## 1.0.1 - 2019-05-07

- Check on PCNTL support before registering listeners

## 1.0.0 - 2019-03-22

- First stable release
- Add the ability to catch exceptions by type
- Thrown errors can only have one handler. 
See [UPGRADING](./UPGRADING.md#100) for more information.

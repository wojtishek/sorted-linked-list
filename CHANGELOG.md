# Changelog

## 1.0.0 (2026-05-07)


### Features

* add abstract base and empty-list behaviour for sorted linked list ([15c744b](https://github.com/wojtishek/sorted-linked-list/commit/15c744b53b1806efcba73f6637846faeb4c04389))
* add contains() with early-exit search ([5fa0f63](https://github.com/wojtishek/sorted-linked-list/commit/5fa0f6377905f3b2353719de7df9712fc372dac5))
* add exception hierarchy with marker interface ([a302408](https://github.com/wojtishek/sorted-linked-list/commit/a3024087fee5eaca7ee64642d43ee6f9ed3bb105))
* add fromArray factory with type validation ([1129caa](https://github.com/wojtishek/sorted-linked-list/commit/1129caadd4ad6b66c5254bbe7c686a0bd1299f1a))
* add internal Node value object ([261c980](https://github.com/wojtishek/sorted-linked-list/commit/261c980a1d59f7e2349113996f5ef49494f63251))
* add remove() with tail pointer maintenance ([222a2c5](https://github.com/wojtishek/sorted-linked-list/commit/222a2c541f178a2aec818a7a6f809e5d75329d28))
* add sorted insert with head/tail pointer maintenance ([d05f10d](https://github.com/wojtishek/sorted-linked-list/commit/d05f10d15f9f507699a42193d7876f2c1def7840))
* add StringSortedLinkedList with byte-wise ordering ([fe35342](https://github.com/wojtishek/sorted-linked-list/commit/fe35342c3221c06b7945bc75a0ff4722068e7cee))
* deep-copy node chain on clone ([bffbbb8](https://github.com/wojtishek/sorted-linked-list/commit/bffbbb8ab1e771b84682274dec97c47bf83a9804))
* narrow return types on IntSortedLinkedList concrete API ([46d5d4f](https://github.com/wojtishek/sorted-linked-list/commit/46d5d4f6b050f7388371e9a53d793eb2adf7beb6))


### Bug Fixes

* remove abstract add() and narrow IntSortedLinkedList::add to int ([2279213](https://github.com/wojtishek/sorted-linked-list/commit/2279213c6d341a2a6575f3b58115f081a69ebb30))


### Refactoring

* remove unused fromValues test factory ([df897f4](https://github.com/wojtishek/sorted-linked-list/commit/df897f430bcc4e2cc7bd879d41da4e4f7542b820))


### Documentation

* add MIT license ([1699308](https://github.com/wojtishek/sorted-linked-list/commit/16993081ddf3c6b6cbe44ef2dfa714639f4f95b2))
* add README with usage, API table, design decisions, and limitations ([c39efff](https://github.com/wojtishek/sorted-linked-list/commit/c39efff7ff67cb7d3374dd516f7a9c0a9850f87b))
* show isEmpty() guard as primary empty-list pattern ([8f3c978](https://github.com/wojtishek/sorted-linked-list/commit/8f3c978d4af6ecb77591a72709df138e7b908606))

## Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This file is maintained automatically by
[release-please](https://github.com/googleapis/release-please) — entries are
generated from [Conventional Commits](https://www.conventionalcommits.org/) on
the `main` branch.

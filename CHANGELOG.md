# Changelog

## x.y.z - UNRELEASED

--------

## 0.9.1 - 2025-05-13

### Added

* Automated docker image builds, no code changes.

--------

## 0.9.0 - 2024-12-19

### Changed

* [Support] Added support for PHP 8.3 and 8.3.
* [Support] Dropped support for PHP 7.2, 7.3, and 7.4.

--------

## 0.8.1 - 2023-04-30

### Changed

* [Support] Require version of the Guzzle PSR library.

--------

## 0.8.0 - 2023-04-30

### Changed

* [Support] Added support for PHP 8.2.
* [Support] Added support for Guzzle 7.

--------

## 0.7.0 - 2022-09-07

### Changed

* [Support] Added support for PHP 8.0 and 8.1.

--------

## 0.6.0 - 2020-06-10

### Added

* [PullRequest] Added a `getBaseBranch()` method.

--------

## 0.5.0 - 2019-11-28

### Added

* [Repository] Added a `getPullRequests()` method to get PRs.
* [PullRequest] Added a `getLabels()` method to get labels attached to a PR.
* [PullRequest] Added a `getBranch()` method.
* [PullRequest] Added a `getMergeableState()` method.

### Changed

* [PullRequest] Dropped the ApiInterface parameter from the constructor.

--------

## 0.4.0 - 2019-11-21

### Added

* [Api] Added a `TokenApi` class for use with an existing GitHub token (eg via GitHub actions).

--------

## 0.3.0 - 2019-09-22

### Added

* [Repository] Added an isArchived() method.
* [Api] Added support for PATCH requests.
* [Api] Cache tokens for improved performance.
* [Api] Allow responses to be cached by passing a PSR compatible cache to the Api constructor.

### Changed

* [Support] Added support for PHP 7.3.
* [Support] Dropped support for PHP 7.1.

### Fixed

* [Http] Correctly handle responses with empty bodies.

--------

## 0.2.0 - 2018-11-27

### Added

* [Repository] Added isPrivate() and isPublic() methods.
* [Repository] Added getDefaultBranch() method.
* [Repository] Added getDescription() method.
* [Repository] Added isFork() method.
* [Tag] Added tag objects, and a Repository::getTags() method.
* [File] Ensure invalid base64 characters throw an exception.

--------

## 0.1.0 - 2018-07-15

### Added

* First release with basic features for interacting with repositories.

--------

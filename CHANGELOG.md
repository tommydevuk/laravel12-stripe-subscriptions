# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

- work in progress

## 2026-03-06

### Added

- `billing:stripe-sync` Artisan command for importing Stripe resources.

### Fixed

- Command now skips missing tables instead of crashing.
- Duplicate output removed from sync phases.
- Observer syntax corrected (StripeSyncState check).

### Tests

- New feature test for sync command using in-memory sqlite.

### Documentation

- Updated README with CLI instructions and changelog section.

## Previous milestones

- Initial DDD billing implementation with webhook handling, subscription management, and packaging refactor.

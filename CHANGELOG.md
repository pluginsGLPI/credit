# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Fixed

- Improve consumed credits modal readability and open related tickets in a new tab
- Show credit vouchers list in entity tab for read-only users while keeping add/config forms restricted to editable contexts.
- Centralize credit consumption validation, prevent invalid voucher selections outside the validity window, and fix the ticket tab quantity field behavior.

## [1.15.2] - 2025-12-22

### Fixed

- Fix SQL error for `lowcredits` task

## [1.15.1] - 2025-11-18

### Fixed

- Fix the maximum consumable quantity value displayed in the tooltip

## [1.15.0] - 2025-09-29

### Added

- GLPI 11 compatibility

## [1.14.1] - 2025-06-25

### Fixed

- Fix `Entity` tab

## [1.14.0] - 2024-06-25

### Added

- Add low credit notification (#131)

## [1.13.2] - 2024-02-20

### Fixed

- Restores values when ticket is reloaded during creation (e.g. category update)

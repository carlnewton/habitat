# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- [#119] Opengraph meta tags

### Fixed

- The updated datetime gets modified when editing a post

## [1.2.0] - 2026-03-21

### Added

- [#105] Change password settings
- Setup login screen
- [#111] Reset admin password console command

### Changed

- The administrator must maintain a logged in session to continue setup
- [#101] Send test email process is clearer in both setup and admin pages
- Removed conditions which warn about no encryption key - the encryption key is mandatory
- Further translations added to the mail administration page

### Fixed

- Corrected mistakes in translation files

## [1.1.0] - 2026-03-15

### Added

- [#14] Italian translation file
- [#14] Language selection in setup and admin area
- [#85] A map on the about page

### Changed

- The about page links to gethabitat.org

### Fixed

- [#95] Moderation filters are working

## [1.0.2] - 2026-03-02

### Fixed

- [#86] SMTP settings can be saved and used without credentials

## [1.0.1] - 2026-03-01

### Fixed

- Unbreak all javascript

## [1.0.0] - 2026-03-01

### Added

- Habitat specification of location and size - enabling posts related to the local area
- Home feed - Displays the most recent posts
- Nearby feed - Displays posts sorted by proximity to the user
- Create posts - Upload photos, set locations, comments
- Categories - Location rules
- Amazon S3 image storage option
- Personalisation - Overrides Habitat defaults per user: kms/miles, hidden categories
- Moderation tools - User, post, comment moderation, block email addresses
- Announcements - Scheduled announcements
- Public moderation log - Keep moderator actions visible for 30 days

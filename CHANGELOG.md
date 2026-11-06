# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.1.0 — 2026-11-06

First public release, extracted from the iHRMIS application.

### Added

- `PdsGenerator` facade and `Renderer\PdsPdfRenderer` for the four-page CS Form
  212 (Personal Data Sheet), drawing the bundled templates at
  `resources/templates/page1.pdf` … `page4.pdf`.
- `Config\PdsLayout` with every legacy coordinate, the table geometry measured
  from the real forms, and `legacyIhrmis()` / `fromDirectory()` / bundled
  template helpers; `Config\PdsOptions` for the print switches.
- Typed models for all ten sections (`Model\Pds`, `PersonalInfo`,
  `FamilyBackground`, `Child`, `EducationEntry`, `Eligibility`,
  `WorkExperience`, `VolunteerWork`, `Training`, `OtherInfo`, `Reference`) and
  the `Contract\PdsRepositoryInterface` + `Adapter\ArrayPdsRepository` read seam.
- `Service\PdsEditor` — the write side: ten save methods mirroring the legacy
  entry screens, with per-field validation, blank-row handling and the employee
  record mirror.
- `Config\PdsTables` for table-name overrides, with `Adapter\PdoPdsStore` and
  `Adapter\ArrayPdsStore`, plus `Model\PdsProfile` and
  `Model\ServiceRecordEntry`.
- Runnable examples: `examples/generate-sample.php` (no database) and
  `examples/write-sample.php` (SQLite schema included).

### Notes

- The `version` field in `composer.json` is intentional: the iHRMIS project
  installs this package through a path repository that needs a `0.1.x` version.
  It must be bumped together with the git tag on every release.

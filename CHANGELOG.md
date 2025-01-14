# Release Notes for Optimum

## 2.3.1 - 2025-01-10
### Fixed
- Fix regression from 2.3.0: Method C was always returning the original variant [#11](https://github.com/matfish2/craft-optimum/issues/11)

## 2.3.0 - 2024-11-13
### Improved
- Added Population Segment support

## 2.2.3 - 2024-10-11
### Fixed
- Lowercase in mixpanel tracking code

## 2.2.2 - 2024-10-09
### Fixed
- When experiment is not active, return original variant instead of not setting the cookie 
- When experiment is not active, do not fire event

## 2.2.1 - 2024-09-21
### Improved
- Add `trackingPlatform` config setting (currently supports `mixpanel` and `ga4`)

## 2.2.0 - 2024-09-21
### Improved
- Pass Experiment and Variant objects to `fireEvent` closure to allow for more complex tracking logic
### Changed
- Removed redundant files

## 2.1.0 - 2024-09-20
> [!IMPORTANT]  
> Breaking Change. If you upgrade to this version, you will need to add an explicit call to `optimumFireEvent` in your twig template. See README.
### Improved
- Decouple from Google Analytics. Allow for custom tracking code
- `fireEvent` must be called manually to track events
- Refactor: move logic from twig token to Experiment record

## 2.0.2 - 2024-06-26
### Fixed
- Fix Postgres compatibility issues [(#9)](https://github.com/matfish2/craft-optimum/issues/9)

## 2.0.1 - 2024-05-30
### Fixed
- Remove `duration` from sortable columns [(#8)](https://github.com/matfish2/craft-optimum/issues/8)
## 2.0.0 - 2024-04-13
- Craft 5: Initial Release

## 1.3.0 - 2024-01-26
### Fixed
- Install Migration: Use dateTime()->notNull() instead of timestamp() [(#6)](https://github.com/matfish2/craft-optimum/issues/6)

## 1.2.2 - 2023-10-09
- Validate Unique Name and Handle [(#4)](https://github.com/matfish2/craft-optimum/issues/4)
- Hard Delete Experiment Element[(#4)](https://github.com/matfish2/craft-optimum/issues/4)
- Cascade delete to plugin tables[(#4)](https://github.com/matfish2/craft-optimum/issues/4)

## 1.2.1 - 2023-09-11
### Fixed
- Fix Install migration: invalid `enabled` default value [(#3)](https://github.com/matfish2/craft-optimum/issues/3)

## 1.2.0 - 2023-09-09
### Fixed
-  Ensure datetime consistency [(#2)](https://github.com/matfish2/craft-optimum/issues/2#issuecomment-1711444522)

## 1.1.4 - 2023-08-28
- Improve date validation
- Add clear date rules explanation if validation fails

## 1.1.3 - 2023-08-28
- Added datetime validation [(#2)](https://github.com/matfish2/craft-optimum/issues/2)
- Fixed issue which occurred when variants failed validation

## 1.1.2 - 2023-08-01
- Slight Clean up and refactor of token parser

## 1.1.1 - 2023-07-11
### Added
- Validate handle. Only allow alphanumeric + underscore

## 1.1.0 - 2023-07-11
### Improved
- Allow multiple experiments on the same page

## 1.0.2 - 2022-11-18
### Updated
- Remove delete button from original variant row
- Allow for changing the name of the original variant 

## 1.0.1 - 2022-11-17
### Fixed
- Remove CP setting

## 1.0.0 - 2022-11-15
- Initial Release
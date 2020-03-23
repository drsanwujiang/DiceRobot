# Changelog

## 1.2.0 - 2020-03-23

## Added
- Order now can begin with Chinese period (。)
- Sanity check (.sc)
- Change attributes (.hp, .mp, .san)
- Set character card attributes
- Record HP and MP when bind character card (.card)
- Show current and max HP/MP/SAN when check attributes/skills (.ra)
- New API `API::sanityCheck()` and `API::updateCharacterCard()`
- New exception `CharacterCardNotBoundException`

### Changed
- Move routes from `App->addRoutes()` to `routes.php`
- Merge `App` and `RouteCollector` class
- Separate methods and variables about response from `Parser` class, create new class `Response`
- `Parser` now extends `Response`
- `App` and `AbstractAction` now use `$this->parseEventData` to parse data
- `CharacterCard` now throws `CharacterCardLost` when fail to open character card file
- `CheckDice`, `SanCheck`, `AttributeChange` now throws `CharacterCardNotBoundException` when card ID is unset

### Removed
- Remove constructor of `Parser`

## 1.1.3 - 2020-02-21

### Fixed
- Fix bugs
- Optimize code

## 1.1.2 - 2020-02-19

### Added
- Repeat order .r

## 1.1.1 - 2020-02-19

### Added
- Repeat order (#times)

## 1.1.0 - 2020-02-19

### Added
- Bind character card (.card)
- Order .ra now supports attribute/skill name check

## 1.0.1 - 2020-02-17

### Added
- Order .ra now supports increase/decrease

### Fixed
- Fix a bug that would cause order .rab unresolved

### Changed
- Change attribute limit of order .ra

## 1.0.0 - 2020-02-14

### Added
- First release
- Supported functions: .r, .ra, .coc, .dnd, .set, .setcoc, .jrrp, .orz, .help, .hello
- Supported robot control functions: .robot start/stop, .robot nn

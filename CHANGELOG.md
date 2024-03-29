# Changelog


## 3.1.0 - 2021-09-25

### Added
- Resource APIs
- Panel APIs for data
- Authentication for panel connection
- Auto restart mechanism
- Put back card (.put)
- Group/friend list refresh logic

### Fixed
- Log service pattern
- New friend request handling logic

### Changed
- Restart logic

### Refactored
- Draw (.draw) logic
- Panel handler routing logic


## 3.0.2 - 2021-08-03

### Fixed
- Private message send logic
- Dice order parsing logic


## 3.0.1 - 2021-08-03

### Fixed
- Group state check logic


## 3.0.0 - 2021-08-02

### Added
- TRPG log APIs
- TRPG log (.log)
- Exception tests
- Message splitter
- Chinese order support
- Dicing order in deck item support
- Panel API
- Common reply variables
- Log service

### Fixed
- Some wordings
- Case sensitivity of some orders
- Some comments
- Some keys change
- Order bugs

### Changed
- DiceRobot API v3
- Heartbeat strategy
- Default chat settings
- Check strategy to empty chat settings
- Application status logic

### Refactored
- Long message split strategy
- Cache strategy
- Initialization strategy
- Robustness

### Removed
- Session and APIs


## 2.1.0 - 2020-12-01

### Added
- Order `maxReplyCharacter` (#33)
- Draw cards (.draw) (#31)
- Deck (.deck) (#32)
- Default chat settings
- Image fragment can be created from Mirai code with path
- Multiple replies
- Reply APIs
- Reply length control (#29)
- Support more Mirai APIs
- API handlers
- Custom service name
- Function switches
- Skeleton updater

### Fixed
- Fragments regular expressions

### Changed
- Robot order will be redirected to about by default (#33)
- Order `maxRepeatTimes` is now 20 by default
- Session strategy

### Refactored
- Starting and initialization logic
- Error log
- `App` and `Server` logic
- Order group

### Removed
- Selective/array-reader, for `ArrayReaderTrait`


## 2.0.3 - 2020-11-14

### Fixed
- Fix filter regular expression


## 2.0.2 - 2020-11-10

### Fixed
- Fix a routing bug


## 2.0.1 - 2020-11-05

### Added
- Web APIs user agent check

### Refactored
- Optimize config APIs logic
- Correct some replies


## 2.0.0 - 2020-11-04

### Added
- Introduce coroutine
- Introduce container (PHP-DI)
- Introduce log system (Monolog)
- Introduce HTTP client (Saber)
- Adapt Mirai API HTTP
- Web APIs
- Support action alias
- Support at
- Generate names (.name)
- Dice pool (.w)

### Fixed
- Identify more incorrect dicing expressions

### Changed
- Published as composer library
- Run as PHP-CLI memory-resident application, driven by Swoole
- Complete DTO adaptation
- Restore all default settings to constants
- Load resources in the initialization, and save regularly
- Replaceable number parameters in the replies are now descriptive names
- Help (.help) now supports specific order

### Refactored
- Repartition the application logic into several modules
- Compliance with IoC
- Unified action logic
- Unified resources
- Optimize exception capture
- Optimize dice and subexpression logic
- Optimize all the action logic
- Optimize all the regular expressions

### Removed
- cURL extension requirement


## 1.4.0 - 2020-09-16

### Changed
- Order filter is now processed by DiceRobot


## 1.3.1 - 2020-04-09

### Added
- Sanity check (.sc) can be used without character card bound

### Fixed
- Fix bugs in `ChatSettings` which can cause `FileUnwritableException` unexpectedly

### Changed
- Directory creation now processed by `IOService`
- File/directory path will be checked before created/written
- Error will be logged when failed to create/write file/directory

### Refactored
- Optimize the logic of `App`
- Optimize the regular expressions


## 1.3.0 - 2020-04-07

### Added
- Class `IOService` separated from class `Customization`
- Class `CoolQAPI`, `DiceRobotAPI`, `Request` and `API\Response`
- DiceRobot API response classes
- Exceptions thrown by non-action class

### Fixed
- Fix a logical fallacy in check rule
- Fix namespace bugs in `CheckRuleException`

### Changed
- Use class static member variables to store settings and replies, instead of global constants
- Autoloader now loads class directly and no longer need mapping
- File reading/writing is now processed by class `IOService`
- API accessing is now processed by class `CoolQAPI` and `DiceRobotAPI`
- DiceRobot APIs now returns specific response object
- Class `App` now responds to the HTTP API plugin
- Non-action class only returns correct result, and throws exception when error occurs
- DiceRobot APIs is now v2, faster and more RESTful

### Refactored
- Refactor some classes
- Optimize the code
- Optimize the architecture
- Optimize API exception handling
- Merge some replies
- Update PHP docs


## 1.2.0 - 2020-03-24

### Added
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

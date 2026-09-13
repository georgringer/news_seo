# Repository Guidelines

Guidance for coding agents working on EXT:news_seo. Personal or
machine-specific preferences do not belong here — put those in an untracked
`CLAUDE.local.md` (already gitignored).

## Context

- One Composer package, `georgringer/news-seo`, PSR-4 `GeorgRinger\NewsSeo\`
  → `Classes/` and `GeorgRinger\NewsSeo\Tests\` → `Tests/`.
- The extension adds SEO features (robots index/follow, max-image-preview,
  canonical link) **on top of EXT:news** and the core extension `seo`. Both are
  hard dependencies.
- **One branch serves TYPO3 13.4 LTS and 14**, on PHP 8.2–8.5. Every change
  has to work on both cores; `.github/workflows/core13.yml` and `core14.yml`
  define the matrix that decides. Where the cores differ, switch on
  `(new Typo3Version())->getMajorVersion()`.
- Issues and code review happen on GitHub,
  <https://github.com/georgringer/news_seo>.

## Working mode

- When a bug is reported or reproduced, **do not start by fixing it**. First
  write a test that reproduces it, then fix and prove it with that test
  passing. The only exception is a purely mechanical fix with no testable
  behaviour, such as a typo in a label, comment or documentation.
- Only add code comments when they add meaning; otherwise leave them out.
- Do not break public API and do not drop TYPO3 13 support in passing.

## Project structure

- `Classes/` — `Domain/Model/News.php` and `NewsDefault.php` (merged into the
  news model, see below), `EventListener/` (canonical tag, hreflang, robots
  meta tag in the detail action), `Utility/FetchUtility.php`.
- `Configuration/` — `TCA/Overrides/tx_news_domain_model_news.php` and
  `Services.yaml`.
- `Resources/Private/Language/`, `Resources/Public/`.
- `Tests/{Unit,Functional}` mirroring the `Classes/` namespace; the frontend
  tests in `Tests/Functional/Frontend/` render real pages with fixtures.
- `Build/` — `Scripts/runTests.sh` (the single test entry point), `phpunit/`,
  `php-cs-fixer/`, `rector/`, `fractor/`.
- Root: `ext_emconf.php`, `ext_localconf.php`, `ext_tables.sql`.
- `.Build/` holds Composer's vendor and web dir (`vendor-dir: .Build/vendor`),
  generated and gitignored — never edit.

## Commands

Everything runs through `Build/Scripts/runTests.sh`, which starts a container
with the requested PHP version and database. Do not invoke `phpunit`,
`php-cs-fixer`, `rector` or `fractor` directly — the wrapper supplies the
environment. `-h` is the authoritative, always-current list of suites.

Things that are easy to get wrong:

- **Prefix local runs with `CI=true`.** Otherwise the script requests a TTY and
  aborts with `cannot attach stdin to a TTY-enabled container`.
- **`-s composerInstall` ignores `-t` and installs TYPO3 13.** Use
  `composerInstallHighest` or `composerInstallLowest`, which is what CI does.
- **A test system must be installed before any suite runs**, and it pins one
  core version. Switching between `-t 13` and `-t 14` means re-running
  `composerInstall*` first.
- **The PHP version has to match the installed test system.** A highest
  install resolves PHPUnit 13, which requires PHP 8.4 — running the suites
  with `-p 8.3` then fails before any test runs.
- The container engine defaults to **podman** if it is installed, otherwise
  docker (`-b docker` to force it).

```bash
# install a test system: pick core version and dependency resolution
CI=true Build/Scripts/runTests.sh -t 14 -p 8.4 -s composerInstallHighest
CI=true Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerInstallLowest

# tests
CI=true Build/Scripts/runTests.sh -p 8.4 -s lint
CI=true Build/Scripts/runTests.sh -p 8.4 -s unit
CI=true Build/Scripts/runTests.sh -p 8.4 -s functional              # sqlite
CI=true Build/Scripts/runTests.sh -p 8.4 -d postgres -s functional
CI=true Build/Scripts/runTests.sh -p 8.4 -s functional -- \
    Tests/Functional/Frontend/CanonicalTagTest.php

# style and automated migrations (-n is the dry-run CI checks)
PHP_CS_FIXER_IGNORE_ENV=1 CI=true Build/Scripts/runTests.sh -p 8.4 -s cgl -n
CI=true Build/Scripts/runTests.sh -p 8.4 -s rector -n
CI=true Build/Scripts/runTests.sh -p 8.4 -s fractor -n

# cleaning up
CI=true Build/Scripts/runTests.sh -s clean
```

Rector is deliberately not part of the CI yet, it still reports open
migrations. `composer cs` and `composer csfix` are shortcuts for the two CGL
runs, they call `runTests.sh` themselves.

## The EXT:news class merge

`ext_localconf.php` registers this extension with news' class extension
mechanism:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXT']['news']['classes']['Domain/Model/News'][] = 'news_seo';
```

news' `ClassCacheManager` then **copies the bodies of
`Classes/Domain/Model/News.php` and `NewsDefault.php` into generated classes in
the `GeorgRinger\News\Domain\Model` namespace.** In the generated file every
unqualified class name resolves against the *news* namespace, not ours.

- Every class reference in those two files must stay **fully qualified**.
- `Build/rector/rector.php` skips both files for this reason, because Rector's
  `withImportNames` would otherwise shorten those names.
- Unit tests do not catch this — only the functional suite boots the class
  cache (`Tests/Functional/Domain/Model/NewsClassMergeTest.php`). Run
  `-s functional` after touching the model.

## Coding style

- PSR-12 via `typo3/coding-standards`, configured in
  `Build/php-cs-fixer/php-cs-fixer.php`. `-s cgl` is the authority; CI runs it
  as a dry-run on PHP 8.3.
- Every PHP file carries the file header comment
  (`This file is part of the "news_seo" Extension for TYPO3 CMS.`).
- Labels live in `Resources/Private/Language/locallang.xlf` and the German
  `de.locallang.xlf`; both are maintained in this repository.

## Testing

- Unit tests extend the testing-framework base cases, functional tests extend
  `FunctionalTestCase` and resolve services with `$this->get(SomeClass::class)`.
  File names end in `Test.php`.
- Use PHPUnit attributes (`#[Test]`, `#[DataProvider]`), not docblock
  annotations. Data providers must be `public static`.
- Functional tests declare both packages:
  `protected array $testExtensionsToLoad = ['georgringer/news', 'georgringer/news-seo'];`
- `Build/phpunit/UnitTests.xml` sets `failOnDeprecation`, `failOnNotice`,
  `failOnRisky` and `failOnWarning`. **A triggered deprecation fails the build
  even when every assertion passes** — the run prints
  `OK, but there were issues!` and still exits non-zero.
- `Build/phpunit/FunctionalTests.xml` has `failOnDeprecation="false"`: EXT:news
  triggers v14.3 deprecations that cannot be fixed here, see the comment in
  that file.
- DB fixtures are CSV files loaded with `importCSVDataSet()`. These paths do
  not resolve `EXT:` prefixes — use `__DIR__`-relative paths.
- `composer.json` carries a `conflict` on `sebastian/recursion-context`
  6.0.0 – 6.0.2: those versions trigger PHP 8.5 deprecations on the
  lowest-dependency matrix cell. Keep it.

## Commits and pull requests

**Only create or modify commits when explicitly asked.**

- Subject tags: `[BUGFIX]`, `[FEATURE]`, `[TASK]`, `[DOC]`. Imperative, concise
  subject; the body describes the behaviour without the patch, why that is a
  problem, and how the patch fixes it.
- Reference the GitHub issue in the footer as `Resolves: #123`.
- Work on a topic branch off `main` and open a pull request; do not commit to
  `main` directly.
- Keep commits as logical units — an unrelated cleanup belongs in its own
  commit, even when it touches the same file.
- Do not credit tooling or assistants in commit messages.
- Before pushing, `lint`, `unit`, `functional` and `cgl -n` should be green,
  and on both TYPO3 13 and 14 whenever the change touches version-sensitive
  code.

## Security

- Never commit secrets or credentials.
- Report potential security issues privately to the TYPO3 Security Team
  (<security@typo3.org>) instead of opening a public GitHub issue.

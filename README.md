<p align="center">
  <a href="https://app.fogito.com" rel="noopener noreferrer nofollow" target="blank">
    <img width="200" src="https://app.fogito.com/assets/images/logos/logo.svg" alt="fogito-core">
  </a>
</p>

<h1 align="center">fogito-core</h1>

<p align="center">
  Shared PHP MVC and service-foundation library for Fogito applications, with routing, bootstrap helpers, request and response abstractions, local and remote model layers, and bundled Core service wrappers.
</p>

## Table of Contents

- [Overview](#overview)
- [What This Repo Contains](#what-this-repo-contains)
- [Runtime Modes And Routes](#runtime-modes-and-routes)
- [Architecture](#architecture)
- [Directory Guide](#directory-guide)
- [Related Repositories](#related-repositories)
- [Installation](#installation)
- [Available Scripts](#available-scripts)
- [Development Notes](#development-notes)
- [Feature-Level Notes For Contributors](#feature-level-notes-for-contributors)
- [Tooling](#tooling)

<a id="overview"></a>
## ✨ Overview

`fogito-core` is the shared PHP foundation used across the Fogito backend ecosystem. It is not a standalone product application by itself. Instead, it provides the reusable pieces that Fogito services and internal apps build on:

- a lightweight application container and service registry
- module-based controller dispatching
- configurable route matching with placeholder support
- request and response helpers for HTTP and CLI execution
- a local MongoDB-oriented model base
- a remote service model base for calling shared Fogito APIs
- helper libraries for auth, company context, translations, caching, and text utilities
- example app structure showing how Fogito sites are typically bootstrapped

If you are onboarding into a Fogito backend repo and want to understand why its boot files, modules, routes, models, and auth flow look the way they do, this is the reference repo that explains that shape.

<a id="what-this-repo-contains"></a>
## 🧩 What This Repo Contains

### Application kernel and dispatch flow

- `src/App.php`
  - application container used as the repo's DI-style runtime
  - service registration through `set()` and `get()`
  - module path registration and controller or action suffix configuration
  - dispatch flow that resolves the active module, loads its `Module.php`, then instantiates the target controller
- `src/Controller.php`
  - base controller layer that consuming modules can extend
- `src/Events/*`
  - lightweight event manager used by modules to attach auth or middleware behavior before dispatch

### Routing and route patterns

- `src/Router.php`
  - route storage, default module or controller or action handling, URI parsing, and dispatch metadata
  - supports both `_url`-based rewrites and `REQUEST_URI`
  - supports CLI-style routing by reading `_url=/path` from command arguments
- `src/Router/Route.php`
  - route placeholder compilation for patterns such as `:module`, `:controller`, `:action`, `:params`, and `:int`
  - method restrictions through `via(...)`

### Bootstrap and configuration helpers

- `src/Loader.php`
  - custom autoloader for namespaces, prefixes, class maps, and directory registration
- `src/Config.php`
  - nested config object with merge and array-access support
  - environment-aware service URL generation for Fogito backends such as `s2s`, `files`, `core`, and `accounting`
- `example/core.php`
  - sample environment bootstrap and fatal-error handling

### HTTP and utility layer

- `src/Http/Request.php`
  - unified input reading from `$_REQUEST`, JSON request body, and CLI args
  - helpers for query, post, headers, scheme, and environment mode
- `src/Http/Response.php`
  - JSON success or error helpers
  - shared CORS headers and CLI-safe output behavior
- `src/Filter.php`, `src/Text.php`, `src/Exception.php`
  - shared sanitization, string, and exception utilities used across the library

### Data access layer

- `src/Db/ModelManager.php`
  - local MongoDB-oriented model base for repositories that store data in their own database
  - provides `find`, `findFirst`, `findById`, `count`, `insert`, `update`, and related helpers
- `src/Db/RemoteModelManager.php`
  - remote API model base that forwards model-style calls over cURL to Fogito services
  - injects auth token, user token, language, origin, and request context into outbound calls

### Shared Fogito service wrappers

The `src/Models` directory ships prebuilt remote wrappers for common platform services:

- `CoreActivities`
- `CoreActivityOperations`
- `CoreCompanies`
- `CoreCustomModel`
- `CoreEmails`
- `CoreFiles`
- `CoreNotifications`
- `CoreSettings`
- `CoreSharedCompanies`
- `CoreSMS`
- `CoreTimezones`
- `CoreTranslations`
- `CoreUsers`

These classes are meant to save consuming repos from rebuilding the same service contracts over and over.

### Auth, language, company, and cache helpers

- `src/Lib/Auth.php`
  - bootstraps account, permissions, company, pricing, and translations from the remote settings endpoint
- `src/Lib/Lang.php`
  - stores active language and translation data
- `src/Lib/Company.php`
  - exposes current company context
- `src/Lib/Cache.php`
  - lightweight cache helper used by auth bootstrap and other shared flows

### Example application and docs

- `example/`
  - sample site layout showing how app config, app libraries, models, middlewares, site config, modules, and the public entrypoint are wired together
- `README/`
  - model-specific usage guides for:
    - [Core Users](README/CoreUsers.md)
    - [Core Files](README/CoreFiles.md)
    - [Core Notifications](README/CoreNotifications.md)
    - [Core Emails](README/CoreEmails.md)
    - [Core SMS](README/CoreSMS.md)
    - [Core Activities](README/CoreActivities.md)
    - [Core Activity Operations](README/CoreActivityOperations.md)

<a id="runtime-modes-and-routes"></a>
## 🗺 Runtime Modes And Routes

### Consumer application bootstrap

The example site in `example/sites/api/public/index.php` shows the intended boot flow for a Fogito service that consumes this library:

1. define an application path
2. load shared environment bootstrap from `example/core.php`
3. load Composer autoloading
4. register project namespaces such as `Lib`, `Models`, and `Middlewares`
5. create a `Fogito\App` instance
6. register services such as `config` and `router`
7. point the app at a modules directory
8. configure controller or action suffix behavior
9. call `$app->handle()`

This is the core runtime contract that many Fogito backend repos follow, even when their domain logic is different.

### Module and controller conventions

The dispatch layer expects a site to follow a module-first structure:

- `sites/<site>/modules/<module>/Module.php`
- `sites/<site>/modules/<module>/controllers/*`

Each module's `Module.php` is responsible for:

- registering the `Controllers` namespace
- setting the app's default controller namespace
- attaching the event manager
- attaching middleware or auth listeners such as `Fogito\Lib\Auth`

In the bundled example, `products/Module.php` attaches both auth and an API middleware before dispatch.

### Route patterns

The example route config in `example/sites/api/config/routes.php` exposes:

- `/testcontroller`
- `/testaction`
- `/:module/:controller/:action/:params`
- `/:module/:controller/:action`
- `/:module/:controller`
- `/:module`

The router can also create similar defaults internally when constructed with default routes enabled.

### URI source and CLI support

Routing and request parsing support more than browser traffic:

- `Router` can resolve the request path from `_url` or `REQUEST_URI`
- `Request` merges data from `$_REQUEST`, JSON request bodies, and CLI arguments
- CLI execution can pass `_url=/module/controller/action` to simulate routed requests

That makes the core usable in traditional HTTP entrypoints and internal scripted or maintenance flows.

### Remote service conventions

Remote model wrappers use the following pattern:

- `Config::getUrl(...)` derives environment-aware service hosts
- `RemoteModelManager` builds URLs as `server/source/action`
- auth token, user token, language, origin, and request URI metadata are attached automatically
- responses are expected in the shared Fogito JSON format with `status`, `description`, and `data`

This is the contract behind the bundled `CoreUsers`, `CoreFiles`, `CoreSettings`, and related wrappers.

<a id="architecture"></a>
## 🏗 Architecture

### Bootstrap layer

- `src/App.php`
  - central runtime object for services, modules, and dispatch
- `src/Loader.php`
  - class loading for project namespaces and custom directories
- `src/Config.php`
  - runtime config object and shared URL builder

### Routing layer

- `src/Router.php`
  - route storage, URI resolution, defaults, matches, and resolved module or controller or action state
- `src/Router/Route.php`
  - pattern compilation and route parameter mapping

### Request and response layer

- `src/Http/Request.php`
  - normalized request access across HTTP and CLI
- `src/Http/Response.php`
  - consistent JSON success or error payloads and CORS behavior

### Utility and context layer

- `src/Lib/Auth.php`
  - initializes auth state and remote settings payloads
- `src/Lib/Lang.php`
  - translation and active-language helpers
- `src/Lib/Company.php`
  - company context storage
- `src/Lib/Cache.php`
  - cache adapter used by shared bootstrap flows
- `src/Filter.php` and `src/Text.php`
  - general-purpose helpers used across requests and models

### Data layer

- `src/Db/ModelManager.php`
  - local data access for repositories with their own Mongo-backed collections
- `src/Db/RemoteModelManager.php`
  - remote service gateway for model-like access to shared APIs
- `src/Models/*`
  - ready-to-use wrappers over frequently used Fogito services

### Example layer

- `example/app`
  - root project config, shared libraries, middlewares, and local models
- `example/sites/api`
  - site-specific config, modules, and public entrypoint

This split mirrors how larger Fogito backend repos often separate application-wide helpers from site-specific module trees.

<a id="directory-guide"></a>
## 📁 Directory Guide

```text
fogito-core
├── README
│   ├── CoreActivities.md
│   ├── CoreActivityOperations.md
│   ├── CoreEmails.md
│   ├── CoreFiles.md
│   ├── CoreNotifications.md
│   ├── CoreSMS.md
│   └── CoreUsers.md
├── example
│   ├── core.php
│   ├── app
│   │   ├── config
│   │   ├── lib
│   │   ├── middlewares
│   │   └── models
│   └── sites
│       └── api
│           ├── config
│           ├── modules
│           │   └── products
│           └── public
├── src
│   ├── Db
│   ├── Events
│   ├── Http
│   ├── Lib
│   ├── Models
│   ├── Router
│   ├── App.php
│   ├── Config.php
│   ├── Controller.php
│   ├── Exception.php
│   ├── Filter.php
│   ├── Loader.php
│   ├── Router.php
│   └── Text.php
├── composer.json
└── README.md
```

### Where to start when making changes

- **Need to understand boot and dispatch?**
  - Start in `src/App.php`, then read `example/sites/api/public/index.php`.
- **Need to change routing behavior or placeholders?**
  - Start in `src/Router.php` and `src/Router/Route.php`.
- **Need to adjust config or service URL generation?**
  - Start in `src/Config.php`.
- **Need to work on auth, language, or company context?**
  - Start in `src/Lib/Auth.php`, `src/Lib/Lang.php`, and `src/Lib/Company.php`.
- **Need to change local model behavior?**
  - Start in `src/Db/ModelManager.php`.
- **Need to add or adjust a remote Core wrapper?**
  - Start in `src/Db/RemoteModelManager.php` and the relevant class in `src/Models`.
- **Need to understand the public API contract of a shared model?**
  - Check the corresponding file in `README/`.

<a id="related-repositories"></a>
## 🔌 Related Repositories

This repo is typically developed together with the following Fogito repositories:

- [`fogito-core-ui`](https://github.com/Fogito-com/fogito-core-ui)
  - shared frontend component and application shell library used by many Fogito React repos
- [`backend.general`](https://github.com/Fogito-com/backend.general)
  - primary shared backend that reflects many of the same module, bootstrap, auth, and service conventions defined here
- [`backend.files`](https://github.com/Fogito-com/backend.files)
  - file service commonly consumed through `CoreFiles` and related upload flows
- [`backend.invoices`](https://github.com/Fogito-com/backend.invoices)
  - accounting service often reached through the `accounting` service path in `Config`
- [`backend.task.management`](https://github.com/Fogito-com/backend.task.management)
  - representative domain backend built on the same Fogito service architecture

If you are trying to understand how this library is used in practice, `backend.general` is usually the best backend companion repo to read next.

<a id="installation"></a>
## 📦 Installation

```bash
composer require fogito/core
```

This is the package install string used by the existing repo docs and description. See the development notes below for the current metadata mismatch in `composer.json`.

<a id="available-scripts"></a>
## 🚀 Available Scripts

No Composer scripts are defined in this repository.

Typical command patterns for consumer apps built on this library are:

| Command | Purpose |
| --- | --- |
| `composer require fogito/core` | Installs the library into a consuming project. |
| `php sites/<site>/public/index.php _url=/module/controller/action` | Simulates a routed request in CLI mode for apps that follow the Fogito bootstrap pattern. |

This repo itself is primarily a library plus example structure, not a turnkey service with its own `start` or `serve` script.

<a id="development-notes"></a>
## 🛠 Development Notes

### Package metadata drift

There is an internal packaging inconsistency in the current repo state:

- `README.md` and the `composer.json` description use `composer require fogito/core`
- `composer.json` currently declares the package name as `fogito/corepro`

If you are publishing or consuming this package outside the usual internal environment, verify which package name your Composer registry actually exposes before documenting or automating around it.

### Example app bootstrap drift

The example site is useful for structure, but it is not fully self-contained in the committed tree:

- `example/sites/api/public/index.php` requires `example/vendor/autoload.php`
- `.gitignore` excludes `example/vendor`
- `.gitignore` also excludes `/example/composer.json`

That means the example documents the expected runtime shape, but local execution depends on internal bootstrap or packaging steps that are not committed here.

### Request handling

`Request` is broader than a simple wrapper over `$_GET` and `$_POST`:

- it merges `$_REQUEST`
- it reads JSON request bodies
- it accepts CLI `key=value` arguments
- it exposes environment, scheme, header, and AJAX helpers

That behavior matters when debugging differences between web traffic, cron jobs, and manual CLI calls.

### Response format

`Response` standardizes Fogito JSON responses:

- `success($data, $description = "")`
- `error($error, $code = 2001, $data = false)`
- `custom($response, $isJson = true)`

It also sets CORS headers and writes directly to stdout in CLI mode, so consumers should treat it as a terminating response helper rather than a value-returning abstraction.

### Service URL generation

`Config::getUrl(...)` derives backend endpoints from the current host and environment mode. The built-in service-path map currently covers:

- `s2s`
- `files`
- `core`
- `accounting`

If a consuming repo depends on another service path, this is one of the first places to check before adding hardcoded URLs elsewhere.

### Auth bootstrap

`Lib/Auth` is an important piece of the shared runtime contract:

- reads language from cookie or request
- reads token and token-user data from request parameters
- fetches `account`, `permissions`, `company`, `pricing`, and `translations` from the remote settings endpoint
- caches that payload locally
- exposes shared constants such as `TOKEN`, `BUSINESS_TYPE`, and `COMPANY_ID`

If a domain service behaves differently depending on account, branch, permissions, or translations, this bootstrap path is often the reason.

<a id="feature-level-notes-for-contributors"></a>
## 🔍 Feature-Level Notes For Contributors

### Boot and application lifecycle

When changing runtime flow, read these pieces together:

- `example/core.php`
- `example/sites/api/public/index.php`
- `src/App.php`
- `example/sites/api/modules/products/Module.php`

Together, they show the real Fogito lifecycle from environment bootstrap to controller execution.

### Routing and dispatch

If you need to add custom route syntax, adjust placeholder behavior, or debug why a controller is not resolving:

- start in `src/Router.php`
- then inspect `src/Router/Route.php`
- then confirm the active module's `Module.php` registers the expected controller namespace

Most "route not found" or "class not found" issues in consumer repos come from one of those three places.

### Local models

Use `src/Db/ModelManager.php` when you are working on data that belongs to the current service's own database. This layer is appropriate for:

- repo-owned Mongo collections
- direct insert or update behavior
- collection-specific filters and bind transforms
- shared collection helpers implemented by local model subclasses

### Remote models and shared platform wrappers

Use `src/Db/RemoteModelManager.php` and `src/Models/*` when the data lives in another Fogito service and you want model-style access from the current codebase.

This is the right layer for:

- settings and auth bootstrap
- user lookups
- file operations
- shared company or translation reads
- activity, email, SMS, or notification helpers

If a new remote wrapper is needed, `CoreCustomModel` is the simplest place to start before specializing behavior.

### Model usage docs

The files in `README/` are worth reading before changing remote wrapper behavior. They capture how the shared models are expected to be used in application code, including common operations such as:

- `find`
- `findFirst`
- `insert`
- `update`
- `deleteRaw`
- token and avatar helpers on user-related models
- file move and temp-file validation helpers on file-related models

Those docs are part of the repo's public contract, not just historical notes.

### Example module patterns

The bundled `products` example is intentionally small, but it demonstrates the main conventions contributors should preserve:

- keep modules self-registering through `Module.php`
- keep route definitions site-scoped
- use namespaces for `Lib`, `Models`, `Middlewares`, and `Controllers`
- attach auth or middleware at the event-manager level instead of duplicating the same checks in every controller

<a id="tooling"></a>
## 🧪 Tooling

- PHP `>=7.0`
- Composer-distributed library structure
- PSR-4 autoloading for the `Fogito\\` namespace
- MongoDB PHP driver usage in the local model layer
- cURL-based service integration in the remote model layer
- JSON-first HTTP and CLI response helpers

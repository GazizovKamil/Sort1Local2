# Project Guidelines

## Architecture
- This repo is a hybrid PHP + .NET system using PeachPie.
- `Server/` is the ASP.NET Core host (net6.0) that serves PHP via `Peachpie.AspNetCore.Web`.
- `Website/` is the PHP codebase compiled by PeachPie (`Website.msbuildproj`).
- Runtime flow: requests enter `Server/Program.cs`, `/` redirects to `/index.php`, and PHP content is served from `../Website` in development.
- API entry points are mixed: legacy routing in `Website/index.php` and modern API bootstrap in `Website/api/index.php`.

## Build And Run
- Restore .NET packages: `dotnet restore`
- Build server: `dotnet build Server/Server.csproj`
- Run locally: `cd Server` then `dotnet run`
- Default runtime URL is `http://localhost:5004` (configured as `http://*:5004/`).
- Install PHP dependencies when needed: `cd Website` then `composer install`
- Optional PeachPie build validation: `dotnet build Website/Website.msbuildproj`
- No reliable automated test command is currently defined in the workspace.

## Conventions
- Preserve mixed architecture: do not force broad rewrites from legacy procedural PHP to modern class-based code unless requested.
- Keep new API-layer code under `Website/api/classes` using existing namespace patterns (`Sort1API\\...`).
- Match local style in touched files; this repo has intentionally inconsistent style across legacy and newer areas.
- Prefer minimal, targeted changes and avoid refactoring unrelated files.

## Safe Editing Boundaries
- Do not edit generated or dependency directories unless explicitly requested:
  - `Server/bin/`, `Server/obj/`, `Server/publish/`
  - `Website/bin/`, `Website/obj/`, `Website/vendor/`
- Treat duplicate backup-like files (for example files with ` copy` in the name) as potentially intentional historical artifacts; do not remove or rename unless asked.

## Environment Notes
- Database access is configured in PHP files under `Website/api` and may be environment-specific.
- Prefer environment-driven configuration changes over hardcoding new secrets.
- Be careful with path assumptions: the project may run on both Windows and Linux during development/deployment.

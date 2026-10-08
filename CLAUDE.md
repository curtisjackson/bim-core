# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Что это

`bim-core` — composer-библиотека (Packagist: `curtisjackson/bim-core`, `replace` для `cjp2600/bim-core`) для версионных миграций структуры БД 1С-Битрикс. Это не самостоятельное приложение: код работает только внутри установленного Битрикс-проекта, куда пакет подключён через composer. Пользовательская документация — `README.md` (зеркало в `docs/index.md`), на русском.

## Сборка, тесты, запуск

- Тестов, линтера и сборки в репозитории нет. Локально из этого каталога CLI не запустить — нужно ядро Битрикса.
- Проверка синтаксиса после правок: `php -l src/path/File.php`.
- Автозагрузка — `classmap` в `composer.json`. При добавлении нового **каталога** с классами его нужно добавить в `autoload.classmap`, иначе классы не найдутся; в проекте-потребителе после изменений нужен `composer dump-autoload`.
- Запуск в проекте-потребителе: `php vendor/bin/bim <команда>` (`up`, `down`, `ls`, `gen`, `init`, `info`, `export`).
- Код должен работать на PHP 8 (недавние фиксы — TypeError на PHP 8 в вызовах старого API Битрикса, например `CGroup::GetList` требует строковые `$by`/`$order`).

## Архитектура

**Точка входа.** `src/bin/bim` вычисляет `DOCUMENT_ROOT` как 5 уровней вверх от себя (`<root>/vendor/curtisjackson/bim-core/src/bin/bim`), подключает `prolog_before.php` Битрикса и вызывает `Bim\Migration::init()`. Тот строит `ConsoleKit\Console` из маппинга `src/config/commands.json` (имя команды → класс). Новая команда = класс в `src/cmd/` + строка в `commands.json`.

**Конфиг.** `src/config/bim.json` (`migration_path`, `logging_path`, `migration_table`) читается через `Bim\Util\Config` (JSON, ключи через точку). Каталог миграций можно переопределить опцией `--migration_path`.

**Команды** (`src/cmd/`, глобальный namespace) наследуют `BaseCommand` (ConsoleKit `Command`). В `BaseCommand` — общая логика: поиск/загрузка файлов миграций (`getDirectoryTree`), рендер шаблонов, сохранение файла, авто-применение, логирование, теги, проверка в БД.

**Файл миграции.** Имя — unix timestamp (`<migration_path>/1423660766.php`), класс внутри — `Migration<timestamp>`, реализует `Bim\Revision` (статические `up()`, `down()`, `getDescription()`, `getAuthor()`). Связь «файл ↔ класс ↔ id в таблице» держится только на этом соглашении об именах. Шаблон — `src/db/template/main.txt` с плейсхолдерами `#CLASS_NAME#`, `#UP_CONTENT#` и т.д. `up()`/`down()` считаются неуспешными, если вернули `false` или бросили исключение. Теги — `#tag` в описании, по ним фильтруют `up/down/ls --tag=`.

**Учёт применённых миграций** — `Bim\Db\Entity\MigrationsTable` (`src/db/entity/MigrationTable.php`): сырые запросы через `global $DB` к таблице из `bim.json` (одна колонка `id`, DDL в `src/db/install/install.sql`). «Применена» = id есть в таблице.

**Генерация «по наличию»** (`bim gen Entity:add|delete`), три слоя:
1. `GenCommand::execute` разбирает `Entity:action`, получает провайдер через фабрику `Bim\Db\Generator\Code::buildHandler()` (класс `Bim\Db\Generator\Providers\<Entity>`) и вызывает метод `gen<Entity><Action>` у самой команды (интерактивные вопросы + опции).
2. **Провайдеры** (`src/db/generator/providers/`, наследуют абстрактный `Code`) читают текущее состояние сущности из БД Битрикса и генерируют PHP-код вызовов через `getMethodContent()` (`var_export` параметров) — строку вида `Bim\Db\Iblock\IblockIntegrate::Add(array(...));`.
3. **Integrate-классы** (`src/db/iblock/`, `src/db/main/`, namespace `Bim\Db\Iblock` / `Bim\Db\Main`) — рантайм-обёртки над API Битрикса (`CIBlock`, `CGroup`, HL-блоки и т.п.), которые вызываются из сгенерированных миграций. Ошибки — через `Bim\Exception\BimException`.

Важно: такие классы попадают в код миграций пользователей, поэтому сигнатуры статических методов Integrate-классов — фактически публичный API; ломать их нельзя.

Для `add` сгенерированная миграция сразу помечается применённой без запуска `up()` (сущность уже есть в БД); для `delete` — `up()` реально выполняется (`BaseCommand::autoUpMethod`). Режим `gen multi` копит up/down нескольких генераций (по ключам `add`/`delete`) и в конце сохраняет каждую отдельным файлом (сначала все `add`, затем все `delete`). Id новой миграции — `BaseCommand::getMigrationName()`: `time()`, но не меньше последнего id в каталоге миграций + 1; `saveTemplate` не перезаписывает существующий файл.

Добавление новой сущности: провайдер в `providers/` + Integrate-класс + пара методов `gen<Entity>Add/Delete` в `GenCommand` + раздел в README (режим multi подхватит её автоматически).

**Export** (`bim export make`): `Bim\Export\Session` + `src/Export/Dump/dump.php` (обёртка над `CBackup` Битрикса) и `alchemy/zippy` для упаковки дампа.

## Соглашения

- Сообщение коммита — просто описание на русском, без префикса задачи.
- Комментарии и документация — на русском; стиль кода — старый PHP (array(), статические методы, без строгой типизации), придерживаться его.


# <a name="about"></a>Bitrix Migration (BIM)

[![Latest Stable Version](https://poser.pugx.org/curtisjackson/bim-core/v/stable.svg)](https://packagist.org/packages/curtisjackson/bim-core) [![Total Downloads](https://poser.pugx.org/curtisjackson/bim-core/downloads.svg)](https://packagist.org/packages/curtisjackson/bim-core) [![Latest Unstable Version](https://poser.pugx.org/curtisjackson/bim-core/v/unstable.svg)](https://packagist.org/packages/curtisjackson/bim-core) [![License](https://poser.pugx.org/curtisjackson/bim-core/license.svg)](https://packagist.org/packages/curtisjackson/bim-core)

Версионная миграция структуры БД для **[1С Битрикс CMS](http://bitrix.ru)**

- [Установка](#install)
  * [Автоматическая установка](#auto)
  * [Ручная установка](#hand)
  * [Команда bim](#bim_file)
- [Настройка](#prop)
- [Выполнение - bim up](#up)
- [Отмена - bim down](#down)
- [Вывод списка - bim ls](#ls)
- [Создание - bim gen](#gen)
  * [Создание пустой миграции](#gen_empty)
  * [Создание миграционного кода по наличию](#gen_nal)
    * Модуль (iblock,highloadblock)
    * [IblockType](#iblocktype)
    * [Iblock](#iblock)
    * [IblockProperty](#iblockproperty)
    * [Hlblock](#hlblock)
    * [HlblockField](#hlblockfield)
    * Модуль (main)
    * [Group](#main_group)
    * [Site](#main_site)
    * [Language](#main_language)
    * [EventType](#main_event_type)
  * [Режим multi - bim gen multi](#multi)
  * [Тегирование миграций](#tag)
  * [Логирование](#logging)
- [Информация о проекте - bim info](#info)

# <a name="install"></a>1 Установка

### <a name="auto"></a>1.1 Автоматическая установка 

Для установки и инициализации bim для bitrix проекта необходимо выполнить следующие действия из корня проекта:

- Установить Composer:
```    
curl -s https://getcomposer.org/installer | php
```
- Выполнить установочный скрипт:

``` bash
php -r "readfile('https://raw.githubusercontent.com/curtisjackson/bim/master/install');" | php
```
> Автоматические действия установщика:

> 1. Инициализация **composer autoloader** в файле **init.php** (`local/php_interface/init.php`, если есть каталог `local`, иначе `bitrix/php_interface/init.php`).
> 2. Создание файла **composer.json** в корне проекта со ссылкой на bim репозиторий **"require": { "curtisjackson/bim-core": ">=1.0.0"}**

> Обратите внимание!

> Если **composer.json** в корне проекта уже существует, установщик его не меняет — добавьте зависимость вручную: `php composer.phar require curtisjackson/bim-core`.

### <a name="hand"></a>1.2 Ручная установка 

Для ручной установки bim необходимо:

- Установить Composer:
   
```    
curl -s https://getcomposer.org/installer | php
```
- Добавить инициализацию composer (в файл init.php добавить запись):

```bash
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'))
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
```

- Создать в корне сайта файл **composer.json** с содержимым:

```json
{
	"require": {
		"curtisjackson/bim-core": ">=1.0.0"
	}
}
```

- В **.gitignore** добавить запись:

```
/vendor
```

- Done! :):

``` php
php bim info
```

### <a name="bim_file"></a>1.3 Команда bim

Все команды в документации запускаются из корня сайта как `php bim <команда>`. Для этого в корне сайта нужен файл **bim** — ссылка на исполняемый файл пакета (создаётся один раз, после установки пакета через composer):

``` bash
ln -s vendor/curtisjackson/bim-core/src/bin/bim bim
```

Без ссылки те же команды можно запускать как `php vendor/bin/bim <команда>`.

# 2 <a name="prop"></a>Настройка

Для начала работы обновляем **composer** и создаем миграционную таблицу в БД:

``` bash
php composer.phar update
```
Создаём таблицу миграций : 

```bash
php bim init
```


# 3 <a name="up"></a>Выполнение миграций [BIM UP]

- Общее выполнение:
```bash
php bim up
```
Выполняет полный список не выполненных либо ранее отмененных миграционных классов, отсортированных по названию (**timestamp**).
Перед выполнением запрашивается подтверждение (нужно ввести `yes`), пропустить вопрос можно опцией `--force`:
```bash
php bim up --force
```

- Единичное выполнение:
```bash
php bim up 1423660766
```
Выполняет указанную в параметрах миграцию. Id миграции можно передать и опцией: `--id=1423660766`.

- Выполнение по временному периоду:
```bash
php bim up --from="29.01.2015 00:01" --to="29.01.2015 23:55"
```
Выполняет не выполненные миграции, созданные в указанный период. Можно указать только одну из границ.
Дата без времени означает начало дня: `--to="29.01.2015"` не включает миграции, созданные 29.01.2015.

- Выполнение по тегу:
```bash
php bim up --tag=iws-123
```
Выполняет все миграции, где найден указанный тег в описании.

- Логирование:
``` bash
php bim up --logging
```

Дополнительные опции:

- `--migration_path=path/to/migrations` — другой каталог миграций: абсолютный путь или путь относительно текущего каталога (по умолчанию — `migrations` в корне сайта).
- `--debug` — выводит файл и строку, где было выброшено исключение.

# 4 <a name="down"></a>Отмена выполненных миграций  [BIM DOWN]

- Общая отмена:
```bash
php bim down
```
Отменяет весь список выполненных миграционных классов (в обратном порядке).
Перед отменой запрашивается подтверждение (нужно ввести `yes`), пропустить вопрос можно опцией `--force`:
```bash
php bim down --force
```

- Единичная отмена:
``` bash
php bim down 1423660766
```
Отменяет указанную в параметрах миграцию. Id миграции можно передать и опцией: `--id=1423660766`.

- Отмена по временному периоду:
```bash
php bim down --from="29.01.2015 00:01" --to="29.01.2015 23:55"
```
Отменяет выполненные миграции, созданные в указанный период. Можно указать только одну из границ.

- Отмена по тегу:
```bash
php bim down --tag=iws-123
```
Отменяет все миграции, где найден указанный тег в описании.

- Логирование:
``` bash
php bim down --logging
```

Опции `--migration_path` и `--debug` работают так же, как в [bim up](#up).

# 5 <a name="ls"></a>Вывод списка миграций [BIM LS]
- Общий список:
```bash
php bim ls
```
- Список выполненных миграций:
```bash
php bim ls --a
```
- Список не выполненных (новых и отменённых) миграций:
```bash
php bim ls --n
```
- Вывод имени файла миграции в отдельной колонке:
```bash
php bim ls --f
```
- Список миграций за определённый период времени:
```bash
php bim ls --from="29.01.2015 00:01" --to="29.01.2015 23:55" 
```
- Список миграций по тегу:
```bash
php bim ls --tag=iws-123
```
- Список миграций из другого каталога (абсолютный путь или относительно текущего каталога):
```bash
php bim ls --migration_path=path/to/migrations
```

# 6 <a name="gen"></a>Создание новых миграций [BIM GEN]

Существует два способа создания миграций:
 
## <a name="gen_empty"></a>1) Создание пустой миграции:
Создается пустой шаблон миграционного класса. Структура класса определена интерфейсом *Bim/Revision* и включает следующие
обязательные методы:
 
  - *up();* - выполнение
  - *down();* - отмена
  - *getDescription();* - получения описания.
  - *getAuthor();* - получение автора.

Дополнительно запрашивается:
- [Description]
 
**Пример:**

``` bash
php bim gen
```
Также возможно передать description опционально:
``` bash  
php bim gen --d="new description #iws-123"
```

> Далее создается файл миграции вида: */[migrations_path]/[timestamp].php

> Например: /migrations/123412434.php
 
## <a name="gen_nal"></a>2) Создание миграционного кода по наличию:

Создается код развертывания/отката существующего элемента схемы bitrix БД.
На данный момент доступно генерация по наличию для следующих элементов bitrix БД:
 
### 2.1 <a name="iblocktype"></a>IblockType *( php bim gen IblockType:[add|delete] )*:

Создается Миграционный код "**Типа ИБ**" включая созданные для него *(UserFields, IBlock, IblockProperty)*
 
Дополнительно запрашивается:
- [IBLOCK_TYPE_ID]
- [Description]
 
**Пример:**

``` bash 
php bim gen IblockType:add
``` 
Также возможно передать iblock type id и description опционально:
``` bash  
php bim gen IblockType:add --typeId=catalog --d="new description #iws-123"
``` 
### <a name="iblock"></a>2.2 Iblock *( php bim gen Iblock:[add|delete] )*:

Создается Миграционный код "**ИБ**" включая созданные для него *(IblockProperty)*

Дополнительно запрашивается:
- [IBLOCK_CODE]
- [Description]

**Пример:**
``` bash  
php bim gen Iblock:add
``` 
Также возможно передать iblock code и description опционально:
``` bash  
php bim gen Iblock:add --code=goods --d="new description #iws-123"
``` 

### <a name="iblockproperty"></a>2.3 IblockProperty *( php bim gen IblockProperty:[add|delete] )*:

Создается Миграционный код "**Свойства ИБ**"

Дополнительно запрашивается:
- [IBLOCK_CODE]
- [PROPERTY_CODE]
- [Description]

**Пример:**
``` bash  
php bim gen IblockProperty:add
``` 
Также возможно передать iblock code, property code и description опционально:
``` bash  
php bim gen IblockProperty:add --code=goods --propertyCode=NEW_ITEM --d="new description #iws-123"
``` 

### <a name="hlblock"></a>2.4 Hlblock *( php bim gen Hlblock:[add|delete] )*:

Создается Миграционный код "**Highloadblock**" включая созданные для него *(UserFields)*

Дополнительно запрашивается:
- [HLBLOCK_ID]
- [Description]

**Пример:**
``` bash  
php bim gen Hlblock:add
``` 
Также возможно передать hlblock id и description опционально:
``` bash  
php bim gen Hlblock:add --id=82 --d="new description #iws-123"
``` 

### <a name="hlblockfield"></a>2.5 HlblockField *( php bim gen HlblockField:[add|delete] )*:

Создается Миграционный код "**HighloadblockField (UserField)**"

Дополнительно запрашивается:
- [HLBLOCK_ID]
- [USER_FIELD_ID]
- [Description]

**Пример:**
``` bash  
php bim gen HlblockField:add
``` 
Также возможно передать hlblock id, hlblock field id и description опционально:
``` bash  
php bim gen HlblockField:add --hlblockid=93 --hlFieldId=582 --d="new description #iws-123"
```

### <a name="main_group"></a>2.6 Group *( php bim gen Group:[add|delete] )*:

Создается Миграционный код "**Group (Группы пользователей)**"

Дополнительно запрашивается:
- [GROUP_ID]
- [Description]

**Пример:**
``` bash  
php bim gen Group:add
``` 
Также возможно передать group id, и description опционально:
``` bash  
php bim gen Group:add --id=5 --d="new description #iws-123"
```

### <a name="main_site"></a>2.7 Site *( php bim gen Site:[add|delete] )*:

Создается Миграционный код "**Site (Сайты)**"

Дополнительно запрашивается:
- [SITE_ID]
- [Description]

**Пример:**
``` bash  
php bim gen Site:add
``` 
Также возможно передать site id, и description опционально:
``` bash  
php bim gen Site:add --id=s1 --d="new description #iws-123"
```

### <a name="main_language"></a>2.8 Language *( php bim gen Language:add )*:

Создается Миграционный код "**Language (Языки)**". Доступен только режим `add`.

Дополнительно запрашивается:
- [LANG_ID]
- [Description]

**Пример:**
``` bash  
php bim gen Language:add
``` 
Также возможно передать language id и description опционально:
``` bash  
php bim gen Language:add --id=en --d="new description #iws-123"
```

### <a name="main_event_type"></a>2.9 EventType *( php bim gen EventType:[add|delete] )*:

Создается Миграционный код "**EventType (Почтовые события)**" — тип почтового события на всех языках включая созданные для него *(почтовые шаблоны)*.

Дополнительно запрашивается:
- [EVENT_NAME]
- [Description]

**Пример:**
``` bash  
php bim gen EventType:add
``` 
Также возможно передать event name и description опционально:
``` bash  
php bim gen EventType:add --eventName=UPDATE_PRICES_OF_PROGRAMS --d="new description #iws-123"
```

В `up()` создаются записи типа события для каждого языка (`EventTypeIntegrate::Add`) и почтовые шаблоны с привязкой к тем же сайтам (`EventMessageIntegrate::Add`).
В `down()` тип события удаляется вместе со всеми его почтовыми шаблонами (`EventTypeIntegrate::Delete`).
Вложения почтовых шаблонов (файлы) в миграцию не переносятся.


> Обратите внимание!

> Миграционные классы, созданные по наличию, сразу отмечаются как выполненные:

> - для `add` элемент уже есть в БД, поэтому миграция только записывается в таблицу миграций, метод `up()` не вызывается;
> - для `delete` метод `up()` выполняется сразу, то есть элемент **удаляется из БД в момент генерации**.

> Имя сущности пишите как в документации: `IblockType:add` работает, `iblocktype:add` — нет (класс провайдера ищется с учётом регистра).


## <a name="multi"></a> Режим multi [BIM GEN MULTI]:

Так же доступен режим массовой генерации по наличию. Данный способ удобен при создании миграций по наличию для множества однотипных элементов,
например для нескольких полей highload-блока (`HlblockField:add`) или свойств инфоблока (`IblockProperty:add`).
Режим работает с любыми командами генерации по наличию из раздела [2](#gen_nal).

``` bash
php bim gen multi
```

**Как это работает:**

1. На запрос `Put generation commands: php bim gen >` введите команду генерации **без** `php bim gen` и без опций, например `HlblockField:add`.
2. Параметры элемента (`[HLBLOCK_ID]`, `[USER_FIELD_ID]` и т.п.) запрашиваются интерактивно — так же, как при обычном запуске команды без опций. Описание миграции на этом шаге не спрашивается.
3. После генерации появится вопрос `You want to repeat command (HlblockField:add) [Y]`:
   - `Enter` или `Y` — повторить ту же команду для следующего элемента;
   - любой другой ответ (например, `n`) — вернуться к запросу новой команды.
4. Чтобы закончить ввод, оставьте запрос новой команды пустым и нажмите `Enter`.
5. Будет запрошено одно общее описание (`Description`) — оно попадёт во все созданные миграции. Описание можно передать заранее: `php bim gen multi --d="#iws-123 поля для HL-блока"`.

Перед каждым запросом выводится список уже сгенерированных элементов (по имени метода, например `> genHlblockFieldAdd`).

**Результат:**

- для **каждого** элемента создаётся **отдельный** файл миграции; id миграций идут подряд в порядке генерации (`timestamp`, `timestamp + 1`, …), поэтому выполняются в том же порядке;
- к описанию автоматически добавляется тег `#add` или `#delete` в зависимости от команды;
- миграции сразу отмечаются как выполненные — так же, как при обычной генерации по наличию (для `delete` элементы **удаляются из БД сразу**, см. примечание в разделе [2](#gen_nal)).

**Пример сессии** — миграции для двух полей одного highload-блока:

```
php bim gen multi
Put generation commands: php bim gen > HlblockField:add
[HLBLOCK_ID]: 3
[USER_FIELD_ID]: 41
You want to repeat command (HlblockField:add) [Y]:
[HLBLOCK_ID]: 3
[USER_FIELD_ID]: 42
You want to repeat command (HlblockField:add) [Y]: n
Put generation commands: php bim gen >
Description: #iws-123 Поля для HL-блока Orders
```

Команды `add` и `delete` можно использовать в одной сессии: сначала сохраняются все миграции `add`, затем все `delete`.

## <a name="tag"></a> Тегирование миграций:

При создании нового миграционного класса существует возможность выставления тега в комментарии к миграции для дальнейшей более удобной отмены либо выполнения группы миграций связанных одним тегом.

**Формат**: #[название]

**Пример:**
Как вариант применения, вставлять тег номера задачи из трекера.

``` bash
[Description]: #IWS-242 Add new Iblock[services]
```

## <a name="logging"></a> Логирование:

Существует возможность логирования информации о состоянии выполнения или отмены миграций.

**Пример:**
``` bash
php bim up --logging
```
или
``` bash
php bim down --logging
```
**Примечание:**
По умолчанию информация сохраняется в файл вида **_log/bim/[Year]/[Month]/[Day]/bim.log**

# <a name="info"></a>7 Информация о проекте [BIM INFO]

Информация о текущем bitrix проекте:

- Название проекта
- Версия bitrix
- Редакция bitrix

**Пример:**
``` bash  
php bim info
```

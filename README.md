# Breakpoint PHP

A more useful `var_dump`!

Inspect breakpoints and handle errors with a visual data explorer

Heavily influenced by and borrowing from https://github.com/charliesome/better_errors/
and https://github.com/magnars/prone

Syntaxt highlighting using http://prismjs.com/

![image](http://i.imgur.com/F7QGBDb.png)

## Usage

Clone this repo into your project and include somewhere near the start of 
execution (e.g. top of index.php):

```php
require_once("breakpoint/src/breakpoint/breakpoint.php");
```

(Composer package coming soon)

Errors will now automatically be handled by Breakpoint.
Error levels reported are determined by the application's `error_reporting`
level.

To get a snapshot of execution and inspect a specific variable insert the
following where required:

```php
// Explore $VAR
Breakpoint::Inspect($VAR);

// Explore locally declared variables
Breakpoint::Inspect(get_defined_vars());
```

**NOTE:** Breakpoint is a development tool it should not be used in production!


## Contribute

Bugs? Features missing? Please grab a fork or raise an issue!

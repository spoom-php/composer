Spoom Composer Installer
======
Composer plugin for Spoom framework to provide nested class support, and handle extension installation. 

## Usage
To use the installer, simply set the package `type` to be `spoom-extension` in your `composer.json`, and add it as a dependency
like the following:

```json
{
  "type": "spoom-extension",
  "require": {
    "spoom-php/composer": "^1.0.0"
  },
  ...
}
```

# Autoload
The autoloader is extend the standard PSR-4 namespace definitions with nested class support. This means you could put more class in one file as long as the class name
begins with the file's name, and the remain part MUST be separated with an uppercase letter (numbers always considered as a lowercase letter). For example the
`ExampleClassInterface` class' files can be:
 
  - *Example.php*
  - *ExampleClass.php*
  - *ExampleClassInterface.php*

# Public files
Extensions can define directories (or files) to copy into the Spoom's public directory during the installation process. It's useful for editable configuration
or localization files. This can be done with adding an `extra` information into `composer.json`:

```json
{
  "extra": {
    "spoom": {
      ...,
      "public": {
        "relative/path/to/source/": "relative/target/path/",
        "Autoload.php": "spoom-composer/directory/Autoload.php",
        ...
      }
    }
  },
  ...
}
```

Target path SHOULD start with a directory named after extension's id and it will be relative from the `Autoload::DIRECTORY` directory.

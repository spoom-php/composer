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

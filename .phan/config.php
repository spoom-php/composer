<?php

/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command line arguments will be applied
 * after this file is read.
 */
return [
    // Supported values: '7.0', '7.1', '7.2', null.
    // If this is set to null,
    // then Phan assumes the PHP version which is closest to the minor version
    // of the php executable used to execute phan.
    'target_php_version' => 7.0,

    // A list of directories that should be parsed for class and
    // method information. After excluding the directories
    // defined in exclude_analysis_directory_list, the remaining
    // files will be statically analyzed for errors.
    //
    // Thus, both first-party and third-party code being used by
    // your application should be included in this list.
    'directory_list' => [
        'src/',
        'vendor/composer/'
    ],

    // A directory list that defines files that will be excluded
    // from static analysis, but whose class and method
    // information should be included.
    //
    // Generally, you'll want to include the directories for
    // third-party code (such as "vendor/") in this list.
    //
    // n.b.: If you'd like to parse but not analyze 3rd
    //       party code, directories containing that code
    //       should be added to both the `directory_list`
    //       and `exclude_analysis_directory_list` arrays.
    "exclude_analysis_directory_list" => [
        'vendor/composer/'
    ],


    // Backwards Compatibility Checking. This is slow
    // and expensive, but you should consider running
    // it before upgrading your version of PHP to a
    // new version that has backward compatibility
    // breaks. (Also see target_php_version)
    'backward_compatibility_checks' => false,
    // If true, this run a quick version of checks that takes less
    // time at the cost of not running as thorough
    // an analysis. You should consider setting this
    // to true only when you wish you had more **undiagnosed** issues
    // to fix in your code base.
    'quick_mode' => false,
    // Set to true in order to ignore issue suppression.
    // This is useful for testing the state of your code, but
    // unlikely to be useful outside of that.
    'disable_suppression' => false,

    // If enabled, check all methods that override a
    // parent method to make sure its signature is
    // compatible with the parent's. This check
    // can add quite a bit of time to the analysis.
    'analyze_signature_compatibility' => true,

    // Set to true in order to attempt to detect dead
    // (unreferenced) code. Keep in mind that the
    // results will only be a guess given that classes,
    // properties, constants and methods can be referenced
    // as variables (like `$class->$property` or
    // `$class->$method()`) in ways that we're unable
    // to make sense of.
    'dead_code_detection' => false,

    // Set to true in order to attempt to detect unused variables.
    // `dead_code_detection` will also enable unused variable detection.
    //
    // This has a few known false positives, e.g. for loops or branches.
    'unused_variable_detection' => true,

    // Enable this to warn about harmless redundant use for classes and namespaces such as `use Foo\bar` in namespace Foo.
    //
    // Note: This does not affect warnings about redundant uses in the global namespace.
    'warn_about_redundant_use_namespaced_class' => true,

    // If enabled, warn about throw statement where the exception types
    // are not documented in the PHPDoc of functions, methods, and closures.
    'warn_about_undocumented_throw_statements' => false,

    // If enabled (and `warn_about_undocumented_throw_statements` is enabled),
    // Phan will warn about function/closure/method invocations that have `@throws`
    // that aren't caught or documented in the invoking method.
    'warn_about_undocumented_exceptions_thrown_by_invoked_functions' => false,

    // This setting maps case-insensitive strings to union types.
    //
    // This is useful if a project uses phpdoc that differs from the phpdoc2 standard.
    //
    // If the corresponding value is the empty string,
    // then Phan will ignore that union type (E.g. can ignore 'the' in `@return the value`)
    //
    // If the corresponding value is not empty,
    // then Phan will act as though it saw the corresponding UnionTypes(s)
    // when the keys show up in a UnionType of `@param`, `@return`, `@var`, `@property`, etc.
    //
    // This matches the **entire string**, not parts of the string.
    // (E.g. `@return the|null` will still look for a class with the name `the`, but `@return the` will be ignored with the below setting)
    //
    // (These are not aliases, this setting is ignored outside of doc comments).
    // (Phan does not check if classes with these names exist)
    //
    // Example setting: `['unknown' => '', 'number' => 'int|float', 'char' => 'string', 'long' => 'int', 'the' => '']`
    'phpdoc_type_mapping' => [ ],
];
<?php namespace Spoom\Composer;

//
class Autoload {

  /**
   * Resource files (for edit or generated) directory
   *
   * This should be at side by side with the vendor-dir
   */
  const DIRECTORY = __DIR__ . '/../../../spoom/';
  /**
   * Absolute path for the Autoloader source file
   */
  const FILE = self::DIRECTORY . 'package.php';

  /**
   * @var static
   */
  private static $instance;

  /**
   * Store the custom namespace connections to their root paths
   *
   * @var array<string,string>
   */
  private $definition = [];

  /**
   * @param string $path
   * @param bool   $prepend
   */
  protected function __construct( string $path, bool $prepend = false ) {

    if( is_file( $path ) ) {

      /** @noinspection PhpIncludeInspection */
      $this->definition = require $path;
    }

    if( !empty( $this->definition ) ) $this->attach( $prepend );
  }

  /**
   * Re(-re)gister the autoloader
   *
   * @param bool $prepend Prepend or append to the autoload queue
   */
  public function attach( $prepend = true ) {
    $this->detach();

    // register the autoloader
    spl_autoload_register( [ $this, 'load' ], true, $prepend );
  }
  /**
   * Unregister the autoloader
   */
  public function detach() {
    spl_autoload_unregister( [ $this, 'load' ] );
  }

  /**
   * Import class files based on the class name and the namespace
   *
   * @param string $class The class fully qualified name
   *
   * @return bool True only if the class is exists after the import
   */
  public function load( $class ) {

    // do not import class that is exists already
    if( static::exist( $class ) ) return true;
    else {

      // fix for absolute class definitions
      $class = ltrim( $class, '\\' );

      $path = explode( '\\', $class );
      $name = array_pop( $path );

      // iterate trough the autloader paths
      foreach( $this->definition as $namespace => list( $directory, $length ) ) {
        if( strpos( $class, $namespace ) === 0 ) {

          // return when the loader find a perfect match, and the class really exist
          if( $this->search( $name, array_slice( $path, $length ), $directory ) && static::exist( $class ) ) {
            return true;
          }
        }
      }

      return false;
    }
  }

  /**
   * Find the class file and load it
   *
   * @param string   $name The class name
   * @param string[] $path The file path
   * @param string   $root The path's root
   *
   * @return bool True if the file was successfully loaded
   */
  protected function search( string $name, array $path, string $root ): bool {

    // check root existance and then try to find the original full named class file
    $path = ltrim( implode( DIRECTORY_SEPARATOR, $path ) . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR );
    if( $this->read( $name, $path, $root ) ) return true;
    else {

      // try to tokenize the class name (based on camel or TitleCase) to support for nested classes
      $tmp = $this->explode( $name );
      foreach( $tmp as $name ) if( $this->read( $name, $path, $root ) ) {
        return true;
      }

      return false;
    }
  }

  /**
   * Split the class name into subclassnames through the camel or TitleCase. The full classname is not included in the result array
   *
   * @example `Autoload::explode( 'My0NestedClass' ) // [ 'My0', 'My0Nested' ]`
   * @example `Autoload::explode( 'MY0NestedClass' ) // [ 'MY0', 'MY0Nested' ]`
   * @example `Autoload::explode( 'MY0Nested' ) // [ 'MY0' ]`
   *
   * @param string $name The original classname
   *
   * @return string[] desc ordered classname "tokens"
   */
  protected function explode( string $name ): array {

    $result  = [];
    $buffer  = '';
    $counter = 0;
    for( $uppercase = ctype_upper( $name{0} ), $count = strlen( $name ), $i = 0; $i < $count; ++$i ) {

      $character = $name{$i};
      if( $character == '_' ) return [];
      else if( !is_numeric( $character ) ) {

        $uppercase_now = ctype_upper( $character );
        if( $uppercase_now != $uppercase && $counter > 1 ) {
          $result[] = !$uppercase_now ? substr( $buffer, 0, -1 ) : $buffer;
          $counter  = 0;
        }

        $uppercase = $uppercase_now;
      }

      ++$counter;
      $buffer .= $character;
    }

    return array_reverse( $result );
  }

  /**
   * Read library files. This will check several case scenario of the name/path
   *
   * @param string $name The file name
   * @param string $path The path to the file from the root
   * @param string $root The path root
   *
   * @return bool True if a file exist and successfully readed
   */
  protected static function read( string $name, string $path, string $root ): bool {

    $file = $root . $path . $name;
    if( is_file( ( $tmp = $file . '.php' ) ) ) include $tmp;
    else if( defined( 'HHVM_VERSION' ) && is_file( ( $tmp = $file . '.hh' ) ) ) include $tmp;
    else {

      // there is no more options, so the file is not exists
      return false;
    }

    return true;
  }

  /**
   * Check for class, interface or trait existance
   *
   * @param string $name The fully qualified name (with namespace)
   *
   * @return bool True, if already loaded
   */
  public static function exist( string $name ): bool {
    return class_exists( $name, false ) || interface_exists( $name, false ) || trait_exists( $name, false );
  }

  /**
   * @return static
   */
  public static function instance() {
    return static::$instance ?: ( static::$instance = new static( static::FILE ) );
  }
}

//
Autoload::instance();

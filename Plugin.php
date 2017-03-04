<?php namespace Spoom\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

/**
 * Class Plugin
 * @package Spoom\Composer
 */
class Plugin implements PluginInterface, EventSubscriberInterface {

  /**
   * The Spoom's package type for extensions
   */
  const PACKAGE_TYPE = 'spoom-extension';

  /**
   * @var Composer
   */
  private $composer;
  /**
   * @var IOInterface
   */
  private $io;
  /**
   * Vendor directory
   *
   * @var string
   */
  private $directory;

  /**
   * Path to the composer.json
   *
   * @var string
   */
  private $root;

  //
  public function activate( Composer $composer, IOInterface $io ) {

    //
    $this->composer = $composer;
    $this->io       = $io;

    //
    // FIXME I'm not sure this will always be the path to the composer.json...
    $this->root      = rtrim( realpath( realpath( getcwd() ) ), '/' ) . '/';
    $this->directory = rtrim( realpath( realpath( $composer->getConfig()->get( 'vendor-dir' ) ) ), '/' ) . '/';
  }

  /**
   * Rewrite autoload paths
   *
   * @param array $list Namespace (key) with their paths (value)
   */
  public function writeAutoload( array $list ) {

    $file = Autoload::FILE;
    if( is_dir( dirname( $file ) ) || mkdir( dirname( $file ), 0777, true ) ) {

      //
      uksort( $list, function ( $a, $b ) {
        return strlen( $b ) - strlen( $a );
      } );

      //
      $content = [];
      foreach( $list as $namespace => $path ) {

        $count     = substr_count( $namespace, '\\' );
        $namespace = str_replace( [ '\\', "'" ], [ '\\\\', "\\'" ], $namespace );
        $path      = str_replace( [ '\\', "'" ], [ '\\\\', "\\'" ], $path );

        $content[] = "  '{$namespace}' => [ '{$path}', {$count} ]";
      }

      // 
      file_put_contents( $file, "<?php return [\n" . implode( ",\n", $content ) . "\n];\n" );

      // invalidate opcache
      if( function_exists( 'opcache_invalidate' ) ) {
        opcache_invalidate( $file, true );
      }
    }
  }

  /**
   * Regenerate Spoom's package list on autoload-dump
   */
  public function onBeforeAutoloadDump() {

    $filesystem = new Filesystem();
    $tmp        = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
    array_unshift( $tmp, $this->composer->getPackage() );

    $list = [];
    foreach( $tmp as $package ) {
      if( $package->getType() == static::PACKAGE_TYPE ) {

        $autoload = $package->getAutoload();
        if( isset( $autoload[ 'psr-4' ] ) ) {
          foreach( $autoload[ 'psr-4' ] as $namespace => $path ) {

            // don't handle multi directory namespace
            if( is_array( $path ) ) $this->io->writeError( 'Spoom\Composer: Ignoring multiple psr-4 namespaces of ' . $package->getPrettyName() );
            else {

              // choose root directory based on the path (absolute path has no root) or package (main or vendor)
              if( $filesystem->isAbsolutePath( $path ) ) $directory = '';
              else if( $package === $this->composer->getPackage() ) $directory = $this->root;
              else $directory = $this->directory . $package->getPrettyName() . '/';

              // normalize and ensure the trailing slash in the path
              $list[ $namespace ] = $filesystem->normalizePath( $directory . $path ) . '/';
            }
          }
        }
      }
    }

    // create autoload file from the package list
    $this->writeAutoload( $list );
  }

  //
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::PRE_AUTOLOAD_DUMP => 'onBeforeAutoloadDump'
    ];
  }
}

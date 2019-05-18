<?php namespace Spoom\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

//
class Plugin implements PluginInterface, EventSubscriberInterface {

  /**
   * The Spoom's package type
   */
  const PACKAGE_TYPE = 'spoom';
  /**
   * The Spoom's legacy package (extension) type
   *
   * @deprecated Use `PACKAGE_TYPE`, this should be removed after v2.0.0
   */
  const PACKAGE_TYPE_LEGACY = 'spoom-extension';

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
    $filesystem      = new Filesystem();
    $this->root      = rtrim( $filesystem->normalizePath( realpath( realpath( getcwd() ) ) ), '/' ) . '/';
    $this->directory = rtrim( $filesystem->normalizePath( realpath( realpath( $composer->getConfig()->get( 'vendor-dir' ) ) ) ), '/' ) . '/';

    // extend composer with this custom installer
    $installer = new Installer( $this, $io, $composer );
    $composer->getInstallationManager()->addInstaller( $installer );
  }

  /**
   * Regenerate Spoom's package list on autoload-dump
   */
  public function onBeforeAutoloadDump() {

    // FIXME Composer call this AFTER the plugin remove, so we need this check to prevent a fatal error
    clearstatcache();
    if( file_exists( __DIR__ . '/Plugin.php' ) ) {

      //
      $filesystem = new Filesystem();
      $tmp        = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
      array_unshift( $tmp, $this->composer->getPackage() );

      $list = [];
      foreach( $tmp as $package ) {
        if( in_array( $package->getType(), [ static::PACKAGE_TYPE, static::PACKAGE_TYPE_LEGACY ] ) ) {

          // choose root directory based on the path (absolute path has no root) or package (main or vendor)
          if( $package === $this->composer->getPackage() ) $directory = $this->root;
          else $directory = $this->getDirectory( $package );

          // process autoloader
          $autoload = $package->getAutoload();
          if( isset( $autoload[ 'psr-4' ] ) ) {
            foreach( $autoload[ 'psr-4' ] as $namespace => $path ) {

              // don't handle multi directory namespace
              if( is_array( $path ) ) $this->io->writeError( 'Spoom\Composer: Ignoring multiple psr-4 namespaces of ' . $package->getPrettyName() );
              else {

                // normalize and ensure the trailing slash in the path
                $list[ $namespace ] = $filesystem->normalizePath( ( $filesystem->isAbsolutePath( $path ) ? '' : $directory ) . $path ) . '/';
              }
            }
          }
        }
      }

      $this->io->write( 'Spoom\Composer: Generating autoload file for ' . count( $list ) . ' package(s)' );

      // create autoload file from the package list
      $this->writeAutoload( $list );
    }
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

        // detect if the path can be relative (then remove the root)
        $is_absolute = substr( $path, 0, strlen( $this->root ) ) != $this->root;
        if( !$is_absolute ) $path = substr( $path, strlen( $this->root ) );

        $count     = substr_count( $namespace, '\\' );
        $namespace = str_replace( [ '\\', "'" ], [ '\\\\', "\\'" ], $namespace );
        $path      = str_replace( [ '\\', "'" ], [ '\\\\', "\\'" ], $path );

        $prefix    = $is_absolute ? "'" : "__DIR__ . '/../";
        $content[] = "  '{$namespace}' => [ {$prefix}{$path}', {$count} ]";
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
   * Get absolute directory of a package
   *
   * @param PackageInterface $package
   *
   * @return string
   */
  public function getDirectory( PackageInterface $package ) {
    return $this->directory . $package->getPrettyName() . '/';
  }
  /**
   * Get exposed file list from a package
   *
   * @param PackageInterface $package
   * @param array            $map Relatice source and destination of file/directories
   *
   * @return array Absolute source and destination of files
   */
  public function getFileList( PackageInterface $package, $map ) {
    $directory = $this->getDirectory( $package );
    $result    = [];

    try {

      foreach( $map as $source => $destination ) {
        if( !is_dir( $directory . $source ) ) $result[ $directory . $source ] = Autoload::DIRECTORY . $destination;
        else {

          $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $directory . $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
            \RecursiveIteratorIterator::SELF_FIRST
          );

          foreach( $iterator as $item ) {
            /** @var SplFileInfo $item */
            if( !$item->isDir() ) {
              $result[ $item->getRealPath() ] = Autoload::DIRECTORY . $destination . DIRECTORY_SEPARATOR . call_user_func( [ $iterator, 'getSubPathName' ] );
            }
          }
        }
      }

    } catch( \Throwable $e ) {
      $this->io->writeError( "Spoom\\Composer: Skip {$package->getPrettyName()} public copy, due to an error" );
    }

    return $result;
  }
  /**
   * @param array $map Absolute source and destination of files
   */
  public function createFileList( $map ) {

    $filesystem = new Filesystem();
    foreach( $map as $source => $destination ) try {

      $tmp = dirname( $destination );
      $filesystem->ensureDirectoryExists( $tmp );

      if( !file_exists( $destination ) ) copy( $source, $destination );
      else {

        // TODO implement better file matching
        $different = filesize( $source ) != filesize( $destination );
        if( $different ) {
          $this->io->writeError( "Spoom\\Composer: Skip '{$source}' path copy to '{$destination}', it's already exists" );
        }

      }
    } catch( \Throwable $e ) {
      $this->io->writeError( "Spoom\\Composer: Skip '{$source}' path copy to '{$destination}', due to an error" );
    }
  }
  /**
   * @param array $list Absolute files to remove
   */
  public function removeFileList( $list ) {

    // sort the list in desc mode, to iterate deeper paths first
    usort( $list, function ( $a, $b ) { return strlen( $b ) - strlen( $a ); } );

    // remove files, and collect directories
    $clear = [];
    foreach( $list as $path ) {
      if( is_file( $path ) ) {

        // check permission
        if( !is_writeable( $path ) ) $this->io->writeError( "Spoom\\Composer: Skip '{$path}' path remove, no permission" );
        else {

          unlink( $path );
          $clear[] = dirname( $path );
        }
      }
    }

    // remove empty directories
    $clear = array_unique( $clear );
    foreach( $clear as $path ) {
      if( is_dir( $path ) && !( new \FilesystemIterator( $path ) )->valid() ) {

        // check permission
        if( !is_writeable( $path ) ) $this->io->writeError( "Spoom\\Composer: Skip '{$path}' path remove, no permission" );
        else rmdir( $path );
      }
    }
  }

  //
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::PRE_AUTOLOAD_DUMP => 'onBeforeAutoloadDump'
    ];
  }
}

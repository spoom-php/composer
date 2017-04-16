<?php namespace Spoom\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Plugin\PluginInterface;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * Class Installer
 * @package Spoom\Composer
 */
class Installer extends LibraryInstaller {

  /**
   * @var Plugin
   */
  private $plugin;

  //
  public function __construct( PluginInterface $plugin, IOInterface $io, Composer $composer ) {
    parent::__construct( $io, $composer );

    $this->plugin = $plugin;
  }

  //
  public function install( InstalledRepositoryInterface $repo, PackageInterface $package ) {
    parent::install( $repo, $package );

    // process Spoom related extra informations
    $extra = $package->getExtra();
    if( isset( $extra[ 'spoom' ][ 'public' ] ) ) {
      $list = $this->plugin->getFileList( $package, $extra[ 'spoom' ][ 'public' ] );
      $this->plugin->createFileList( $list );
    }
  }
  //
  public function update( InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target ) {
    parent::update( $repo, $initial, $target );

    $list = [];

    // process Spoom public files
    $extra = $target->getExtra();
    if( isset( $extra[ 'spoom' ][ 'public' ] ) ) {
      $list = $this->plugin->getFileList( $target, $extra[ 'spoom' ][ 'public' ] );
      $this->plugin->createFileList( $list );
    }

    // remove unnecessary files and directories
    $extra = $initial->getExtra();
    if( isset( $extra[ 'spoom' ][ 'public' ] ) ) {
      $list = array_diff( $this->plugin->getFileList( $initial, $extra[ 'spoom' ][ 'public' ] ), $list );
      $this->plugin->removeFileList( array_values( $list ) );
    }
  }
  //
  public function uninstall( InstalledRepositoryInterface $repo, PackageInterface $package ) {

    // collect removeable files for the extension
    $extra = $package->getExtra();
    if( isset( $extra[ 'spoom' ][ 'public' ] ) ) {
      $list = $this->plugin->getFileList( $package, $extra[ 'spoom' ][ 'public' ] );
    }

    // uninstall package as normal
    parent::uninstall( $repo, $package );

    // remove the collected files
    if( !empty( $list ) ) {
      $this->plugin->removeFileList( array_values( $list ) );
    }
  }

  //
  public function supports( $packageType ) {
    return $packageType === Plugin::PACKAGE_TYPE;
  }
}

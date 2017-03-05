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

    //
    $this->installExtension( $package );
  }
  //
  public function update( InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target ) {
    parent::update( $repo, $initial, $target );

    //
    $this->updateExtension( $target, $initial );
  }
  //
  public function uninstall( InstalledRepositoryInterface $repo, PackageInterface $package ) {
    parent::uninstall( $repo, $package );

    //
    $this->uninstallExtension( $package );
  }

  //
  public function supports( $packageType ) {
    return $packageType === Plugin::PACKAGE_TYPE;
  }

  /**
   * @param PackageInterface $package
   */
  protected function installExtension( PackageInterface $package ) {

    // process Spoom related extra informations
    $extra = $package->getExtra();
    if( isset( $extra[ 'spoom' ][ 'public' ] ) ) {
      $list = $this->plugin->getFileList( $package, $extra[ 'spoom' ][ 'public' ] );
      $this->plugin->createFileList( $list );
    }
  }
  /**
   * @param PackageInterface $package
   * @param PackageInterface $package_installed
   */
  protected function updateExtension( PackageInterface $package, PackageInterface $package_installed ) {
    $list = [];

    // process Spoom public files
    $extra = $package->getExtra();
    if( isset( $extra[ 'spoom' ][ 'public' ] ) ) {
      $list = $this->plugin->getFileList( $package, $extra[ 'spoom' ][ 'public' ] );
      $this->plugin->createFileList( $list );
    }

    // remove unnecessary files and directories
    $extra = $package->getExtra();
    if( isset( $extra[ 'spoom' ][ 'public' ] ) ) {
      $list = array_diff( $this->plugin->getFileList( $package_installed, $extra[ 'spoom' ][ 'public' ] ), $list );
      $this->plugin->removeFileList( array_values( $list ) );
    }
  }
  /**
   * @param PackageInterface $package
   */
  protected function uninstallExtension( PackageInterface $package ) {

    // remove unnecessary files and directories
    $extra = $package->getExtra();
    if( isset( $extra[ 'spoom' ][ 'public' ] ) ) {
      $list = $this->plugin->getFileList( $package, $extra[ 'spoom' ][ 'public' ] );
      $this->plugin->removeFileList( array_values( $list ) );
    }
  }
}

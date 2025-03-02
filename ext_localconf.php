<?php
defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use WapplerSystems\FilecollectionGallery\Controller\GalleryController;

ExtensionUtility::configurePlugin(
    'filecollection_gallery',
    'Gallery',
    [
        GalleryController::class => 'list,nested,nestedFromFolder,listFromFolder',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);


ExtensionManagementUtility::addPageTSConfig(
    "@import 'EXT:filecollection_gallery/Configuration/TsConfig/ContentElementWizard.tsconfig'"
);

<?php

namespace WapplerSystems\FilecollectionGallery\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Extbase\Mvc\View\ViewResolverInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\View\AbstractTemplateView;
use WapplerSystems\FilecollectionGallery\Service\FileCollectionService;
use WapplerSystems\FilecollectionGallery\Service\FolderService;

/**
 * GalleryController
 *
 * @author Sven Wappler <typo3@wappler.systems>
 */
class GalleryController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{


    public function __construct(readonly FileCollectionService $fileCollectionService, readonly FileCollectionRepository $fileCollectionRepository, readonly FolderService $folderService, readonly ViewResolverInterface $viewResolver)
    {

    }


    /**
     * Initializes the view before invoking an action method.
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     */
    protected function initializeView($view)
    {
        $view->assign('contentObjectData', $this->request->getAttribute('currentContentObject')->data);
    }

    /**
     * List action
     *
     * @param int $offset The offset
     *
     * @return ResponseInterface
     * @throws ResourceDoesNotExistException
     */
    public function listAction($offset = 0): ResponseInterface
    {

        $collectionUids = (trim($this->settings['fileCollection']) !== '') ? explode(',', $this->settings['fileCollection']) : [];
        if (isset($this->settings['inlineFileCollection'])) {
            $collectionUids = array_merge($collectionUids, explode(',', $this->settings['inlineFileCollection']));
        }
        $cObj = $this->request->getAttribute('currentContentObject');
        $currentUid = $cObj->data['uid'];
        $columnPosition = $cObj->data['colPos'];
        /** @var AbstractFileCollection $collection */
        $collection = null;

        $showBackToGallerySelectionLink = false;

        if ($collectionUids === []) {
            return $this->htmlErrorResponse('LLL:EXT:filecollection_gallery/Resources/Private/Language/locallang.xlf:error.noGallerySelected');
        }

        if ($this->request->hasArgument('galleryUID')) {
            $gallery = [$this->request->getArgument('galleryUID')];
            $mediaItems = $this->fileCollectionService->getFileObjectsFromCollection($gallery);
            $collection = $this->fileCollectionRepository->findByUid($this->request->getArgument('galleryUID'));
            $showBackToGallerySelectionLink = true;
        } else {
            $mediaItems = $this->fileCollectionService->getFileObjectsFromCollection($collectionUids);
        }

        if ($collection === null && count($collectionUids) === 1) {
            $collection = $this->fileCollectionRepository->findByUid($collectionUids[0]);
        }

        if ($collection !== null) {
            $collection->loadContents();
            $this->view->assign('galleryListName', $collection->getTitle());
        }

        $this->view->assignMultiple([
            'mediaItems' => $mediaItems,
            'currentUid' => $currentUid,
            'columnPosition' => $columnPosition,
            'showBackToGallerySelectionLink' => $showBackToGallerySelectionLink
        ]);

        return $this->htmlResponse();
    }

    /**
     * List from folder action
     *
     * @param int $offset The offset
     *
     * @return ResponseInterface
     */
    public function listFromFolderAction($offset = 0): ResponseInterface
    {
        if ($this->settings['fileCollection'] !== '' && $this->settings['fileCollection']) {
            $cObj = $this->configurationManager->getContentObject();
            $currentUid = $cObj->data['uid'];
            $columnPosition = $cObj->data['colPos'];

            $showBackToGallerySelectionLink = false;
            $mediaItems = [];
            //if a special gallery is requested
            if ($this->request->hasArgument('galleryFolder') && $this->request->hasArgument('galleryUID')) {
                $galleryFolderHash = $this->request->getArgument('galleryFolder');
                $galleryUid = [$this->request->getArgument('galleryUID')];
                $mediaItems = $this->fileCollectionService->getGalleryItemsByFolderHash($galleryUid, $galleryFolderHash);
                $showBackToGallerySelectionLink = true;
            }

            if ($mediaItems) {
                $this->view->assign('galleryFolderName', $this->folderService->getFolderByFile($mediaItems[0])->getName());
            }

            $this->view->assignMultiple($this->fileCollectionService->buildArrayForAssignToView(
                $mediaItems,
                $offset,
                $this->fileCollectionService->buildPaginationArray($this->settings),
                $this->settings,
                $currentUid,
                $columnPosition,
                $showBackToGallerySelectionLink
            ));
        }

        return $this->htmlResponse();
    }

    /**
     * Nested action
     *
     * @param int $offset The offset
     *
     * @return void
     */
    public function nestedAction($offset = 0): ResponseInterface
    {
        if ($this->settings['fileCollection'] !== '' && $this->settings['fileCollection']) {
            $cObj = $this->configurationManager->getContentObject();
            $currentUid = $cObj->data['uid'];
            $columnPosition = $cObj->data['colPos'];

            $collectionUids = explode(',', $this->settings['fileCollection']);

            $mediaItems = $this->fileCollectionService->getGalleryCoversFromCollections($collectionUids);

            $this->view->assignMultiple($this->fileCollectionService->buildArrayForAssignToView(
                $mediaItems,
                $offset,
                $this->fileCollectionService->buildPaginationArrayForNested($this->settings),
                $this->settings,
                $currentUid,
                $columnPosition,
                false
            ));
        }

        return $this->htmlResponse();
    }

    /**
     * Nested action
     *
     * @param int $offset The offset
     *
     * @return void
     */
    public function nestedFromFolderAction($offset = 0): ResponseInterface
    {
        if ($this->settings['fileCollection'] !== '' && $this->settings['fileCollection']) {
            $cObj = $this->configurationManager->getContentObject();
            $currentUid = $cObj->data['uid'];
            $columnPosition = $cObj->data['colPos'];

            $collectionUids = explode(',', $this->settings['fileCollection']);

            $mediaItems = $this->fileCollectionService->getGalleryCoversFromNestedFoldersCollection($collectionUids);

            $this->view->assignMultiple($this->fileCollectionService->buildArrayForAssignToView(
                $mediaItems,
                $offset,
                $this->fileCollectionService->buildPaginationArrayForNested($this->settings),
                $this->settings,
                $currentUid,
                $columnPosition,
                false
            ));
        }

        return $this->htmlResponse();
    }



    protected function htmlErrorResponse(?string $errorLabel = null): ResponseInterface
    {
        $view = $this->viewResolver->resolve(
            $this->request->getControllerObjectName(),
            $this->request->getControllerActionName(),
            $this->request->getFormat()
        );
        $this->setViewConfiguration($view);
        if ($view instanceof AbstractTemplateView) {
            $renderingContext = $view->getRenderingContext();
            if ($renderingContext instanceof RenderingContext) {
                $renderingContext->setRequest($this->request);
            }
            $renderingContext->setControllerAction('error');
            $templatePaths = $view->getRenderingContext()->getTemplatePaths();
            $templatePaths->fillDefaultsByPackageName($this->request->getControllerExtensionKey());
            $templatePaths->setFormat($this->request->getFormat());
        }
        $view->assign('errorLabel', $errorLabel);

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withStatus(500)
            ->withBody($this->streamFactory->createStream($view->render()));
    }
}

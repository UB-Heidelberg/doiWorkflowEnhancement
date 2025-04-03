<?php

/**
 * @file plugins/generic/doiWorkflowEnhancement/pages/DoiWorkflowEnhancementHandler.php
 */

namespace APP\plugins\generic\doiWorkflowEnhancement\pages;

use APP\core\Request;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\monograph\ChapterDAO;
use APP\plugins\generic\doiWorkflowEnhancement\DoiWorkflowEnhancementPlugin;
use APP\publicationFormat\PublicationFormatDAO;
use PKP\context\Context;
use PKP\core\JSONMessage;
use PKP\core\PKPRequest;
use PKP\db\DAORegistry;
use PKP\doi\exceptions\DoiException;
use PKP\security\authorization\DoisEnabledPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\submissionFile\SubmissionFile;

class DoiWorkflowEnhancementHandler extends Handler
{
    private DoiWorkflowEnhancementPlugin $plugin;

    /**
     * Constructor
     */
    public function __construct(DoiWorkflowEnhancementPlugin $plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;
    }

    /**
     * @param Request $request
     * @param array $args
     * @param array $roleAssignments
     *
     * @return bool
     */
    public function authorize($request, &$args, $roleAssignments): bool
    {
        $this->addPolicy(new UserRolesRequiredPolicy($request), true);

        // DOIs must be enabled to access DOI API endpoints
        $this->addPolicy(new DoisEnabledPolicy($request->getContext()));

        return parent::authorize($request, $args, $roleAssignments);
    }
    

    /**
     * Ajax request
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return null|JSONMessage
     */
    public function ajax(array $args, PKPRequest $request): JSONMessage|null
    {
        $userVars = $request->getUserVars();
        $user = $request->getUser();
        $context = $request->getContext();
        $plugin = $this->plugin;

        if (empty($userVars['id'])
            || empty($userVars['uid'])
            || !$context
            || null === $plugin) {
            $request->getDispatcher()->handle404();
        }

        $doiPrefix = $context->getData(Context::SETTING_DOI_PREFIX);
        $submissionId = (int) $userVars['id'];
        $uid = $userVars['uid'];
        $uid = explode('-', $uid);
        if (empty($doiPrefix) || count($uid) !== 3 || (int) $uid[0] !== $submissionId) {
            $request->getDispatcher()->handle404();
        }

        $submission = Repo::submission()->get($submissionId);
        if ($submission === null) {
            $request->getDispatcher()->handle404();
        }

        $publication = $submission->getCurrentPublication();
        if ($publication === null) {
            $request->getDispatcher()->handle404();
        }

        $contentType = $uid[1];
        $contentId = (int) $uid[2];
        $doiCreationFailures = [];
        $doiId = '';

        switch ($contentType) {
            case 'monograph':
                if (empty($publication->getData('doiId'))
                    && $context->isDoiTypeEnabled(Repo::doi()::TYPE_PUBLICATION)) {
                    try {
                        $doiId = Repo::doi()->mintPublicationDoi($publication, $submission, $context);
                        Repo::publication()->edit($publication, ['doiId' => $doiId]);
                    } catch (DoiException $exception) {
                        $doiCreationFailures[] = $exception;
                    }
                }
                break;
            case 'chapter':
                /** @var ChapterDAO $chapterDao */
                $chapterDao = DAORegistry::getDAO('ChapterDAO');
                $chapter = $chapterDao->getChapter($contentId, $publication->getId());
                if ($chapter === null) {
                    $chapter = $chapterDao->getBySourceChapterAndPublication($contentId, $publication->getId());
                }
                if ($chapter !== null
                    && empty($chapter->getData('doiId'))
                    && $context->isDoiTypeEnabled(Repo::doi()::TYPE_CHAPTER)) {
                        try {
                            $doiId = Repo::doi()->mintChapterDoi($chapter, $submission, $context);
                            $chapter->setData('doiId', $doiId);
                            $chapterDao->updateObject($chapter);
                        } catch (DoiException $exception) {
                            $doiCreationFailures[] = $exception;
                        }
                }
                break;
            case 'representation': // Publication format
                /** @var PublicationFormatDAO $publicationFormatDao */
                $publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
                $publicationFormat = $publicationFormatDao->getByBestId($contentId, $publication->getId() );
                if ($publicationFormat !== null
                    && empty($publicationFormat->getData('doiId'))
                    && $context->isDoiTypeEnabled(Repo::doi()::TYPE_REPRESENTATION)) {
                        try {
                            $doiId = Repo::doi()->mintPublicationFormatDoi($publicationFormat, $submission, $context);
                            $publicationFormat->setData('doiId', $doiId);
                            $publicationFormatDao->updateObject($publicationFormat);
                        } catch (DoiException $exception) {
                            $doiCreationFailures[] = $exception;
                        }
                }
                break;
            case 'file': // Submission file
                if ($context->isDoiTypeEnabled(Repo::doi()::TYPE_SUBMISSION_FILE)) {
                    // Get all submission files assigned to a publication format
                    $submissionFiles = Repo::submissionFile()
                        ->getCollector()
                        ->filterBySubmissionIds([$submissionId])
                        ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_PROOF])
                        ->getMany();

                    /** @var SubmissionFile $submissionFile */
                    foreach ($submissionFiles as $submissionFile) {
                        if ($submissionFile->getId() === $contentId
                            && empty($submissionFile->getData('doiId'))) {
                            try {
                                $doiId = Repo::doi()->mintSubmissionFileDoi($submissionFile, $submission, $context);
                                Repo::submissionFile()->edit($submissionFile, ['doiId' => $doiId]);
                            } catch (DoiException $exception) {
                                $doiCreationFailures[] = $exception;
                            }
                        }
                    }
                }
                break;
            default:
                $request->getDispatcher()->handle404();
                break;
        }

        if (empty($doiCreationFailures) && !empty($doiId)) {
            $doi = Repo::doi()->get($doiId);
            if ($doi !== null) {
                $doi = $doi->getDoi();
                return new JSONMessage(true, [
                    'doiId' => $doiId,
                    'doi' => $doi,
                    'uid' => $userVars['uid'],
                    'submissionId' => $submissionId,
                ]);
            }

        }

        return new JSONMessage(
            false,
            ['failedDoiActions' => array_map(
                function (DoiException $item) {
                    return $item->getMessage();
                },
                $doiCreationFailures
            )]);
    }
}

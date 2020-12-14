<?php

declare(strict_types=1);

namespace App\Controller;

use App\Event\JobReadyEvent;
use App\Exception\MissingTestSourceException;
use App\Model\Manifest;
use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use App\Response\BadAddSourcesRequestResponse;
use App\Response\BadJobCreateRequestResponse;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use App\Services\SourceFactory;
use App\Services\TestSerializer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\JobFactory;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;
use webignition\BasilWorker\PersistenceBundle\Services\Store\SourceStore;

class JobController extends AbstractController
{
    private JobStore $jobStore;

    public function __construct(JobStore $jobStore)
    {
        $this->jobStore = $jobStore;
    }

    /**
     * @Route("/create", name="create", methods={"POST"})
     */
    public function create(JobFactory $jobFactory, JobCreateRequest $request): JsonResponse
    {
        if ('' === $request->getLabel()) {
            return BadJobCreateRequestResponse::createLabelMissingResponse();
        }

        if ('' === $request->getCallbackUrl()) {
            return BadJobCreateRequestResponse::createCallbackUrlMissingResponse();
        }

        if (null === $request->getMaximumDurationInSeconds()) {
            return BadJobCreateRequestResponse::createMaximumDurationMissingResponse();
        }

        if (true === $this->jobStore->has()) {
            return BadJobCreateRequestResponse::createJobAlreadyExistsResponse();
        }

        $jobFactory->create(
            $request->getLabel(),
            $request->getCallbackUrl(),
            $request->getMaximumDurationInSeconds()
        );

        return new JsonResponse();
    }

    /**
     * @Route("/add-sources", name="add-sources", methods={"POST"})
     */
    public function addSources(
        SourceStore $sourceStore,
        SourceFactory $sourceFactory,
        EventDispatcherInterface $eventDispatcher,
        AddSourcesRequest $addSourcesRequest
    ): JsonResponse {
        if (false === $this->jobStore->has()) {
            return BadAddSourcesRequestResponse::createJobMissingResponse();
        }

        if (true === $sourceStore->hasAny()) {
            return BadAddSourcesRequestResponse::createSourcesNotEmptyResponse();
        }

        $manifest = $addSourcesRequest->getManifest();
        if (!$manifest instanceof Manifest) {
            return BadAddSourcesRequestResponse::createManifestMissingResponse();
        }

        $manifestTestPaths = $manifest->getTestPaths();
        if ([] === $manifestTestPaths) {
            return BadAddSourcesRequestResponse::createManifestEmptyResponse();
        }

        $uploadedSources = $addSourcesRequest->getUploadedSources();

        try {
            $sourceFactory->createCollectionFromManifest($manifest, $uploadedSources);
        } catch (MissingTestSourceException $testSourceException) {
            return BadAddSourcesRequestResponse::createSourceMissingResponse($testSourceException->getPath());
        }

        $eventDispatcher->dispatch(new JobReadyEvent());

        return new JsonResponse();
    }

    /**
     * @Route("/status", name="status", methods={"GET"})
     */
    public function status(
        SourceStore $sourceStore,
        TestRepository $testRepository,
        TestSerializer $testSerializer,
        CompilationState $compilationState,
        ExecutionState $executionState
    ): JsonResponse {
        if (false === $this->jobStore->has()) {
            return new JsonResponse([], 400);
        }

        $job = $this->jobStore->get();
        $tests = $testRepository->findAll();

        $data = array_merge(
            $job->jsonSerialize(),
            [
                'sources' => $sourceStore->findAllPaths(),
                'compilation_state' => $compilationState->getCurrentState(),
                'execution_state' => $executionState->getCurrentState(),
                'tests' => $testSerializer->serializeCollection($tests),
            ]
        );

        return new JsonResponse($data);
    }
}

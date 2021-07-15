<?php


namespace App\Controller;


use App\Helper\EntidadeFactory;
use App\Helper\ExtratorDadosRequest;
use App\Helper\ResponseFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends AbstractController
{

    protected ObjectRepository $repository;
    protected EntityManagerInterface $entityManager;
    protected EntidadeFactory $factory;
    private ExtratorDadosRequest $extratorDadosRequest;
    private CacheItemPoolInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ObjectRepository $repository,
        EntidadeFactory $factory,
        ExtratorDadosRequest $extratorDadosRequest,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        $this->repository           = $repository;
        $this->entityManager        = $entityManager;
        $this->factory              = $factory;
        $this->extratorDadosRequest = $extratorDadosRequest;
        $this->cache                = $cache;
        $this->logger               = $logger;
    }

    public function buscarTodos(Request $request): Response
    {
        $orderInfo   = $this->extratorDadosRequest->buscaDadosOrdenacao($request);
        $filterOrder = $this->extratorDadosRequest->buscaDadosFiltro($request);
        [
            $paginaAtual,
            $itensPorPagina,
        ] = $this->extratorDadosRequest->buscaDadosPaginado($request);

        $entityList = $this->repository->findBy(
            $filterOrder,
            $orderInfo,
            $itensPorPagina,
            ($paginaAtual - 1) * $itensPorPagina

        );

        $fabricaResposta = new ResponseFactory(
            true,
            $entityList,
            Response::HTTP_OK,
            $paginaAtual,
            $itensPorPagina,
        );

        return $fabricaResposta->getResponse();
    }

    public function buscarUm(int $id): Response
    {
        $entidade = $this->cache->hasItem($this->cachePrefix() . $id) ?
            $this->cache->getItem($this->cachePrefix() . $id)->get() :
            $this->repository->find($id);

        $responseCode = is_null($entidade)
            ? JsonResponse::HTTP_NO_CONTENT
            : JsonResponse::HTTP_OK;

        $fabricaResposta = new ResponseFactory(
            true,
            $entidade,
            $responseCode
        );

        return $fabricaResposta->getResponse();
    }

    public function remove(int $id): JsonResponse
    {
        $entidade = $this->repository->find($id);
        $this->entityManager->remove($entidade);
        $this->entityManager->flush();

        $this->cache->deleteItem($this->cachePrefix() . $id);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    public function novo(Request $request): Response
    {
        $dadosRequest = $request->getContent();

        $entidade = $this->factory->criarEntidade($dadosRequest);
        $this->entityManager->persist($entidade);
        $this->entityManager->flush();

        $cacheItem = $this->cache->getItem(
            $this->cachePrefix() .
            $entidade->getId()
        );
        $cacheItem->set($entidade);

        $this->cache->save($cacheItem);

        $this->logger
            ->notice(
                'Novo registro de {entidade} adicionado com id: {id}.',
                [
                    'entidade' => get_class($entidade),
                    'id'       => $entidade->getId(),
                ]
            );

        return new JsonResponse($entidade, Response::HTTP_CREATED);
    }

    public function atualiza(int $id, Request $request): JsonResponse
    {
        $corpoRequisicao = $request->getContent();
        $entidadeEnviada = $this->factory->criarEntidade($corpoRequisicao);

        $entidadeExistente = $this->repository->find($id);

        if (is_null($entidadeExistente)) {
            return new JsonResponse(['Registro não encontrado.'],
                JsonResponse::HTTP_NOT_FOUND);
        }

        $this->atualizarEntidadeExistente($entidadeExistente, $entidadeEnviada);

        $this->entityManager->flush();

        $cacheItem = $this->cache->getItem($this->cachePrefix() . $id);

        $cacheItem->set($entidadeExistente);
        $this->cache->save($cacheItem);

        return new JsonResponse($entidadeExistente);
    }

    abstract public function atualizarEntidadeExistente(
        $entidadeExistente,
        $entidadeEnviada
    );

    abstract public function cachePrefix(): string;

}
<?php


namespace App\EventListeners;

use App\Helper\EntityFactoryException;
use App\Helper\ResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionHandler implements EventSubscriberInterface
{

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [
                ['handleEntityException', 1],
                ['handle404Exception', 0],
                ['handleGenericException', -1],
            ],
        ];
    }

    public function handle404Exception(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundHttpException) {
            $response = new ResponseFactory(
                false,
                ['mensagem' => $exception->getMessage()],
                404
            );
            /*
             * PODE SER FEITO ASSIM:
            $response = HypermidiaResponse::fromError($exception)
                                          ->getResponse();
            $response->setStatusCode($exception->getStatusCode());
             * PODE SER FEITO ASSIM TAMBÉM:
             * Dessa forma qualquer exception passaria por aqui e caso não tenha
             * status code retorna 400 (bad request)
             $exception = $event->getThrowable();
            if ($exception instanceof \Exception) {
                $response = new ResponseFactory(
                    false,
                    ['mensagem' => $exception->getMessage()],
                    method_exists($exception,
                        'getStatusCode') ? $exception->getStatusCode() : 500,
            );
            */
            $event->setResponse($response->getResponse());
        }
    }

    public function handleEntityException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if ($exception instanceof EntityFactoryException) {
            $response = new ResponseFactory(
                false,
                ['mensagem' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
            $event->setResponse($response->getResponse());
        }
    }

    public function handleGenericException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $this->logger->critical(
            'Uma exceção ocorreu. {stack}',
            [
                'stack' => $exception->getTraceAsString(),
            ]
        );
        $response = new ResponseFactory(
            false,
            ['mensagem' => $exception->getMessage()],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
        $event->setResponse($response->getResponse());
    }

}
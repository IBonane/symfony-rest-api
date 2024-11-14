<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if(in_array('application/json', $event->getRequest()->getAcceptableContentTypes(), true)){
            
            $data = [];

            if($exception instanceof HttpException){
                $data = [
                    'status' => $exception->getStatusCode(),
                    'message' => $exception->getMessage(),
                ];
            }
            else{
                $data = [
                    'status' => 500,
                    'message' => $exception->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse($data));

        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onExceptionEvent',
        ];
    }
}

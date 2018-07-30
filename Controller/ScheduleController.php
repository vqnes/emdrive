<?php

namespace Emdrive\Controller;

use Emdrive\Service\ScheduleService;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ScheduleController
{
    private $twig;

    private $scheduleService;

    public function __construct(\Twig_Environment $twig = null, ScheduleService $scheduleService)
    {
        $this->twig = $twig;
        $this->scheduleService = $scheduleService;
    }

    public function __invoke(Request $request, $area = 'default')
    {
        if ($request->get('list')) {
            return new JsonResponse(['items' => $this->scheduleService->getAll()]);
        } elseif ($name = $request->get('name')) {
            if ($request->get('run')) {
                $fields = ['next_start_at' => date('Y-m-d H:i:s')];
            } else {
                $fields = $request->get('fields');
            }

            $this->scheduleService->updateJob($name, $fields);

            return new JsonResponse();
        }

        $view = [];
        if ('' !== $request->getBaseUrl()) {
            $view['basePath'] = $request->getBaseUrl();
        }



        return new Response(
            $this->twig->render('@Emdrive/Schedule/index.html.twig', $view),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html']
        );
    }
}

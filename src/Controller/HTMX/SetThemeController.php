<?php

namespace App\Controller\HTMX;

use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SetThemeController extends AbstractController
{
    #[Route(path: '/hx/set-theme', name: 'app_hx_set_theme', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $router,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        $theme = null;

        if (!is_null($request->request->get('light'))) {
            $theme = 'light';
        }

        if (!is_null($request->request->get('dark'))) {
            $theme = 'dark';
        }

        if (is_null($theme)) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('set_theme', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $userSettingsRepository = $entityManager->getRepository(UserSettings::class);
        $userSetting = $userSettingsRepository->findOneBy([
            'name' => 'theme',
            'user' => $user->getId(),
        ]);

        if (!$userSetting) {
            $userSetting = new UserSettings();
            $userSetting->setName('theme');
            $userSetting->setUser($user);
        }

        if ($userSetting->getValue() === $theme) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $userSetting->setValue($theme);
        $entityManager->persist($userSetting);
        $entityManager->flush();

        $response = new Response();
        $response->headers->set('HX-Redirect', $router->generate('app_settings'));

        return $response;
    }
}

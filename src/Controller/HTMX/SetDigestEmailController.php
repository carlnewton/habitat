<?php

namespace App\Controller\HTMX;

use App\Entity\Settings;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Though this is technically an admin setting, it seemed most appropriate to put it inside of the user settings page,
 * because it's relevant to the admin user experience in particular.
 */
#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class SetDigestEmailController extends AbstractController
{
    #[Route(path: '/hx/set-digest', name: 'app_hx_set_digest', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $router,
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('set_digest', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $digest = null;

        if (!is_null($request->request->get('daily'))) {
            $digest = 'daily';
        } elseif (!is_null($request->request->get('immediately'))) {
            $digest = 'immediately';
        } elseif (!is_null($request->request->get('never'))) {
            $digest = 'never';
        }

        if (is_null($digest)) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $settingsRepository = $entityManager->getRepository(Settings::class);

        $setting = $settingsRepository->getSettingByName('digestEmail');
        if (!$setting) {
            $setting = new Settings();
            $setting->setName('digestEmail');
        }
        $setting->setValue($digest);

        $entityManager->persist($setting);
        $entityManager->flush();

        return $this->render('partials/hx/digest.html.twig', [
            'digest' => $digest,
        ]);
    }
}

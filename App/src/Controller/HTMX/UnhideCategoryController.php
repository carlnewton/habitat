<?php

namespace App\Controller\HTMX;

use App\Entity\User;
use App\Entity\UserHiddenCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UnhideCategoryController extends AbstractController
{
    #[Route(path: '/hx/unhide-category', name: 'app_hx_unhide_category', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        if (empty($request->get('category'))) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('unhide_category', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $hiddenCategoryRepository = $entityManager->getRepository(UserHiddenCategory::class);
        $hiddenCategory = $hiddenCategoryRepository->findOneBy([
            'category' => $request->get('category'),
            'user' => $user->getId(),
        ]);

        if ($hiddenCategory) {
            $entityManager->remove($hiddenCategory);
            $entityManager->flush();
        }

        return new Response('', Response::HTTP_OK);
    }
}

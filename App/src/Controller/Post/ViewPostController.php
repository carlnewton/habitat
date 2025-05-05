<?php

namespace App\Controller\Post;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Event\BeforePostViewedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ViewPostController extends AbstractController
{
    #[Route(path: '/post/{id}', name: 'app_view_post', methods: ['GET'])]
    public function index(
        int $id,
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        $postRepository = $entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $id,
        ]);

        if (!$post) {
            throw $this->createNotFoundException('The post does not exist');
        }

        if (null !== $user) {
            foreach ($post->getHearts() as $heart) {
                if ($heart->getUser()->getId() === $user->getId()) {
                    $post->setCurrentUserHearted(true);
                    break;
                }
            }
        }

        $categoryRepository = $entityManager->getRepository(Category::class);

        $event = new BeforePostViewedEvent($post, $user ?? null);
        $eventDispatcher->dispatch($event, BeforePostViewedEvent::NAME);

        return $this->render('view_post.html.twig', [
            'post' => $post,
            'page_title' => $post->getTitle(),
            'show_category' => $categoryRepository->count() > 1,
        ]);
    }
}

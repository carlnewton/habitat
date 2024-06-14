<?php

namespace App\Controller\Admin\Moderation;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class CommentsController extends AbstractController
{
}

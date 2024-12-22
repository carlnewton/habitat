<?php

namespace App\Controller\Admin;

use App\Entity\RegistrationQuestion;
use App\Entity\RegistrationQuestionAnswer;
use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class UserRegistrationSettingsController extends AbstractController
{
    #[Route(path: '/admin/user-registration', name: 'app_admin_user_registration', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $questionRepository = $entityManager->getRepository(RegistrationQuestion::class);
        $questions = $questionRepository->findAll();

        $settingsRepository = $entityManager->getRepository(Settings::class);
        $registrationSetting = $settingsRepository->getSettingByName('registration');

        if ('POST' === $request->getMethod()) {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->render('admin/user_registration.html.twig', [
                    'values' => [
                        'questions' => $this->convertQuestionsToJsonString($questions),
                        'registration' => ($registrationSetting) ? $registrationSetting->getValue() : '',
                    ],
                ]);
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('admin/user_registration.html.twig', [
                    'errors' => $fieldErrors,
                    'values' => [
                        'questions' => $request->get('registration-challenge-questions'),
                        'registration' => $request->get('enable-registration'),
                    ],
                ]);
            }

            $existingQuestions = $questionRepository->findAll();
            foreach ($existingQuestions as $existingQuestion) {
                $entityManager->remove($existingQuestion);
            }

            $submittedQuestions = $request->get('registration-challenge-questions');
            if (!empty($submittedQuestions) && json_validate($submittedQuestions)) {
                $submittedQuestions = json_decode($submittedQuestions);
                foreach ($submittedQuestions as $question) {
                    if (!empty(trim($question->question)) && !empty($question->answers)) {
                        $questionEntity = new RegistrationQuestion();
                        $questionEntity->setQuestion(trim($question->question));
                        $entityManager->persist($questionEntity);

                        $entityManager->persist($questionEntity);
                        foreach ($question->answers as $answer) {
                            if (empty(trim($answer))) {
                                continue;
                            }

                            $answerEntity = new RegistrationQuestionAnswer();
                            $answerEntity->setAnswer(mb_strtolower(trim($answer)));
                            $answerEntity->setQuestion($questionEntity);
                            $entityManager->persist($answerEntity);
                        }
                    }
                }

                if (!$registrationSetting) {
                    $registrationSetting = new Settings();
                    $registrationSetting->setName('registration');
                }
                $registrationSetting->setValue($request->get('enable-registration'));
                $entityManager->persist($registrationSetting);

                $entityManager->flush();

                $this->addFlash(
                    'notice',
                    'Settings saved'
                );

                return $this->redirectToRoute('app_admin_user_registration');
            }
        }

        return $this->render('admin/user_registration.html.twig', [
            'values' => [
                'questions' => $this->convertQuestionsToJsonString($questions),
                'registration' => ($registrationSetting) ? $registrationSetting->getValue() : '',
            ],
        ]);
    }

    protected function convertQuestionsToJsonString(array $questionEntities)
    {
        if (empty($questionEntities)) {
            return json_encode([]);
        }

        $questions = [];
        foreach ($questionEntities as $questionEntity) {
            $question = [
                'question' => $questionEntity->getQuestion(),
                'answers' => [],
            ];

            $answers = [];
            foreach ($questionEntity->getAnswers() as $answerEntity) {
                $answers[] = $answerEntity->getAnswer();
            }

            $question['answers'] = $answers;
            $questions[] = $question;
        }

        return json_encode($questions);
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (!empty($request->get('enable-registration')) && 'on' !== $request->get('enable-registration')) {
            $errors['enable-registration'] = 'You must decide to enable or disable user registration';
        }

        $submittedQuestions = $request->get('registration-challenge-questions');

        if (!json_validate($submittedQuestions)) {
            $errors['questions'][] = 'Unable to validate the registration questions';
        } else {
            $submittedQuestions = json_decode($submittedQuestions);
            foreach ($submittedQuestions as $question) {
                if (strlen(trim($question->question)) > 255) {
                    $errors['questions'][] = 'All questions must be 255 characters or less';
                }

                if (strlen(trim($question->question)) > 0) {
                    $questionHasAnswer = false;
                    foreach ($question->answers as $answer) {
                        if (strlen(trim($answer)) > 0) {
                            $questionHasAnswer = true;

                            if (strlen(trim($answer)) > 255) {
                                $errors['questions'][] = 'All answers must be 255 characters or less';
                            }
                        }
                    }

                    if (!$questionHasAnswer) {
                        $errors['questions'][] = 'All questions must have at least one correct answer';
                    }
                }
            }
        }

        if (!empty($errors['questions'])) {
            $errors['questions'] = array_unique($errors['questions']);
        }

        return $errors;
    }
}

<?php

namespace OtusHw\Controller;

use OtusHw\Exception\UsernameInUseRegistrationException;
use OtusHw\Security\User;
use OtusHw\Form\RegistrationFormType;
use OtusHw\Security\LoginFormAuthenticator;
use OtusHw\Service\InterestService;
use OtusHw\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class RegistrationController extends AbstractController
{
    /** @var UserService */
    private UserService $userService;

    /** @var InterestService */
    private InterestService $interestService;

    /**
     * RegistrationController constructor.
     *
     * @param UserService $userService
     * @param InterestService $interestsService
     */
    public function __construct(UserService $userService, InterestService $interestsService)
    {
        $this->userService = $userService;
        $this->interestService = $interestsService;
    }

    /**
     * @Route("/register", name="app_register")
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param GuardAuthenticatorHandler $guardHandler
     * @param LoginFormAuthenticator $authenticator
     * @return Response
     */
    public function register(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        GuardAuthenticatorHandler $guardHandler,
        LoginFormAuthenticator $authenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('current_user_page');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $encodedPassword = $passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData());

            try {
                $userId = $this->userService->addUserSettingRow($form->get('username')->getData(), $encodedPassword);
                $this->userService->addUserInfo(
                    $userId,
                    $form->get('name')->getData(),
                    $form->get('surname')->getData(),
                    $form->get('age')->getData(),
                    $form->get('gender')->getData(),
                    $form->get('city')->getData(),
                );
                $this->interestService->addInterests($form->get('interests')->getData());
                $this->userService->addUserInterests($userId, $form->get('interests')->getData());

                // do anything else you need here, like send an email

                $user->setUsername($form->get('username')->getData())
                    ->setPassword($encodedPassword);
                return $guardHandler->authenticateUserAndHandleSuccess(
                    $user,
                    $request,
                    $authenticator,
                    'main' // firewall name in security.yaml
                );
            } catch (UsernameInUseRegistrationException $e) {
                $form->get('username')->addError(new FormError($e->getMessage()));
            } catch (\Exception $e) {
                $form->addError(new FormError(
                    'Internal server error'
                ));
            }
        }

        return $this->render('registration/register.html.twig', [
            'registration_form' => $form->createView(),
        ]);
    }
}

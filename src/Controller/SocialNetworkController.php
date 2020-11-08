<?php

namespace OtusHw\Controller;

use Doctrine\DBAL\DBALException;
use OtusHw\Exception\UsernameNotFoundException;
use OtusHw\Form\EditUserInfoFormType;
use OtusHw\Form\UserSearchType;
use OtusHw\Security\User;
use OtusHw\Service\FriendsService;
use OtusHw\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SocialNetworkController extends AbstractController
{
    /** @var UserService */
    private UserService $userService;

    /** @var FriendsService */
    private FriendsService $friendsService;

    /**
     * SocialNetworkController constructor.
     *
     * @param UserService $userService
     * @param FriendsService $friendsService
     */
    public function __construct(UserService $userService, FriendsService $friendsService)
    {
        $this->userService = $userService;
        $this->friendsService = $friendsService;
    }

    /**
     * @Route("/social", name="current_user_page")
     *
     * @return Response
     * @throws DBALException
     */
    public function currentUserPage(): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }

        /** @var User $currentUser */
        $userInfo = $this->userService->getUserInfo($currentUser->getUserId());
        $userInterests = $this->userService->getUserInterests($currentUser->getUserId());

        return $this->render('social_network/index.html.twig', [
            'is_current_user_page' => true,
            'is_user_added_to_friends' => false,
            'username' => $currentUser->getUsername(),
            'user_info' => $userInfo,
            'user_interests' => $userInterests,
        ]);
    }

    /**
     * @Route("/social/user/{username}", name="user_page")
     *
     * @param string $username Имя пользователя, страницу которого хотим просмотреть
     * @return Response
     * @throws DBALException
     */
    public function userPage(string $username): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }

        try {
            $pageOwner = $this->userService->getUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            return new Response('Username not found', Response::HTTP_NOT_FOUND);
        }

        $isCurrentUserPage = $currentUser->getUsername() === $username;
        $userAddedToFriends = false;
        /** @var User $currentUser */
        $userInfo = $this->userService->getUserInfo($pageOwner->getUserId());
        $userInterests = $this->userService->getUserInterests($pageOwner->getUserId());
        if (!$isCurrentUserPage) {
            try {
                $userAddedToFriends = $this->friendsService->checkByUsernameIfUserIsAddedToFriends($currentUser, $username);
            } catch (UsernameNotFoundException $e) {
                return new Response('Username not found', Response::HTTP_NOT_FOUND);
            }
        }

        return $this->render('social_network/index.html.twig', [
            'is_current_user_page' => $isCurrentUserPage,
            'is_user_added_to_friends' => $userAddedToFriends,
            'username' => $username,
            'user_info' => $userInfo,
            'user_interests' => $userInterests,
        ]);
    }

    /**
     * @Route("/social/friends", name="current_user_friends")
     *
     * @return Response
     * @throws DBALException
     */
    public function currentUserFriends(): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }
        /** @var User $currentUser */
        $userForFriendsSearchInfo = $this->userService->getUserInfo($currentUser->getUserId());
        $friendsList = $this->friendsService->getUserMutualFriends($currentUser);
        return $this->render('social_network/friends.html.twig', [
            'user_info' => $userForFriendsSearchInfo,
            'friends_list' => $friendsList
        ]);
    }

    /**
     * @Route("/social/friends/{username}", name="user_friends")
     *
     * @param string $username Имя пользователя, друзей которого хотим просмотреть
     * @return Response
     * @throws DBALException
     */
    public function userFriends(string $username): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }
        try {
            /** @var User $currentUser */
            $userForFriendsSearch = $this->userService->getUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            return new Response('Username not found', Response::HTTP_NOT_FOUND);
        }

        $userForFriendsSearchInfo = $this->userService->getUserInfo($userForFriendsSearch->getUserId());
        $friendsList = $this->friendsService->getUserMutualFriends($userForFriendsSearch);
        return $this->render('social_network/friends.html.twig', [
            'user_info' => $userForFriendsSearchInfo,
            'friends_list' => $friendsList
        ]);
    }

    /**
     * @Route("/social/search", name="search_user")
     *
     * @param Request $request
     * @return Response
     * @throws DBALException
     */
    public function searchUser(Request $request): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserSearchType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $searchResults = $this->userService->searchUsers($form->getData());
            return $this->render('social_network/search_results.html.twig', [
                'search_results' => $searchResults,
            ]);
        }

        return $this->render('social_network/search.html.twig', [
            'user_search_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/social_wci/search", name="search_user_wci")
     *
     * @param Request $request
     * @return Response
     * @throws DBALException
     */
    public function searchUserWithCompositeIndices(Request $request): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserSearchType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $searchResults = $this->userService->searchUsersWithCompositeIndices($form->getData());
            return $this->render('social_network/search_results.html.twig', [
                'search_results' => $searchResults,
            ]);
        }

        return $this->render('social_network/search.html.twig', [
            'user_search_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/social_wsi/search", name="search_user_wsi")
     *
     * @param Request $request
     * @return Response
     * @throws DBALException
     */
    public function searchUserWithSeparateIndices(Request $request): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserSearchType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $searchResults = $this->userService->searchUsersWithSeparateIndices($form->getData());
            return $this->render('social_network/search_results.html.twig', [
                'search_results' => $searchResults,
            ]);
        }

        return $this->render('social_network/search.html.twig', [
            'user_search_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/social/add_friend/{username}", name="add_user_to_friends")
     *
     * @param string $username Имя пользователя, которого хотим добавить в друзья текущему
     * @return Response
     * @throws DBALException
     */
    public function addUserToFriends(string $username): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }
        try {
            /** @var User $currentUser */
            $this->friendsService->addUserToFriends($currentUser, $username);
        } catch (UsernameNotFoundException $e) {
            return new Response('Username not found', Response::HTTP_NOT_FOUND);
        }
        return $this->redirectToRoute('user_page', ['username' => $username]);
    }

    /**
     * @Route("/social/user_edit", name="edit_user_info")
     *
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws DBALException
     */
    public function editUserInfo(Request $request)
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }
        /** @var User $currentUser */
        $userInfo = $this->userService->getUserInfo($currentUser->getUserId());
        $userInterests = $this->userService->getUserInterests($currentUser->getUserId());
        $form = $this->createForm(EditUserInfoFormType::class, [
            'name' => $userInfo['name'],
            'surname' => $userInfo['surname'],
            'age' => $userInfo['age'],
            'gender' => $userInfo['gender'],
            'interests' => $userInterests,
            'city' => $userInfo['city'],
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->editUserInfo($currentUser->getUserId(), $form->getData());
        }
        return $this->render('social_network/edit_user_info.html.twig', [
            'edit_user_info_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/social/friends_requests", name="get_friendship_requests")
     *
     * @throws DBALException
     */
    public function getFriendshipRequests()
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            return $this->redirectToRoute('app_login');
        }
        /** @var User $currentUser */
        $incomingRequests = $this->friendsService->getUserIncomingFriendshipRequests($currentUser);
        $outgoingRequests = $this->friendsService->getUserOutgoingFriendshipRequests($currentUser);
        return $this->render('social_network/friendship_requests.html.twig', [
            'incoming_friendship_requests' => $incomingRequests,
            'outgoing_friendship_requests' => $outgoingRequests,
        ]);
    }
}

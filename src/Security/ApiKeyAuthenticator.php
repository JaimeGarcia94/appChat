<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;

class ApiKeyAuthenticator implements OAuthAwareUserProviderInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {

        // dd($response->getData());

        $data = $response->getData();

        $username = $data["name"];
        $picture = $data["picture"];
        $email = $data["email"];
        $date = new DateTime();

        $user = $this->em->getRepository(User::class)->findOneByEmail($email);

        if (!$user) {

            //Create user
            
            $user = new User();
            $user->setUsername($username);
            $user->setPictureUrl($picture);
            $user->setEmail($email);
            $user->setPassword(sha1(str_shuffle('abscdop123390hHHH;:::000I')));
            $user->setCreatedAt($date);

            $this->em->persist($user);
            $this->em->flush();

            return $user;

            }

            $this->updateUser($user, $response);

        return $user;

    }

    public function connect(UserInterface $user, UserResponseInterface $response)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Expected an instance of App\Model\User, but got "%s".', get_class($user)));
        }

        $property = $this->getProperty($response);
        $username = $response->getUsername();

        if (null !== $previousUser = $this->registry->getRepository(User::class)->findOneBy(array($property => $username))) {
            // 'disconnect' previously connected users
            $this->disconnect($previousUser, $response);
        }

        $this->updateUser($user, $response);
    }

    /**
     * ##STOLEN#
     * Gets the property for the response.
     *
     * @param UserResponseInterface $response
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function getProperty(UserResponseInterface $response)
    {
        $resourceOwnerName = $response->getResourceOwner()->getName();

        if (!isset($this->properties[$resourceOwnerName])) {
            throw new \RuntimeException(sprintf("No property defined for entity for resource owner '%s'.", $resourceOwnerName));
        }

        return $this->properties[$resourceOwnerName];
    }

    /**
     * Disconnects a user.
     *
     * @param UserInterface $user
     * @param UserResponseInterface $response
     * @throws \TypeError
     */
    public function disconnect(UserInterface $user, UserResponseInterface $response)
    {
        $property = $this->getProperty($response);
        $accessor = PropertyAccess::createPropertyAccessor();

        $accessor->setValue($user, $property, null);

        $this->updateUser($user, $response);
    }

    /**
     * Update the user and persist the changes to the database.
     * @param UserInterface $user
     * @param UserResponseInterface $response
     */
    private function updateUser(UserInterface $user, UserResponseInterface $response)
    {
        $data = $response->getData();

        $username = $data["name"];
        $picture = $data["picture"];
        $email = $data["email"];
        $date = new DateTime();

        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPictureUrl($picture);
        $user->setCreatedAt($date);

        // TODO: Add more fields?!

        $this->em->persist($user);
        $this->em->flush();
    }

}

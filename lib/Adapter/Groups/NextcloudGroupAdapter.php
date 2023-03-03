<?php

namespace OCA\SCIMServiceProvider\Adapter\Groups;

use OCP\IGroup;
use OCP\IRequest;
use OCP\IUserManager;
use Opf\Adapters\AbstractAdapter;
use Opf\Models\SCIM\Standard\Groups\CoreGroup;
use Opf\Models\SCIM\Standard\MultiValuedAttribute;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class NextcloudGroupAdapter extends AbstractAdapter
{
    /** @var Psr\Log\LoggerInterface */
    private $logger;

    /** @var IUserManager */
    private $userManager;

    /** @var IRequest */
    private $request;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerInterface::class);
        $this->userManager = $container->get(IUserManager::class);
        $this->request = $container->get(IRequest::class);
    }

    /**
     * Transform an NC group into a SCIM group
     */
    public function getCoreGroup(?IGroup $ncGroup): ?CoreGroup
    {
        $this->logger->info(
            "[" . NextcloudGroupAdapter::class . "] entering getCoreGroup() method"
        );

        $baseUrl = $this->request->getServerProtocol() . "://" . $this->request->getServerHost() . "/index.php/apps/scimserviceprovider";

        if (!isset($ncGroup)) {
            $this->logger->error(
                "[" . NextcloudGroupAdapter::class . "] passed NC group in getCoreGroup() method is null"
            );

            return null;
        }

        $coreGroup = new CoreGroup();
        $coreGroup->setId($ncGroup->getGID());
        $coreGroup->setDisplayName($ncGroup->getDisplayName());

        $ncGroupMembers = $ncGroup->getUsers();

        if (isset($ncGroupMembers) && !empty($ncGroupMembers)) {
            $coreGroupMembers = [];

            foreach ($ncGroupMembers as $ncGroupMember) {
                $coreGroupMember = new MultiValuedAttribute();
                $coreGroupMember->setValue($ncGroupMember->getUID());
                $coreGroupMember->setRef($baseUrl . "/Users/" . $ncGroupMember->getUID());
                $coreGroupMember->setDisplay($ncGroupMember->getDisplayName());

                $coreGroupMembers[] = $coreGroupMember;
            }

            $coreGroup->setMembers($coreGroupMembers);
        }

        return $coreGroup;
    }

    /**
     * Transform a SCIM group into an NC group
     *
     * Note: the second parameter is needed, since we can't instantiate an NC group
     * ourselves and need to receive an instance, passed from somewhere
     */
    public function getNCGroup(?CoreGroup $coreGroup, IGroup $ncGroup): ?IGroup
    {
        $this->logger->info(
            "[" . NextcloudGroupAdapter::class . "] entering getNCGroup() method"
        );

        if (!isset($coreGroup) || !isset($ncGroup)) {
            $this->logger->error(
                "[" . NextcloudGroupAdapter::class . "] passed Core Group in getNCGroup() method is null"
            );

            return null;
        }

        $ncGroup->setDisplayName($coreGroup->getDisplayName());

        if ($coreGroup->getMembers() !== null && !empty($coreGroup->getMembers())) {
            foreach ($coreGroup->getMembers() as $coreGroupMember) {
                // If user with this uid exists, then add it as a member of the group
                if ($coreGroupMember->getValue() !== null && !empty($coreGroupMember->getValue())) {
                    if ($this->userManager->userExists($coreGroupMember->getValue())) {
                        $ncGroup->addUser($this->userManager->get($coreGroupMember->getValue()));
                    }
                }
            }
        }

        return $ncGroup;
    }
}

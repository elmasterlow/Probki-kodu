<?php

namespace SomeBundle\Component\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ExecutionContextInterface;
use SomeBundle\Entity\Url;

class UrlValidator extends ConstraintValidator
{
    private $entityManager;

    private $tokenStorage;

    private $data;

    private $object = null;

    public function __construct(EntityManager $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function validate($value, Constraint $constraint)
    {
        $context = $this->context;
        $this->data = $context->getRoot()->getData();

        // find by raw domain
        $this->object = $this->entityManager->getRepository('SomeBundle:Url')->findOneBy([
            'rawDomain' => $this->data->getRawDomain(),
        ]);

        switch ($this->data->getType()) {
            // url must be valid url
            case Url::TYPE_HTTP_RESPONSE:
                $this->typeHTTPResponse($value, $context);
                break;

            // url must be valid ip address or host
            case Url::TYPE_PING:
                $this->typePing($value, $context);
                break;

            default:
                $this->typeDefault($value, $context);
                break;
        }
    }

    private function typeHTTPResponse($value, &$context)
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            $context->buildViolation('This is not valid url.')
                ->atPath('url')
                ->addViolation()
            ;
        }

        if ($this->data->getRawDomain() === null) {
            $context->buildViolation('Unable to check raw domain from this url.')
                ->atPath('url')
                ->addViolation()
            ;
        }

        if ($this->object && $this->object->getUser() !== $this->tokenStorage->getToken()->getUser()) {
            $context->buildViolation('This domain is used by other user.')
                ->atPath('url')
                ->addViolation()
            ;
        }
    }

    private function typePing($value, &$context)
    {
        if ((filter_var($value, FILTER_VALIDATE_IP) === false) && !gethostbynamel($value)) {
            $context->buildViolation('This is not valid ip or hostname address.')
                ->atPath('url')
                ->addViolation()
            ;
        }

        if ($this->object && $this->object->getUser() !== $this->tokenStorage->getToken()->getUser()) {
            $context->buildViolation('This domain is used by other user.')
                ->atPath('url')
                ->addViolation()
            ;
        }
    }

    private function typeDefault($value, &$context)
    {
        $context->buildViolation('This is not valid value.')
            ->atPath('url')
            ->addViolation()
        ;
    }
}
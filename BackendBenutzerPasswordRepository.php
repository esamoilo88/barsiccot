<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\BackendBenutzer;
use App\Domain\Entities\BackendBenutzerPassword;
use App\Domain\Repositories\Interfaces\IBackendBenutzerPasswordRepository;
use App\Exceptions\Business\ObjectNotExistsException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use App\Domain\Repositories\BaseRepository;


class BackendBenutzerPasswordRepository extends BaseRepository implements IBackendBenutzerPasswordRepository
{
    protected string $alias = 'benutzer_password';

    /**
     * @param int $id
     * @return BackendBenutzerPassword|object
     * @throws ObjectNotExistsException
     */
    public function find(int $id): BackendBenutzerPassword
    {
        $entity = $this->genericRepository->find($id);

        if ($entity === null) {
            throw new ObjectNotExistsException('BackendBenutzerPasswort not exists');
        }

        return $entity;
    }

    /**
     * @return Collection
     */
    public function findAll(): Collection
    {
        return new ArrayCollection($this->genericRepository->findAll());
    }

    /**
     * @param BackendBenutzer $user
     * @return Collection
     */
    public function findLastThreeRows(BackendBenutzer $user): Collection
    {
        /** @var QueryBuilder $builder */
        $builder = $this->genericRepository->createQueryBuilder($this->alias);

        return new ArrayCollection($builder
            ->where("{$this->alias}.backendBenutzer = :user")
            ->orderBy("$this->alias.passwordId", 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults(3)
            ->getQuery()
            ->getResult()
        );
    }


}


<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\Doctrine\ORM\Repository\ExternalApi;

use Akeneo\Tool\Component\Api\Repository\ApiResourceRepositoryInterface;
use Akeneo\Tool\Component\Classification\Repository\CategoryRepositoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnexpectedResultException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * @author    Pierre Jolly <pierre.jolly@akeneo.com>
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CategoryRepository extends EntityRepository implements ApiResourceRepositoryInterface
{
    /** @var CategoryRepositoryInterface */
    private $categoryRepository;

    public function __construct(
        EntityManager $entityManager,
        $className,
        CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($entityManager, $entityManager->getClassMetadata($className));

        $this->categoryRepository = $categoryRepository;
    }

    public function getIdentifierProperties(): array
    {
        return $this->categoryRepository->getIdentifierProperties();
    }

    public function findOneByIdentifier($identifier)
    {
        return $this->categoryRepository->findOneByIdentifier($identifier);
    }

    public function searchAfterOffset(array $searchFilters, array $orders, $limit, $offset)
    {
        $qb = $this->createQueryBuilder('r');
        $qb = $this->addFilters($qb, $searchFilters);

        foreach ($orders as $field => $sort) {
            $qb->addOrderBy(sprintf('r.%s', $field), $sort);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function count(array $searchFilters = []): int
    {
        try {
            $qb = $this->createQueryBuilder('r');
            $this->addFilters($qb, $searchFilters);

            return (int) $qb
                ->select('COUNT(r.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (UnexpectedResultException $e) {
            return 0;
        }
    }

    protected function addFilters(QueryBuilder $qb, array $searchFilters): QueryBuilder
    {
        $this->validateSearchFilters($searchFilters);

        foreach ($searchFilters as $property => $searchFilter) {
            foreach ($searchFilter as $key => $criterion) {
                $field = sprintf('r.%s', $property);

                switch ($property) {
                    case 'parent':
                        $qb->andWhere($qb->expr()->in($field, $this->categoryRepository->getCategoryIdsByCodes($criterion['value'])));
                }

            }
        }

        return $qb;
    }

    protected function validateSearchFilters(array $searchFilters): void
    {
        if (empty($searchFilters)) {
            return;
        }

        $validator = Validation::createValidator();
        $constraints = [
            'parent' => new Assert\All([
                new Assert\Collection([
                    'operator' => new Assert\IdenticalTo([
                        'value' => 'IN',
                        'message' => 'In order to search on categories parents you must use "IN" operator, {{ compared_value }} given.',
                    ]),
                    'value' => [
                        new Assert\Type([
                            'type' => 'array',
                            'message' => 'In order to search on categories parents you must send an array of categories parents as value, {{ type }} given.'
                        ]),
                        new Assert\All([
                            new Assert\Type('string')
                        ])
                    ],
                ])
            ]),
        ];
        $availableSearchFilters = array_keys($constraints);

        $exceptionMessage = '';
        foreach ($searchFilters as $property => $searchFilter) {
            if (!in_array($property, $availableSearchFilters)) {
                throw new \InvalidArgumentException(sprintf(
                    'Available search filters are "%s" and you tried to search on unavailable filter "%s"',
                    implode(', ', $availableSearchFilters),
                    $property
                ));
            }
            $violations = $validator->validate($searchFilter, $constraints[$property]);
            if (0 !== $violations->count()) {
                foreach ($violations as $violation) {
                    $exceptionMessage .= $violation->getMessage();
                }
            }
        }
        if ('' !== $exceptionMessage) {
            throw new \InvalidArgumentException($exceptionMessage);
        }
    }
}

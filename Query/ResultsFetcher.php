<?php

namespace Samson\Bundle\AutocompleteBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Samson\Bundle\AutocompleteBundle\Autocomplete\Autocomplete;
use Symfony\Component\HttpFoundation\Request;

class ResultsFetcher
{

    /**
     *
     * @deprecated( 'Use getResultsByRequest' )
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $searchFields
     * @param $searchFromBeginning
     * @return \Doctrine\ORM\Query
     */
    public function getResults(Request $request, QueryBuilder $qb, array $searchFields, $searchFromBeginning)
    {
        return $this->getResultsByRequest($request, $qb, $searchFields, $searchFromBeginning)->getQuery();
    }

    public function getResultsByRequest(Request $request, QueryBuilder $qb, array $searchFields, $searchFromBeginning)
    {
        if ($searchFromBeginning) {
            $search = array(trim($request->get(Autocomplete::KEY_SEARCH)));
        } else {
            $search = preg_split('/\s+/', trim($request->get(Autocomplete::KEY_SEARCH)));
        }
        return $this->getResultsByArray($search, $request->get(Autocomplete::KEY_PAGE, 1), $qb, $searchFields, $searchFromBeginning);
    }

    public function getResultsByArray(array $search, $page, QueryBuilder $qb, array $searchFields, $searchFromBeginning)
    {
        $this->appendQuery($qb, $search, $searchFields, $searchFromBeginning);
        $qb->setMaxResults(10);
        $qb->setFirstResult(($page - 1) * 10);
        return $qb;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $searchFields
     * @param $searchFromBeginning
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getPaginatedResults(Request $request, QueryBuilder $qb, array $searchFields, $searchFromBeginning)
    {
        $query = $this->getResultsByRequest($request, $qb, $searchFields, $searchFromBeginning);
        $pageSize = $request->get(Autocomplete::KEY_LIMIT, 10);
        $page = $request->get(Autocomplete::KEY_PAGE, 1);

        $query->setFirstResult(($page - 1) * $pageSize);
        $query->setMaxResults($pageSize);

        return new Paginator($query);
    }

    public function appendQuery(QueryBuilder $qb, array $searchWords, array $searchFields, $searchFromBeginning)
    {
        foreach ($searchWords as $key => $searchWord) {
            $expressions = array();

            foreach ($searchFields as $key2 => $field) {
                $expressions[] = $qb->expr()->like($qb->expr()->lower($field), ':query' . $key . $key2);
                $qb->setParameter('query' . $key . $key2, ($searchFromBeginning ? '' : '%') . strtolower($searchWord) . '%');
            }
            $qb->andWhere("(" . call_user_func_array(array($qb->expr(), 'orx'), $expressions) . ")");
        }
    }
}

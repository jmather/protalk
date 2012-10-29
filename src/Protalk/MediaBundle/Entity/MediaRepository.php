<?php

/**
 * ProTalk
 *
 * Copyright (c) 2012-2013, ProTalk
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Protalk\MediaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * MediaRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MediaRepository extends EntityRepository
{
    /**
     * Query database for a fixed number of media records ordered by
     * a specific field
     *
     * @param string $orderField
     * @param int    $page
     * @param int    $max
     * @param string $order
     *
     * @return array Array with total and results
     */
    public function getMediaOrderedBy($sort, $page, $max, $order = 'DESC')
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("m")
           ->from("ProtalkMediaBundle:Media", "m")
           ->where("m.isPublished = 1")
           ->orderBy("m." . $sort, $order);
        $results = $qb->getQuery()->getResult();

        return $this->getResultList($results, $page, $max);
    }

    /**
     * Create result list by manually doing the limit/offset
     *
     * @param array $results
     * @param int   $page
     * @param int   $max
     *
     * @return array Array with total and results
     */
    private function getResultList($results, $page, $max)
    {
        $start = ($page - 1) * $max;
        $end = ($page * $max) - 1;
        $total = count($results);

        $result = array();
        for ($i = $start; $i <= $end && $i < $total; $i++) {
            $result[] = $results[$i];
        }

        return array('total' => $total, 'results' => $result);
    }

    /**
     * Find media by search term
     *
     * @param string $search
     * @param string $sort
     * @param int    $page
     * @param int    $max
     * @param string $order
     *
     * @return array Array with count and result
     */
    public function findMedia($search, $sort, $page, $max, $order)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("m")->distinct(true)
           ->from("ProtalkMediaBundle:Media", "m")
           ->leftJoin("m.categories", "c")
           ->leftJoin("m.tags", "t")
           ->join("m.speakers", "s")
           ->join("m.mediatype", "mtype")
           ->where(
               $qb->expr()->andX(
                   $qb->expr()->orX(
                       "LOWER(c.name) LIKE :search1",
                       "LOWER(t.name) LIKE :search2",
                       "LOWER(s.name) LIKE :search3",
                       "LOWER(m.title) LIKE :search4",
                       "LOWER(m.description) LIKE :search5",
                       "LOWER(mtype.name) LIKE :search6"
                   ),
                   "m.isPublished = 1"
               )
           );
        $query = $qb->getQuery();
        $query->setParameter('search1', '%'.strtolower($search).'%')
              ->setParameter('search2', '%'.strtolower($search).'%')
              ->setParameter('search3', '%'.strtolower($search).'%')
              ->setParameter('search4', '%'.strtolower($search).'%')
              ->setParameter('search5', '%'.strtolower($search).'%')
              ->setParameter('search6', '%'.strtolower($search).'%');
        $results = $query->getResult();

        return $this->getResultList($results, $page, $max);
    }

    /**
     * Override native findOneBySlug method to include
     * mediatype join, reducing no. of queries to db
     * and increment no of visits made to media item
     *
     * @param  string   $slug
     * @return Doctrine Record
     */
    public function findOneBySlug($slug)
    {
        return $this->getEntityManager()
                    ->createQuery(
                        'SELECT m, mt
                         FROM ProtalkMediaBundle:Media m
                         JOIN m.mediatype mt
                         WHERE m.slug = :slug AND m.isPublished = 1'
                    )
                    ->setParameter('slug', $slug)
                    ->getSingleResult();
    }

    /**
     * Find media items by category
     *
     * @param string $slug       (category name)
     * @param string $orderField
     * @param int    $page
     * @param int    $max
     * @param string $order
     *
     * @return array Array with total and results
     */
    public function findByCategory($slug, $orderField, $page, $max, $order = 'DESC')
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("m")
           ->from("ProtalkMediaBundle:Media", "m")
           ->join("m.categories", "c")
           ->where(
               $qb->expr()->andX(
                   "c.slug = :slug",
                   "m.isPublished = 1"
               )
           )
           ->orderBy("m." . $orderField, $order);
        $query = $qb->getQuery();
        $query->setParameter("slug", $slug);

        $results = $query->getResult();

        return $this->getResultList($results, $page, $max);
    }

    /**
     * Find media items by tag
     *
     * @param string $slug       (tag name)
     * @param string $orderField
     * @param int    $page
     * @param int    $max
     * @param string $order
     *
     * @return array Array with total and results
     */
    public function findByTag($slug, $orderField, $page, $max, $order = 'DESC')
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("m")
           ->from("ProtalkMediaBundle:Media", "m")
           ->join("m.tags", "t")
           ->where(
               $qb->expr()->andX(
                   "t.slug = :slug",
                   "m.isPublished = 1"
               )
           )
           ->orderBy("m." . $orderField, $order);
        $query = $qb->getQuery();
        $query->setParameter("slug", $slug);
        $results = $query->getResult();

        return $this->getResultList($results, $page, $max);
    }

    /**
     * Find media items by speaker
     *
     * @param int    $speakerId
     * @param string $orderField
     * @param int    $page
     * @param int    $max
     *
     * @return array Array with total and results
     */
    public function findBySpeaker($speakerId, $orderField, $page, $max)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("m")
           ->from("ProtalkMediaBundle:Media", "m")
           ->join("m.speakers", "s")
           ->where(
               $qb->expr()->andX(
                   "s.id = :speakerId",
                   "m.isPublished = 1"
               )
           )
           ->orderBy("m." . $orderField, "DESC");
        $query = $qb->getQuery();
        $query->setParameter('speakerId', $speakerId);
        $results = $query->getResult();

        return $this->getResultList($results, $page, $max);
    }

    /**
     * Increment number of visits to media item
     *
     * @param object $media
     */
    public function incrementVisitCount($media)
    {
        $currentVisits = $media->getVisits();
        $media->setVisits($currentVisits + 1);
        $this->getEntityManager()->flush();
    }

    /**
     * Get the average rating of a media item
     *
     * @param  integer $mediaId
     * @return integer
     */
    public function getAverageRating($mediaId)
    {
        $result = $this->getEntityManager()
                       ->createQuery(
                           'SELECT AVG(r.rating)
                            FROM ProtalkMediaBundle:Rating r
                            WHERE r.media_id=:id'
                       )
                       ->setParameter('id', $mediaId)
                       ->getResult();

        $average = $result[0][1];

        return $average;
    }
}

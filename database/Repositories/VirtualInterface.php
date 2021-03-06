<?php

/*
 * Copyright (C) 2009 - 2019 Internet Neutral Exchange Association Company Limited By Guarantee.
 * All Rights Reserved.
 *
 * This file is part of IXP Manager.
 *
 * IXP Manager is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, version v2.0 of the License.
 *
 * IXP Manager is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GpNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Repositories;

use Doctrine\ORM\EntityRepository;

use Entities\{
    Infrastructure as InfraEntity,
    PhysicalInterface as PIEntity,
    VirtualInterface as VIEntity
};
use IXP\Exceptions\GeneralException;

/**
 * VirtualInterface
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VirtualInterface extends EntityRepository
{
    
    /**
     * Utility function to provide a count of different customer types as `type => count`
     * where type is as defined in Entities\Customer::$CUST_TYPES_TEXT
     *
     * @return array Number of customers of each customer type as `[type] => count`
     */
    public function getByLocation()
    {
        return $ints = $this->getEntityManager()->createQuery(
            "SELECT c.id AS customerid, vi.id AS id, pi.speed AS speed, i.name AS infrastructure, l.name AS locationname, sixp.name as ixp, sixp.shortname as locixp
                FROM Entities\\VirtualInterface vi
                    JOIN vi.Customer c
                    JOIN vi.PhysicalInterfaces pi
                    JOIN pi.SwitchPort sp
                    JOIN sp.Switcher sw
                    JOIN sw.Infrastructure i
                    JOIN i.IXP sixp
                    JOIN sw.Cabinet ca
                    JOIN ca.Location l
                WHERE
                    " . Customer::DQL_CUST_EXTERNAL
        )->getArrayResult();
    }


    /**
     * Utility function to provide an array of all virtual interface objects on a given
     * infrastructure
     *
     * @param InfraEntity $infra The infrastructure to gather VirtualInterfaces for
     * @param int $proto Either 4 or 6 to limit the results to interface with IPv4 / IPv6
     * @param bool $externalOnly If true (default) then only external (non-internal) interfaces will be returned
     * @return array As defined above.
     * @throws \IXP_Exception
     */
    public function getObjectsForInfrastructure( InfraEntity $infra, $proto = false, $externalOnly = true )
    {
        $qstr = "SELECT vi
                    FROM Entities\VirtualInterface vi
                        JOIN vi.Customer c
                        JOIN vi.VlanInterfaces vli
                        JOIN vi.PhysicalInterfaces pi
                        JOIN pi.SwitchPort sp
                        JOIN sp.Switcher sw
                        JOIN sw.Infrastructure i
                    WHERE
                        i = :infra
                        AND " . Customer::DQL_CUST_ACTIVE     . "
                        AND " . Customer::DQL_CUST_CURRENT    . "
                        AND " . Customer::DQL_CUST_TRAFFICING . "
                        AND pi.status = " . PIEntity::STATUS_CONNECTED;

        if( $proto ) {
            if( !in_array( $proto, [ 4, 6 ] ) )
                throw new \IXP_Exception( 'Invalid protocol specified' );

            $qstr .= " AND vli.ipv{$proto}enabled = 1 ";
        }

        if( $externalOnly ) {
            $qstr .= " AND " . Customer::DQL_CUST_EXTERNAL;
        }

        $qstr .= " ORDER BY c.name ASC";

        $q = $this->getEntityManager()->createQuery( $qstr );
        $q->setParameter( 'infra', $infra );
        return $q->getResult();
    }

    /**
     * For the given $vi, we want to ensure its channel group is unique
     * within a switch
     *
     * @param VIEntity $vi
     * @return bool
     * @throws GeneralException
     */
    public function validateChannelGroup( VIEntity $vi ): bool {

        if( $vi->getChannelgroup() === null ) {
            throw new GeneralException("Should not be testing a null channel group number");
        }

        if( count( $vi->getPhysicalInterfaces() ) == 0 ) {
            throw new GeneralException("Channel group number is only relevant when there is at least one physical interface");
        }

        // not sure if we're supporting multi-chassis LAGs. May work, may not. Let's be positive and assume it works and account for that:
        $switches = [];

        /** @var \Entities\PhysicalInterface $pi */
        foreach( $vi->getPhysicalInterfaces() as $pi ) {
            if( !in_array( $pi->getSwitchPort()->getSwitcher()->getId(), $switches ) ) {
                $switches[] = $pi->getSwitchPort()->getSwitcher()->getId();
            }
        }

        /** @var VIEntity[] $vis */
        $vis = $this->getEntityManager()->createQuery("
                    SELECT vi FROM Entities\VirtualInterface vi
                        JOIN vi.PhysicalInterfaces pi
                        JOIN pi.SwitchPort sp
                        JOIN sp.Switcher s 
                    WHERE 
                        vi.channelgroup = :cg
                        AND s.id IN ( :switches )
                ")
            ->setParameter('cg',       $vi->getChannelgroup())
            ->setParameter('switches', $switches )
            ->getResult();

        if( count( $vis ) == 0 ) {
            return true;
        }

        foreach( $vis as $v ) {
            if( $v->getId() != $vi->getId() ) {
                return false;
            }
        }

        return true;
    }

    /**
     * For the given $vi, assign a unique channel group
     *
     * @param VIEntity $vi
     * @return int
     * @throws GeneralException
     */
    public function assignChannelGroup( VIEntity $vi ): int {

        if( count( $vi->getPhysicalInterfaces() ) == 0 ) {
            throw new GeneralException("Channel group number is only relevant when there is at least one physical interface");
        }

        // FIXME: need a more reasonbale way of doing this but I also want to ensure old group IDs get reused
        //        as many switches have an upper limit that is quite small
        $orig = $vi->getChannelgroup();
        for( $i = 1; $i < 1000; $i++ ) {
            $vi->setChannelgroup($i);
            if( $this->validateChannelGroup($vi) ) {
                $vi->setChannelgroup($orig);
                return $i;
            }
        }

        $vi->setChannelgroup($orig);
        throw new GeneralException("Could not assign a free channel group number");
    }

    /**
     * Provide a collection of virtual interfaces for the standard controller list action
     *
     * Example usage: resources/views/interfaces/virtual/list.foil.php
     *
     * @return array
     */
    public function getForList(): array
    {
        return $this->getEntityManager()->createQuery(
                "SELECT vi, pi, fpi, ppi, c, sp, s, cab, l, ci, ppp
                    FROM Entities\\VirtualInterface vi
                        LEFT JOIN vi.Customer c
                        LEFT JOIN vi.PhysicalInterfaces pi
                        LEFT JOIN pi.FanoutPhysicalInterface fpi
                        LEFT JOIN pi.PeeringPhysicalInterface ppi
                        LEFT JOIN pi.coreInterface ci
                        LEFT JOIN pi.SwitchPort sp
                        LEFT JOIN sp.Switcher s
                        LEFT JOIN sp.patchPanelPort ppp
                        LEFT JOIN s.Cabinet cab
                        LEFT JOIN cab.Location l"
            )->getResult();
    }

    /**
     * Check if the virtual interface is linked to a core bundle
     *
     * @return array
     */
    public function coreBundlesLinked( int $id ){


        $vi = $this->getEntityManager()->createQuery(
            "SELECT DISTINCT vi
                    FROM Entities\\VirtualInterface vi
                        LEFT JOIN vi.PhysicalInterfaces pi
                        INNER JOIN pi.coreInterface ci
                        
                        INNER JOIN ci.coreLink cl
                        INNER JOIN cl.coreBundle cb
                        
                        INNER JOIN ci.coreLink2 cl2
                        INNER JOIN cl2.coreBundle cb2
                        
                        WHERE vi.id = {$id}"
        )->getResult();

        if( $vi ){
            return $vi[0];
        }

        return false;
    }

}

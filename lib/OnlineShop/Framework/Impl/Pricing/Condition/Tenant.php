<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_Framework_Impl_Pricing_Condition_Tenant implements OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @var string[]
     */
    protected $tenant;


    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        $currentTenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        return in_array($currentTenant, $this->getTenant());
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'Tenant'
            , 'tenant' => implode(',', $this->getTenant())
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setTenant( explode(',', $json->tenant) );

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * @param string[] $tenant
     *
     * @return $this
     */
    public function setTenant(array $tenant)
    {
        $this->tenant = $tenant;
        return $this;
    }
}
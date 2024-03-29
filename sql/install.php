<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
$sql = array();

$sql[0] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pc_configurations` (
    `id_pc_configuration` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_attribute` int(10) UNSIGNED NOT NULL,
    `id_product` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY  (`id_pc_configuration`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[1] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pc_configuration_categories` (
    `id_pc_configuration_category` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_pc_configuration` int(10) UNSIGNED NOT NULL,
    `id_category` int(10) UNSIGNED NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `type` int(10) NOT NULL,
    `add_to_package` BIT(1) NOT NULL,
    PRIMARY KEY  (`id_pc_configuration_category`),
    FOREIGN KEY (`id_pc_configuration`) REFERENCES `' . _DB_PREFIX_ . 'pc_configurations`(`id_pc_configuration`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

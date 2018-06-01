<?php
/**
 * Copyright (C) 2017-2018 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */

// File Example for upgrade

if (!defined('_PS_VERSION_'))
	exit;

// object module ($this) available
function upgrade_module_0_8($object)
{
	$upgrade_version = '0.8';

	$object->upgrade_detail[$upgrade_version] = [];

	// Change url type from varchar to text to avoid url length issues
	$query = 'ALTER TABLE  `'._DB_PREFIX_.'themeconfigurator` CHANGE  `url`  `url` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL';

	if (!Db::getInstance()->execute($query))
		$object->upgrade_detail[$upgrade_version][] = $object->l(sprintf('Can\'t change %s type', _DB_PREFIX_.'themeconfigurator.url'));


	return (bool)!count($object->upgrade_detail[$upgrade_version]);
}

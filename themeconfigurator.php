<?php
/*
* 2007-2016 PrestaShop
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
* @author	PrestaShop SA <contact@prestashop.com>
* @copyright	2007-2016 PrestaShop SA
* @license	http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class ThemeConfigurator
 */
class ThemeConfigurator extends Module
{
    protected $max_image_size = 1048576;
    protected $default_language;
    protected $languages;

    /**
     * ThemeConfigurator constructor.
     */
    public function __construct()
    {
        $this->name = 'themeconfigurator';
        $this->tab = 'front_office_features';
        $this->version = '3.0.1';
        $this->bootstrap = true;
        $this->secure_key = Tools::encrypt($this->name);
        $this->default_language = Language::getLanguage(Configuration::get('PS_LANG_DEFAULT'));
        $this->languages = Language::getLanguages();
        $this->author = 'thirty bees';
        parent::__construct();
        $this->displayName = $this->l('Theme configurator');
        $this->description = $this->l('Configure the main elements of your theme.');
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];
        $this->module_path = _PS_MODULE_DIR_.$this->name.'/';
        $this->uploads_path = _PS_MODULE_DIR_.$this->name.'/img/';
        $this->admin_tpl_path = _PS_MODULE_DIR_.$this->name.'/views/templates/admin/';
        $this->hooks_tpl_path = _PS_MODULE_DIR_.$this->name.'/views/templates/hooks/';
    }

    public function install()
    {
        $themesColors = [
            'theme1',
            'theme2',
            'theme3',
            'theme4',
            'theme5',
            'theme6',
            'theme7',
            'theme8',
            'theme9',
        ];
        $themesFonts = [
            'font1'  => 'Open Sans',
            'font2'  => 'Josefin Slab',
            'font3'  => 'Arvo',
            'font4'  => 'Lato',
            'font5'  => 'Volkorn',
            'font6'  => 'Abril Fatface',
            'font7'  => 'Ubuntu',
            'font8'  => 'PT Sans',
            'font9'  => 'Old Standard TT',
            'font10' => 'Droid Sans',
        ];

        if (!parent::install()
            || !$this->installDB()
            || !$this->installFixtures(Language::getLanguages(true)) ||
            !$this->registerHook('displayHeader') ||
            !$this->registerHook('displayTopColumn') ||
            !$this->registerHook('displayLeftColumn') ||
            !$this->registerHook('displayRightColumn') ||
            !$this->registerHook('displayHome') ||
            !$this->registerHook('displayFooter') ||
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->registerHook('actionObjectLanguageAddAfter') ||
            !Configuration::updateValue('PS_TC_THEMES', serialize($themesColors)) ||
            !Configuration::updateValue('PS_TC_FONTS', serialize($themesFonts)) ||
            !Configuration::updateValue('PS_TC_THEME', '') ||
            !Configuration::updateValue('PS_TC_FONT', '') ||
            !Configuration::updateValue('PS_TC_ACTIVE', 1) ||
            !Configuration::updateValue('PS_SET_DISPLAY_SUBCATEGORIES', 1) ||
            !$this->createAjaxController()
        ) {
            return false;
        }

        return true;
    }

    private function installDB()
    {
        return (
            Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'themeconfigurator`') &&
            Db::getInstance()->Execute(
                '
			CREATE TABLE `'._DB_PREFIX_.'themeconfigurator` (
					`id_item` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`id_shop` int(10) unsigned NOT NULL,
					`id_lang` int(10) unsigned NOT NULL,
					`item_order` int(10) unsigned NOT NULL,
					`title` VARCHAR(100),
					`title_use` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
					`hook` VARCHAR(100),
					`url` TEXT,
					`target` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
					`image` VARCHAR(100),
					`image_w` VARCHAR(10),
					`image_h` VARCHAR(10),
					`html` TEXT,
					`active` tinyint(1) unsigned NOT NULL DEFAULT \'1\',
					PRIMARY KEY (`id_item`)
			) ENGINE = '._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;'
            )
        );

    }

    public function installFixtures($languages = null)
    {
        $result = true;

        if ($languages === null) {
            $languages = Language::getLanguages(true);
        }

        foreach ($languages as $language) {
            for ($i = 1; $i < 6; $i++) {
                $result &= $this->installFixture('home', $i, $this->context->shop->id, $language['id_lang']);
            }

            for ($i = 6; $i < 8; $i++) {
                $result &= $this->installFixture('top', $i, $this->context->shop->id, $language['id_lang']);
            }
        }

        return $result;
    }

    protected function installFixture($hook, $idImage, $idShop, $idLang)
    {
        $result = true;

        $sizes = @getimagesize((dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'banner-img'.(int) $idImage.'.jpg'));
        $width = (isset($sizes[0]) && $sizes[0]) ? (int) $sizes[0] : 0;
        $height = (isset($sizes[1]) && $sizes[1]) ? (int) $sizes[1] : 0;

        $result &= Db::getInstance()->Execute(
            '
			INSERT INTO `'._DB_PREFIX_.'themeconfigurator` (
					`id_shop`, `id_lang`, `item_order`, `title`, `title_use`, `hook`, `url`, `target`, `image`, `image_w`, `image_h`, `html`, `active`
			) VALUES (
				\''.(int) $idShop.'\',
				\''.(int) $idLang.'\',
				\''.(int) $idImage.'\',
				\'\',
				\'0\',
				\''.pSQL($hook).'\',
				\'http://www.prestashop.com/\',
				\'0\',
				\'banner-img'.(int) $idImage.'.jpg\',
				'.$width.',
				'.$height.',
				\'\',
				1)
			'
        );

        return $result;
    }

    public function createAjaxController()
    {
        $tab = new Tab();
        $tab->active = 1;
        $languages = Language::getLanguages(false);
        if (is_array($languages)) {
            foreach ($languages as $language) {
                $tab->name[$language['id_lang']] = 'themeconfigurator';
            }
        }
        $tab->class_name = 'AdminThemeConfigurator';
        $tab->module = $this->name;
        $tab->id_parent = -1;

        return (bool) $tab->add();
    }

    public function uninstall()
    {
        $images = [];
        if (count(Db::getInstance()->executeS('SHOW TABLES LIKE \''._DB_PREFIX_.'themeconfigurator\''))) {
            $images = Db::getInstance()->executeS('SELECT image FROM `'._DB_PREFIX_.'themeconfigurator`');
        }
        foreach ($images as $image) {
            $this->deleteImage($image['image']);
        }

        if (!Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'themeconfigurator`') || !$this->_removeAjaxContoller() || !parent::uninstall()) {
            return false;
        }

        return true;
    }

    protected function deleteImage($image)
    {
        $fileName = $this->uploads_path.$image;

        if (realpath(dirname($fileName)) != realpath($this->uploads_path)) {
            Tools::dieOrLog(sprintf('Could not find upload directory'));
        }

        if ($image != '' && is_file($fileName) && !strpos($fileName, 'banner-img') && !strpos($fileName, 'bg-theme') && !strpos($fileName, 'footer-bg')) {
            unlink($fileName);
        }
    }

    private function _removeAjaxContoller()
    {
        if ($idTab = (int) Tab::getIdFromClassName('AdminThemeConfigurator')) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        return true;
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') != $this->name) {
            return;
        }

        $this->context->controller->addCSS($this->_path.'css/admin.css');
        $this->context->controller->addJquery();
        $this->context->controller->addJS($this->_path.'js/admin.js');
    }

    public function hookdisplayHeader()
    {
        $this->context->controller->addCss($this->_path.'css/hooks.css', 'all');

        if ((int) Configuration::get('PS_TC_ACTIVE') == 1 && Tools::getValue('live_configurator_token') && Tools::getValue('live_configurator_token') == $this->getLiveConfiguratorToken() && $this->checkEnvironment()) {
            $this->context->controller->addCSS($this->_path.'css/live_configurator.css');
            $this->context->controller->addJS($this->_path.'js/live_configurator.js');

            if (Tools::getValue('theme')) {
                $this->context->controller->addCss($this->_path.'css/'.Tools::getValue('theme').'.css', 'all');
            }

            if (Tools::getValue('theme_font')) {
                $this->context->controller->addCss($this->_path.'css/'.Tools::getValue('theme_font').'.css', 'all');
            }
        } else {
            if (Configuration::get('PS_TC_THEME') != '') {
                $this->context->controller->addCss($this->_path.'css/'.Configuration::get('PS_TC_THEME').'.css', 'all');
            }

            if (Configuration::get('PS_TC_FONT') != '') {
                $this->context->controller->addCss($this->_path.'css/'.Configuration::get('PS_TC_FONT').'.css', 'all');
            }
        }

        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'category') {
            $this->context->smarty->assign(
                [
                    'display_subcategories' => (int) Configuration::get('PS_SET_DISPLAY_SUBCATEGORIES'),
                ]
            );

            return $this->display(__FILE__, 'hook.tpl');
        }
    }

    public function getLiveConfiguratorToken()
    {
        return Tools::getAdminToken(
            $this->name.(int) Tab::getIdFromClassName($this->name)
            .(is_object(Context::getContext()->employee) ? (int) Context::getContext()->employee->id :
                Tools::getValue('id_employee'))
        );
    }

    protected function checkEnvironment()
    {
        $cookie = new Cookie('psAdmin', '', (int) Configuration::get('PS_COOKIE_LIFETIME_BO'));

        return isset($cookie->id_employee) && isset($cookie->passwd) && Employee::checkPassword($cookie->id_employee, $cookie->passwd);
    }

    public function hookActionObjectLanguageAddAfter($params)
    {
        return $this->installFixtures([['id_lang' => (int) $params['object']->id]]);
    }

    public function hookdisplayTopColumn()
    {
        return $this->hookdisplayTop();
    }

    public function hookdisplayTop()
    {
        if (!isset($this->context->controller->php_self) || $this->context->controller->php_self != 'index') {
            return;
        }
        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('top'),
                'hook'      => 'top',
            ]
        );

        return $this->display(__FILE__, 'hook.tpl');
    }

    protected function getItemsFromHook($hook)
    {
        if (!$hook) {
            return false;
        }

        return Db::getInstance()->ExecuteS(
            '
		SELECT *
		FROM `'._DB_PREFIX_.'themeconfigurator`
		WHERE id_shop = '.(int) $this->context->shop->id.' AND id_lang = '.(int) $this->context->language->id.'
		AND hook = \''.pSQL($hook).'\' AND active = 1
		ORDER BY item_order ASC'
        );
    }

    public function hookDisplayHome()
    {
        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('home'),
                'hook'      => 'home',
            ]
        );

        return $this->display(__FILE__, 'hook.tpl');
    }

    public function hookDisplayLeftColumn()
    {
        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('left'),
                'hook'      => 'left',
            ]
        );

        return $this->display(__FILE__, 'hook.tpl');
    }

    public function hookDisplayRightColumn()
    {
        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('right'),
                'hook'      => 'right',
            ]
        );

        return $this->display(__FILE__, 'hook.tpl');
    }

    public function hookDisplayFooter()
    {
        $html = '';

        if ((int) Configuration::get('PS_TC_ACTIVE') == 1 && Tools::getValue('live_configurator_token') && Tools::getValue('live_configurator_token') == $this->getLiveConfiguratorToken() && Tools::getIsset('id_employee') && $this->checkEnvironment()) {
            if (Tools::isSubmit('submitLiveConfigurator')) {
                Configuration::updateValue('PS_TC_THEME', Tools::getValue('theme'));
                Configuration::updateValue('PS_TC_FONT', Tools::getValue('theme_font'));
            }

            $adImage = $this->_path.'img/'.$this->context->language->iso_code.'/advertisement.png';

            if (!file_exists($adImage)) {
                $adImage = $this->_path.'img/en/advertisement.png';
            }

            $this->smarty->assign(
                [
                    'themes'                  => Tools::unserialize(Configuration::get('PS_TC_THEMES')),
                    'fonts'                   => Tools::unserialize(Configuration::get('PS_TC_FONTS')),
                    'theme_font'              => Tools::getValue('theme_font', Configuration::get('PS_TC_FONT')),
                    'live_configurator_token' => $this->getLiveConfiguratorToken(),
                    'id_shop'                 => (int) $this->context->shop->id,
                    'id_employee'             => is_object($this->context->employee) ? (int) $this->context->employee->id :
                        Tools::getValue('id_employee'),
                    'advertisement_image'     => $adImage,
                    'advertisement_url'       => 'http://addons.prestashop.com/en/205-premium-templates?utm_source=back-office'
                        .'&utm_medium=theme-configurator'
                        .'&utm_campaign=back-office-'.Tools::strtoupper($this->context->language->iso_code)
                        .'&utm_content='.(defined('_PS_HOST_MODE_') ? 'ondemand' : 'download'),
                    'advertisement_text'      => $this->l('Over 800 PrestaShop premium templates! Browse now!'),
                ]
            );

            $html .= $this->display(__FILE__, 'live_configurator.tpl');
        }

        $this->context->smarty->assign(
            [
                'htmlitems' => $this->getItemsFromHook('footer'),
                'hook'      => 'footer',
            ]
        );

        return $html.$this->display(__FILE__, 'hook.tpl');
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitModule')) {
            Configuration::updateValue('PS_QUICK_VIEW', (int) Tools::getValue('quick_view'));
            Configuration::updateValue('PS_TC_ACTIVE', (int) Tools::getValue('live_conf'));
            Configuration::updateValue('PS_GRID_PRODUCT', (int) Tools::getValue('grid_list'));
            Configuration::updateValue('PS_SET_DISPLAY_SUBCATEGORIES', (int) Tools::getValue('sub_cat'));
            foreach ($this->getConfigurableModules() as $module) {
                if (!isset($module['is_module']) || !$module['is_module'] || !Validate::isModuleName($module['name']) || !Tools::isSubmit($module['name'])) {
                    continue;
                }

                $moduleInstance = Module::getInstanceByName($module['name']);
                if ($moduleInstance === false || !is_object($moduleInstance)) {
                    continue;
                }

                $is_installed = (int) Validate::isLoadedObject($moduleInstance);
                if ($is_installed) {
                    if (($active = (int) Tools::getValue($module['name'])) == $moduleInstance->active) {
                        continue;
                    }

                    if ($active) {
                        $moduleInstance->enable();
                    } else {
                        $moduleInstance->disable();
                    }
                } else {
                    if ((int) Tools::getValue($module['name'])) {
                        $moduleInstance->install();
                    }
                }
            }
        }

        if (Tools::isSubmit('newItem')) {
            $this->addItem();
        } elseif (Tools::isSubmit('updateItem')) {
            $this->updateItem();
        } elseif (Tools::isSubmit('removeItem')) {
            $this->removeItem();
        }

        $html = $this->renderConfigurationForm();
        $html .= $this->renderThemeConfiguratorForm();

        return $html;
    }

    protected function getConfigurableModules()
    {
        // Construct the description for the 'Enable Live Configurator' switch
        if ($this->context->shop->getBaseURL()) {
            $request =
                'live_configurator_token='.$this->getLiveConfiguratorToken()
                .'&id_employee='.(int) $this->context->employee->id
                .'&id_shop='.(int) $this->context->shop->id
                .(Configuration::get('PS_TC_THEME') != '' ? '&theme='.Configuration::get('PS_TC_THEME') : '')
                .(Configuration::get('PS_TC_FONT') != '' ? '&theme_font='.Configuration::get('PS_TC_FONT') : '');
            $url = $this->context->link->getPageLink('index', null, $id_lang = null, $request);

            $desc = '<a class="btn btn-default" href="'.$url.'" onclick="return !window.open($(this).attr(\'href\'));" id="live_conf_button">'
                .$this->l('View').' <i class="icon-external-link"></i></a><br />'
                .$this->l('Only you can see this on your front office - your visitors will not see this tool.');
        } else {
            $desc = $this->l('Only you can see this on your front office - your visitors will not see this tool.');
        }

        $ret = [
            [
                'label'     => $this->l('Display links to your store\'s social accounts (Twitter, Facebook, etc.)'),
                'name'      => 'blocksocial',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blocksocial')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display your contact information'),
                'name'      => 'blockcontactinfos',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blockcontactinfos')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display social sharing buttons on the product\'s page'),
                'name'      => 'socialsharing',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('socialsharing')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display the Facebook block on the home page'),
                'name'      => 'blockfacebook',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blockfacebook')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display the custom CMS information block'),
                'name'      => 'blockcmsinfo',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blockcmsinfo')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label' => $this->l('Display quick view window on homepage and category pages'),
                'name'  => 'quick_view',
                'value' => (int) Tools::getValue('PS_QUICK_VIEW', Configuration::get('PS_QUICK_VIEW')),
            ],
            [
                'label' => $this->l('Display categories as a list of products instead of the default grid-based display'),
                'name'  => 'grid_list',
                'value' => (int) Configuration::get('PS_GRID_PRODUCT'),
                'desc'  => $this->l('Works only for first-time users. This setting is overridden by the user\'s choice as soon as the user cookie is set.'),
            ],
            [
                'label'     => $this->l('Display top banner'),
                'name'      => 'blockbanner',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('blockbanner')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
            [
                'label'     => $this->l('Display logos of available payment methods'),
                'name'      => 'productpaymentlogos',
                'value'     => (int) Validate::isLoadedObject($module = Module::getInstanceByName('productpaymentlogos')) && $module->isEnabledForShopContext(),
                'is_module' => true,
            ],
        ];

        if ($this->context->theme->name !== 'community-theme-default') {
            $ret[] = [
                'label' => $this->l('Display Live Configurator'),
                'name'  => 'live_conf',
                'value' => (int) Tools::getValue('PS_TC_ACTIVE', Configuration::get('PS_TC_ACTIVE')),
                'hint'  => $this->l('This customization tool allows you to make color and font changes in your theme.'),
                'desc'  => $desc,
            ];
        }

        $ret[] = [
                'label' => $this->l('Display subcategories'),
                'name'  => 'sub_cat',
                'value' => (int) Tools::getValue('PS_SET_DISPLAY_SUBCATEGORIES', Configuration::get('PS_SET_DISPLAY_SUBCATEGORIES')),
        ];

        return $ret;
    }

    protected function addItem()
    {
        $title = Tools::getValue('item_title');
        $content = Tools::getValue('item_html');

        if (!Validate::isCleanHtml($title, (int) Configuration::get('PS_ALLOW_HTML_IFRAME'))
            || !Validate::isCleanHtml($content, (int) Configuration::get('PS_ALLOW_HTML_IFRAME'))
        ) {
            $this->context->smarty->assign('error', $this->l('Invalid content'));

            return false;
        }

        if (!$currentOrder = (int) Db::getInstance()->getValue(
            '
			SELECT item_order + 1
			FROM `'._DB_PREFIX_.'themeconfigurator`
			WHERE
				id_shop = '.(int) $this->context->shop->id.'
				AND id_lang = '.(int) Tools::getValue('id_lang').'
				AND hook = \''.pSQL(Tools::getValue('item_hook')).'\'
				ORDER BY item_order DESC'
        )
        ) {
            $currentOrder = 1;
        }

        $imageW = is_numeric(Tools::getValue('item_img_w')) ? (int) Tools::getValue('item_img_w') : '';
        $imageH = is_numeric(Tools::getValue('item_img_h')) ? (int) Tools::getValue('item_img_h') : '';

        if (!empty($_FILES['item_img']['name'])) {
            if (!$image = $this->uploadImage($_FILES['item_img'], $imageW, $imageH)) {
                return false;
            }
        } else {
            $image = '';
            $imageW = '';
            $imageH = '';
        }

        if (!Db::getInstance()->Execute(
            '
			INSERT INTO `'._DB_PREFIX_.'themeconfigurator` (
					`id_shop`, `id_lang`, `item_order`, `title`, `title_use`, `hook`, `url`, `target`, `image`, `image_w`, `image_h`, `html`, `active`
			) VALUES (
					\''.(int) $this->context->shop->id.'\',
					\''.(int) Tools::getValue('id_lang').'\',
					\''.(int) $currentOrder.'\',
					\''.pSQL($title).'\',
					\''.(int) Tools::getValue('item_title_use').'\',
					\''.pSQL(Tools::getValue('item_hook')).'\',
					\''.pSQL(Tools::getValue('item_url')).'\',
					\''.(int) Tools::getValue('item_target').'\',
					\''.pSQL($image).'\',
					\''.pSQL($imageW).'\',
					\''.pSQL($imageH).'\',
					\''.pSQL($this->filterVar($content), true).'\',
					1)'
        )
        ) {
            if (!Tools::isEmpty($image)) {
                $this->deleteImage($image);
            }

            $this->context->smarty->assign('error', $this->l('An error occurred while saving data.'));

            return false;
        }

        $this->context->smarty->assign('confirmation', $this->l('New item successfully added.'));

        return true;
    }

    protected function uploadImage($image, $imageW = '', $imageH = '')
    {
        $res = false;
        if (is_array($image) && (ImageManager::validateUpload($image, $this->max_image_size) === false) && ($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) && move_uploaded_file($image['tmp_name'], $tmpName)) {
            $salt = sha1(microtime());
            $pathinfo = pathinfo($image['name']);
            $imgName = $salt.'_'.Tools::str2url($pathinfo['filename']).'.'.$pathinfo['extension'];

            if (ImageManager::resize($tmpName, dirname(__FILE__).'/img/'.$imgName, $imageW, $imageH)) {
                $res = true;
            }
        }

        if (!$res) {
            $this->context->smarty->assign('error', $this->l('An error occurred during the image upload.'));

            return false;
        }

        return $imgName;
    }

    protected function filterVar($value)
    {
        if (version_compare(_PS_VERSION_, '1.6.0.7', '>=') === true) {
            return Tools::purifyHTML($value);
        } else {
            return filter_var($value, FILTER_SANITIZE_STRING);
        }
    }

    protected function updateItem()
    {
        $idItem = (int) Tools::getValue('item_id');
        $title = Tools::getValue('item_title');
        $content = Tools::getValue('item_html');

        if (!Validate::isCleanHtml($title, (int) Configuration::get('PS_ALLOW_HTML_IFRAME')) || !Validate::isCleanHtml($content, (int) Configuration::get('PS_ALLOW_HTML_IFRAME'))) {
            $this->context->smarty->assign('error', $this->l('Invalid content'));

            return false;
        }

        $newImage = '';
        $imageW = (is_numeric(Tools::getValue('item_img_w'))) ? (int) Tools::getValue('item_img_w') : '';
        $imageH = (is_numeric(Tools::getValue('item_img_h'))) ? (int) Tools::getValue('item_img_h') : '';

        if (!empty($_FILES['item_img']['name'])) {
            if ($oldImage = Db::getInstance()->getValue('SELECT image FROM `'._DB_PREFIX_.'themeconfigurator` WHERE id_item = '.(int) $idItem)) {
                if (file_exists(dirname(__FILE__).'/img/'.$oldImage)) {
                    @unlink(dirname(__FILE__).'/img/'.$oldImage);
                }
            }

            if (!$image = $this->uploadImage($_FILES['item_img'], $imageW, $imageH)) {
                return false;
            }

            $newImage = 'image = \''.pSQL($image).'\',';
        }

        if (!Db::getInstance()->execute(
            '
			UPDATE `'._DB_PREFIX_.'themeconfigurator` SET
					title = \''.pSQL($title).'\',
					title_use = '.(int) Tools::getValue('item_title_use').',
					hook = \''.pSQL(Tools::getValue('item_hook')).'\',
					url = \''.pSQL(Tools::getValue('item_url')).'\',
					target = '.(int) Tools::getValue('item_target').',
					'.$newImage.'
					image_w = '.(int) $imageW.',
					image_h = '.(int) $imageH.',
					active = '.(int) Tools::getValue('item_active').',
					html = \''.pSQL($this->filterVar($content), true).'\'
			WHERE id_item = '.(int) Tools::getValue('item_id')
        )
        ) {
            if ($image = Db::getInstance()->getValue('SELECT image FROM `'._DB_PREFIX_.'themeconfigurator` WHERE id_item = '.(int) Tools::getValue('item_id'))) {
                $this->deleteImage($image);
            }

            $this->context->smarty->assign('error', $this->l('An error occurred while saving data.'));

            return false;
        }

        $this->context->smarty->assign('confirmation', $this->l('Successfully updated.'));

        return true;
    }

    protected function removeItem()
    {
        $idItem = (int) Tools::getValue('item_id');

        if ($image = Db::getInstance()->getValue('SELECT image FROM `'._DB_PREFIX_.'themeconfigurator` WHERE id_item = '.(int) $idItem)) {
            $this->deleteImage($image);
        }

        Db::getInstance()->delete(_DB_PREFIX_.'themeconfigurator', 'id_item = '.(int) $idItem);

        if (Db::getInstance()->Affected_Rows() == 1) {
            Db::getInstance()->execute(
                '
				UPDATE `'._DB_PREFIX_.'themeconfigurator`
				SET item_order = item_order-1
				WHERE (
					item_order > '.(int) Tools::getValue('item_order').' AND
					id_shop = '.(int) $this->context->shop->id.' AND
					hook = \''.pSQL(Tools::getValue('item_hook')).'\')'
            );
            Tools::redirectAdmin('index.php?tab=AdminModules&configure='.$this->name.'&conf=6&token='.Tools::getAdminTokenLite('AdminModules'));
        } else {
            $this->context->smarty->assign('error', $this->l('Can\'t delete the slide.'));
        }
    }

    public function renderConfigurationForm()
    {
        $inputs = [];

        foreach ($this->getConfigurableModules() as $module) {
            $desc = '';

            if (isset($module['is_module']) && $module['is_module']) {
                $moduleInstance = Module::getInstanceByName($module['name']);
                if (Validate::isLoadedObject($moduleInstance) && method_exists($moduleInstance, 'getContent')) {
                    $desc = '<a class="btn btn-default" href="'.$this->context->link->getAdminLink('AdminModules', true).'&configure='.urlencode($moduleInstance->name).'&tab_module='.$moduleInstance->tab.'&module_name='.urlencode($moduleInstance->name).'">'.$this->l('Configure').' <i class="icon-external-link"></i></a>';
                }
            }
            if (!$desc && isset($module['desc']) && $module['desc']) {
                $desc = $module['desc'];
            }

            $inputs[] = [
                'type'   => 'switch',
                'label'  => $module['label'],
                'name'   => $module['name'],
                'desc'   => $desc,
                'values' => [
                    [
                        'id'    => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id'    => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ];
        }

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => $inputs,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    public function getConfigFieldsValues()
    {
        $values = [];
        foreach ($this->getConfigurableModules() as $module) {
            $values[$module['name']] = $module['value'];
        }

        return $values;
    }

    protected function renderThemeConfiguratorForm()
    {
        $idShop = (int) $this->context->shop->id;
        $items = [];
        $hooks = [];

        $this->context->smarty->assign(
            'htmlcontent', [
                'admin_tpl_path' => $this->admin_tpl_path,
                'hooks_tpl_path' => $this->hooks_tpl_path,

                'info' => [
                    'module'    => $this->name,
                    'name'      => $this->displayName,
                    'version'   => $this->version,
                    'psVersion' => _PS_VERSION_,
                    'context'   => (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') == 0) ? 1 : ($this->context->shop->getTotalShops() != 1) ? $this->context->shop->getContext() : 1,
                ],
            ]
        );

        foreach ($this->languages as $language) {
            $hooks[$language['id_lang']] = [
                'home',
                'top',
                'left',
                'right',
                'footer',
            ];

            foreach ($hooks[$language['id_lang']] as $hook) {
                $items[$language['id_lang']][$hook] = Db::getInstance()->ExecuteS(
                    '
					SELECT * FROM `'._DB_PREFIX_.'themeconfigurator`
					WHERE id_shop = '.(int) $idShop.'
					AND id_lang = '.(int) $language['id_lang'].'
					AND hook = \''.pSQL($hook).'\'
					ORDER BY item_order ASC'
                );
            }
        }

        $this->context->smarty->assign(
            'htmlitems', [
                'items'      => $items,
                'theme_url'  => $this->context->link->getAdminLink('AdminThemeConfigurator'),
                'lang'       => [
                    'default'  => $this->default_language,
                    'all'      => $this->languages,
                    'lang_dir' => _THEME_LANG_DIR_,
                    'user'     => $this->context->language->id,
                ],
                'postAction' => 'index.php?tab=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=other&module_name='.$this->name.'',
                'id_shop'    => $idShop,
            ]
        );

        $this->context->controller->addJqueryUI('ui.sortable');

        return $this->display(__FILE__, 'views/templates/admin/admin.tpl');
    }
}

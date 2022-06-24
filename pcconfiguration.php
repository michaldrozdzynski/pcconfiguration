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

if (!defined('_PS_VERSION_')) {
    exit;
}
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
class PcConfiguration extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'pcconfiguration';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Michał Drożdżyński';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PC configuration');
        $this->description = $this->l('PC configuration');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');

        Configuration::updateValue('pcConfigurationCategoryId', 2);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayProductConfiguration') &&
            $this->registerHook('displayProductAdditionalInfo');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    public function getContent() {
        if (Tools::isSubmit('viewpc_configurations')) {
            return $this->getContentDetail();
        }
        if (Tools::isSubmit('deletepc_configuration_categories')) {
            $id = Tools::getValue('id_pc_configuration_category');
            $query =  'DELETE FROM `' . _DB_PREFIX_ . 'pc_configuration_categories` WHERE id_pc_configuration_category = ' . $id; 
            Db::getInstance()->execute($query);
        }

        if (Tools::isSubmit('submitAddCategory')) {
            return $this->getContentDetail();
        }

        if (Tools::isSubmit('addConfiguration')) {
            return $this->addConfiguration();
        }

        if (Tools::isSubmit('submitAddConfiguration')) {
            $productId = Tools::getValue('product');
            $attributeId = Tools::getValue('attribute');
            $db = Db::getInstance();
            $db->insert("pc_configurations", [
                'id_product' => $productId,
                'id_attribute' => $attributeId,
            ]);
        }

        if (Tools::isSubmit('deletepc_configurations')) {
            $id = Tools::getValue('id_pc_configuration');
            $query =  'DELETE FROM `' . _DB_PREFIX_ . 'pc_configuration_categories` WHERE id_pc_configuration = ' . $id; 
            Db::getInstance()->execute($query);

            $query =  'DELETE FROM `' . _DB_PREFIX_ . 'pc_configurations` WHERE id_pc_configuration = ' . $id; 
            Db::getInstance()->execute($query);
        }

        if (Tools::isSubmit('submitConfiguration')) {
            $catId = Tools::getValue('category');
            Configuration::updateValue('pcConfigurationCategoryId', $catId);
        }

        return $this->renderForm() . $this->pcConfigurationTable();
    }

    public function getContentDetail() {
        if (Tools::isSubmit('addCategory')) {
            return $this->addCategoryForm();
        }

        if (Tools::isSubmit('submitAddCategory')) {
            $categoryId = Tools::getValue('category');
            $id = Tools::getValue('id_pc_configuration');
            $description = Tools::getValue('description');
            $type = Tools::getValue('type');
            $add_to_package = Tools::getValue('add_to_package');

            $db = Db::getInstance();
            $db->insert("pc_configuration_categories", [
                'id_category' => $categoryId,
                'id_pc_configuration' => $id,
                'description' => $description,
                'type' => $type,
                'add_to_package' => $add_to_package,
            ]);
        }
        return $this->pcConfigurationDetailTable();
    }

    public function addCategoryForm() {
        $categories = Category::getAllCategoriesName();

        $types = [
            0 => [
                'id_option' => 1,
                'name' => 'Select',
            ],
            1 => [
                'id_option' => 2,
                'name' => 'List',
            ],
            2 => [
                'id_option' => 3,
                'name' => 'Hidden',
            ],
            3 => [
                'id_option' => 4,
                'name' => 'Big List',
            ]
        ];

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-link',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Category'),
                        'name' => 'category',
                        'size' => 1,
                        'options' => [
                            'query' => $categories,
                            'id' => 'id_category',
                            'name' => 'name',
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Description'),
                        'name' => 'description',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Type'),
                        'name' => 'type',
                        'options' => array(
                            'query' => $types,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Add to package'),
                        'name' => 'add_to_package',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'id_pc_configuration',
                    ],
                ],
                'submit' => [
                    'name' => 'submitAddCategory',
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
                'buttons' => array(
                    array(
                        'href' => AdminController::$currentIndex.'&configure='.$this->name.'&viewpc_configurations=&id_pc_configuration='. Tools::getValue('id_pc_configuration').'&token='.Tools::getAdminTokenLite('AdminModules'),
                        'title' => $this->l('Back to list'),
                        'icon' => 'process-icon-back'
                    )
                )
            ],
        ];
   

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
        $helper->fields_value = array(
            'id_pc_configuration' => Tools::getValue('id_pc_configuration'),
       );

        return $helper->generateForm([$fields_form]);
    }

    public function addConfiguration()
    {
        $attributes = AttributeGroup::getAttributesGroups($this->context->language->id);
        $i = 0;
        foreach ($attributes as $attribute) {
             $options[$i] = [
                'id_option' => $attribute['id_attribute_group'],
                'name' => $attribute['public_name'],
              ];
              $i++;
         }
         $products = Product::getProducts(Context::getContext()->language->id, 0, 0, 'date_upd', 'ASC', Configuration::get('pcConfigurationCategoryId'), true);

            $fields_form = [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Configuration'),
                        'icon' => 'icon-link',
                    ],
                    'input' => [
                        [
                            'type' => 'select',
                            'label' => $this->l('Attribute Name'),
                            'name' => 'attribute',
                            'size' => 1,
                            'required' => true,
                            'options' => [
                                'query' => $options,
                                'id' => 'id_option',
                                'name' => 'name',
                            ]
                        ],
                        [
                            'type' => 'select',
                            'label' => $this->l('Product Name'),
                            'name' => 'product',
                            'size' => 1,
                            'required' => true,
                            'options' => [
                                'query' => $products,
                                'id' => 'id_product',
                                'name' => 'name',
                            ]
                        ],
                    ],
                    'submit' => [
                        'name' => 'submitAddConfiguration',
                        'title' => $this->trans('Save', [], 'Admin.Actions'),
                    ],
                    'buttons' => array(
                        array(
                            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
                            'title' => $this->l('Back to list'),
                            'icon' => 'process-icon-back'
                        )
                    )
                ],
            ];
       

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function pcConfigurationDetailTable() {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'pc_configuration_categories WHERE id_pc_configuration = '. Tools::getValue('id_pc_configuration') .' ORDER BY id_pc_configuration_category';

        $results = Db::getInstance()->executeS($sql);
        $fields_list = array(
          'id_pc_configuration_category'=> array(
              'title' => "ID",
              'align' => 'center',
              'class' => 'fixed-width-xs',
              'search' => false,
              'orderby' => true,
            ),
            'id_category' => array(
                'title' => $this->l('Category'),
                'class' => 'fixed-width-xxl',
                'callback' => 'displayCategoryName',
                'callback_object' => $this,
                'search' => false,
              ),
            'description' => array(
                'title' => $this->l('Description'),
                'class' => 'fixed-width-xxl',
                'search' => false
            ),
            'type' => array(
                'title' => $this->l('Type'),
                'class' => 'fixed-width-xxl',
                'search' => false,
            ),
            'add_to_package' => array(
                'title' => $this->l('Add to package'),
                'class' => 'fixed-width-xxl',
                'search' => false,
                'align' => 'center',
        		'type' => 'bool',
            )
        );
  
        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->identifier = 'id_pc_configuration_category';
        $helper->table = 'pc_configuration_categories';
        $helper->actions = ['delete'];
        $helper->actions['delete'] = array (
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&add'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
			'desc' => $this->l('Add new')
        );
        $helper->show_toolbar = false;
        $helper->listTotal = count($results);
        $helper->_default_pagination = 10;
        $helper->_pagination = array(5, 10, 50, 100);
        $helper->toolbar_btn['new'] = [
            'href' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name, 'viewpc_configurations' => '', 'module_name' => $this->name, 'addCategory' => '', 'id_pc_configuration' => Tools::getValue('id_pc_configuration'),]),
            'desc' => $this->l('Add New Configuration'),
        ];
        $helper->module = $this;
        $helper->title = $this->l('Category list');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $page = ( $page = Tools::getValue( 'submitFilter' . $helper->table ) ) ? $page : 1;
        $pagination = ( $pagination = Tools::getValue( $helper->table . '_pagination' ) ) ? $pagination : 10;
        $content = $this->paginate_content( $results, $page, $pagination );

        return $helper->generateList($content, $fields_list).$this->display(__FILE__, 'views/templates/admin/footerList.tpl');
    }

    public function pcConfigurationTable() {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'pc_configurations ORDER BY id_pc_configuration';

        $results = Db::getInstance()->executeS($sql);
        $fields_list = array(
          'id_pc_configuration'=> array(
              'title' => "ID",
              'align' => 'center',
              'class' => 'fixed-width-xs',
              'search' => false,
              'orderby' => true,
            ),
          'id_attribute' => array(
              'title' => $this->l('Attribute'),
              'class' => 'fixed-width-xxl',
              'callback' => 'displayAttributeName',
              'callback_object' => $this,
              'search' => false,
            ),
            'id_product' => array(
                'title' => $this->l('Product'),
                'class' => 'fixed-width-xxl',
                'callback' => 'displayProductName',
                'callback_object' => $this,
                'search' => false,
              ),
        );
  
        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->identifier = 'id_pc_configuration';
        $helper->table = 'pc_configurations';
        $helper->actions = ['view', 'delete'];
        $helper->show_toolbar = false;
        $helper->listTotal = count($results);
        $helper->_default_pagination = 10;
        $helper->_pagination = array(5, 10, 50, 100);
        $helper->toolbar_btn['new'] = [
            'href' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name, 'module_name' => $this->name, 'addConfiguration' => '']),
            'desc' => $this->l('Add New Configuration'),
        ];
        $helper->module = $this;
        $helper->title = $this->l('Configuration list');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $page = ( $page = Tools::getValue( 'submitFilter' . $helper->table ) ) ? $page : 1;
        $pagination = ( $pagination = Tools::getValue( $helper->table . '_pagination' ) ) ? $pagination : 10;
        $content = $this->paginate_content( $results, $page, $pagination );

        return $helper->generateList($content, $fields_list);
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value = array(
            'category' => Configuration::get('pcConfigurationCategoryId'),
       );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $categories = Category::getAllCategoriesName();

        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    [
                        'type' => 'select',
                        'label' => $this->l('Main Category Name'),
                        'name' => 'category',
                        'size' => 1,
                        'required' => true,
                        'options' => [
                            'query' => $categories,
                            'id' => 'id_category',
                            'name' => 'name',
                        ]
                    ],
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    public function displayCategoryName($id) {
        $ids[0] = $id;
        $category = Category::getCategoryInformation($ids);

        return $category[key($category)]['name'];
    }

    public function displayProductName($id) {
        $langID = $this->context->language->id;

        $product = new Product($id, false, $langID);

       return $product->name;
    }

    public function displayAttributeName($id) {
        $attributes = AttributeGroup::getAttributesGroups($this->context->language->id);

        foreach ($attributes as $attribute) {
            if ($attribute['id_attribute_group'] == $id) {
                return $attribute['public_name'];
            }
        }
    }

    private function paginate_content( $content, $page = 1, $pagination = 10 ) {

        if( count($content) > $pagination ) {
             $content = array_slice( $content, $pagination * ($page - 1), $pagination );
        }
     
        return $content;
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        if (isset($params['product'])) {
            $quantity = StockAvailable::getQuantityAvailableByProduct($params['product']['id_product'], $params['product']['id_product_attribute']);

            return '<input type="hidden" name="product_quantity" id="product_quantity" value="'.$quantity.'"/>';
        }
    }

    public function hookDisplayProductConfiguration($params) {
        $productId = $params['productId'];

        $product = new Product($productId);
        $attributes = $product->getAttributesGroups(Context::getContext()->language->id);

        $groupsIdArray = [];
        $groupsId = '';

        foreach ($attributes as $attribute) {
            if (!in_array($attribute['id_attribute_group'], $groupsIdArray)) {
              array_push($groupsIdArray, $attribute['id_attribute_group']);

              $groupsId .= $attribute['id_attribute_group'] . '-';
            }
        }
        $groupsId = substr($groupsId, 0, -1);
        $this->context->smarty->assign('groupsId', $groupsId);

        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'pc_configurations WHERE id_product = '. $productId;
        $row = Db::getInstance()->getRow($sql);

        $this->context->smarty->assign('attributeGroupId', $row['id_attribute']);
        $id = $row['id_pc_configuration'];

        $sql = 'SELECT id_category, description FROM ' . _DB_PREFIX_ . 'pc_configuration_categories WHERE id_pc_configuration = '. $id .' ORDER BY id_pc_configuration_category';
        $results = Db::getInstance()->executeS($sql);

        $categoryIds = '';
        foreach ($results as $result) {
            $categoryIds .= $result['id_category'] . '-' . $result['description'] . '/';
        }
        $categoryString =  substr($categoryIds, 0, -1);
        $this->context->smarty->assign('categoryIds', $categoryString);
    	$this->context->smarty->assign('configurationId', $id);

        return $this->display(__FILE__, 'views/templates/hook/pcconfiguration.tpl');
    }
}

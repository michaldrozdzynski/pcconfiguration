<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
use PrestaShop\PrestaShop\Core\Domain\Product\Pack\ValueObject\PackStockType;

require_once(_PS_MODULE_DIR_.'pcconfiguration/classes/MyImportController.php');

class PcConfigurationAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool
     */
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     *
     * @return void
     */
    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('getProductId')){
            $productId = $this->getProductId();

            ob_end_clean();
            header('Content-Type: application/json');
            die(json_encode([
                'productId' => $productId,
            ]));

        } else {
            $productId = Tools::getValue('productId');

            $selectedAttributeId = Tools::getValue('group');

            $elements = explode('/',Tools::getValue('categoryIds'));

            $categories = [];
            $desc = [];
            foreach ($elements as $element) {
                $el = explode('-', $element);
                array_push($categories, $el[0]);
                array_push($desc, $el);
            }
            
            $cats = Category::getCategoryInformations($categories);
        
        	$configurationId = Tools::getValue('configurationId');

            foreach ($cats as $key => $cat) {
                $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'pc_configuration_categories WHERE id_pc_configuration = '. $configurationId .' AND id_category = '. $cat['id_category'];
                $row = Db::getInstance()->getRow($sql);
            

                $cats[$key]['type'] = $row['type'];
                $cats[$key]['add_to_package'] = $row['add_to_package'];
                $cats[$key]['description'] = $row['description'];

                $prods = Product::getProducts(Context::getContext()->language->id, 0, 0, 'date_upd', 'ASC', $cat['id_category'], true);
                $cats[$key]['products'] = [];
                foreach ($prods as $prod) {
                    if ($prod['id_product'] != $productId) {
                        $tempProd = new Product($prod['id_product']);
                        $img = $tempProd->getCover($prod['id_product']);
                        $link = new Link;
                        
                        $img_url = 'https://' . $link->getImageLink($tempProd ->link_rewrite[Context::getContext()->language->id], $img['id_image'], 'ultra_small');
                        $img_url2 = 'https://' . $link->getImageLink($tempProd ->link_rewrite[Context::getContext()->language->id], $img['id_image'], 'cart_default');
                        $prod['image'] = $img_url;
                        $prod['image_big'] = $img_url2;
                        $attributes = $tempProd->getAttributeCombinations( $this->context->language->id);
                        foreach($attributes as $att) {
                            if ($att['id_attribute'] == $selectedAttributeId) {
                                $quantity = $att['quantity'];
                                $price = (int) $att['price'];
                                $price = Tools::convertPriceFull($price);
                                $price = Product::getPriceStatic($prod['id_product'], true, $att['id_product_attribute']);
                                $netto = Product::getPriceStatic($prod['id_product'], false, $att['id_product_attribute']);
                                $prod['quantity'] = (int) $quantity;
                                $prod['id_attribute'] = $att['id_product_attribute'];
                                $prod['price'] = $price;
                                $prod['netto'] = $netto;

                                if ($quantity > 0) {
                                    array_push($cats[$key]['products'], $prod);
                                }
                            }
                        }
                    }
                };
            }

            $fabric = $this->getFabricProd($selectedAttributeId);

            $serviceFabric = new Product(100);
            $price = Product::getPriceStatic(100, true);
            $serviceFabric->price = $price;

            $servicePainting = new Product(99);
            $price = Product::getPriceStatic(99, true);
            $servicePainting->price = $price;

            ob_end_clean();
            header('Content-Type: application/json');
            die(json_encode([
                'cats' => $cats,
                'fabric' => $fabric,
                'serviceFabric' => $serviceFabric,
                'servicePainting' => $servicePainting,
            ]));
        }
    }

    public function getFabricProd($selectedAttributeId) {
        $products = Product::getProducts(Context::getContext()->language->id, 0, 0, 'date_upd', 'ASC', 9, true);

        foreach ($products as $key => $product) {
            $products[$key]['price'] = Product::getPriceStatic($product['id_product']);
            $products[$key]['quantity'] = StockAvailable::getQuantityAvailableByProduct($product['id_product']);

            $prod = new Product($product['id_product']);
            
            $attributes = $prod->getAttributeCombinations($this->context->language->id);
            foreach($attributes as $att) {
                if ($att['id_attribute'] == $selectedAttributeId) {
                    $quantity = $att['quantity'];
                    $price = (int) $att['price'];
                    $price = Tools::convertPriceFull($price);
                    $price = Product::getPriceStatic($product['id_product'], true, $att['id_product_attribute']);
                    $products[$key]['quantity'] = (int) $quantity;
                    $products[$key]['id_attribute'] = $att['id_product_attribute'];
                    $products[$key]['price'] = $price;

                    if ($quantity <= 0) {
                        unset($products[$key]);
                    }
                }
            }
            $img = $prod->getCover($prod->id);
            $link = new Link;
            $img_url = 'https://' . $link->getImageLink($prod->link_rewrite[Context::getContext()->language->id], $img['id_image'], 'ultra_small');
            $products[$key]['image'] = $img_url;
        }

        return $products;
    }

    public function getProductId() {
        $productsInfo = Tools::getValue('productsInfo');

        $productsInfo = explode('/', $productsInfo);

        $attrAndProd = [];
        foreach ($productsInfo as $info) {
            $elements = explode('-', $info);

            $tempArray = [
                'productId' => $elements[1],
                'attributeId' => $elements[0],
            ];

            array_push($attrAndProd, $tempArray);
        }
		
        $packs = [];
        foreach ($attrAndProd as $item) {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'pack WHERE id_product_item = '. $item['productId'] .' AND id_product_attribute_item = '. $item['attributeId'];

            $results = Db::getInstance()->executeS($sql);
            array_push($packs, $results);
        }

        $possibleId = [];

        foreach ($packs[0] as $pack) {
            array_push($possibleId, $pack['id_product_pack']);
        }

        $size = count($packs);

        for ($i = 1; $i < $size; $i++) {
            $tempPossibleId = [];
            foreach ($packs[$i] as $pack) {

                array_push($tempPossibleId, $pack['id_product_pack']);
            }

            $possibleId = array_intersect($possibleId, $tempPossibleId);
        }     
        
        foreach ($possibleId as $id) {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'pack WHERE id_product_pack = '. $id;
            $results = Db::getInstance()->executeS($sql);

            $productIds = [];
            $attrIds = [];
            foreach ($results as $result) {
                array_push($productIds, $result['id_product_item']);    
                array_push($attrIds, $result['id_product_attribute_item']); 
            }

            $isOk =  true;
            foreach ($attrAndProd as $item) {
                if (!in_array($item['productId'], $productIds) || !in_array($item['attributeId'], $attrIds)) {
                    $isOk = false;
                    break;
                }
            }

            if ($isOk && count($attrAndProd) == count($productIds) && count($attrAndProd) == count($attrIds)) {
                return $id;
            }
        }

        return $this->createProductPack();
    }

    public function createProductPack() {
        $price = Tools::getValue('price');
        $productsInfo = Tools::getValue('productsInfo');
        $productsInfo = explode('/', $productsInfo);

        $attrAndProd = [];
        foreach ($productsInfo as $info) {
            $elements = explode('-', $info);

            $tempArray = [
                'productId' => $elements[1],
                'attributeId' => $elements[0],
            ];

            array_push($attrAndProd, $tempArray);
        }

        $packName = '';

        foreach($attrAndProd as $key => $item) {
            $prodId = $item['productId'];
            $prod = new Product($prodId, false, $this->context->language->id);
            $name = $prod->name;
            
            if ($key == 0) {
                $cover = Product::getCover($prod->id);
                $coverId = $cover['id_image'];
                $link = new Link();
                $imageLink = $link->getImageLink($prod->link_rewrite, $coverId);
                $packName .= $name;
            } else {
                $packName .= ' + ' . $name;
            }
            
        }
        $baseProductId = $attrAndProd[0]['productId'];;

        $default_lang = Configuration::get("PS_LANG_DEFAULT");
        $product = new Product();
        $product->name = [$default_lang => $packName];
        $product->price = $price;
        $product->quantity = 1000;
        $product->id_category = [2];
        $product->id_default_category = 2;
        $product->link_rewrite = [$default_lang => 'paczka'];
        $product->id_tax_rules_group = 1;
        $product->pack_stock_type = PackStockType::STOCK_TYPE_PRODUCTS_ONLY;

        if ($product->add()) {
            $product->updateCategories($product->id_category);
            StockAvailable::setQuantity((int) $product->id, 0, $product->quantity, Context::getContext()->shop->id);
        }

        foreach ($attrAndProd as $item) {
            Pack::addItem($product->id, $item['productId'], 1, $item['attributeId']);
        }

        $image = new Image;
        $image->id_product = (int) $product->id;
        $image->position = Image::getHighestPosition($product->id) + 1;
        $image->cover =  true;
        $image->add();

        $url = 'https://' . $imageLink;
        
        if (!$this->copyImg($product->id, $image->id, $url)) {
            $image->delete();
            $sdgs = $gdf;
        }

        return $product->id;
    }

    public function copyImg($id_entity, $id_image, $url, $entity = 'products', $regenerate = true) {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));


        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int) $id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int) $id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int) $id_entity;
                break;
        }
        $url = str_replace(' ', '%20', trim($url));


        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (!ImageManager::checkImageMemoryLimit($url))
            return false;


        // 'file_exists' doesn't work on distant file, and getimagesize makes the import slower.
        // Just hide the warning, the processing will be the same.
        if (Tools::copy($url, $tmpfile)) {
            ImageManager::resize($tmpfile, $path . '.jpg');
            $images_types = ImageType::getImagesTypes($entity);


            if ($regenerate)
                foreach ($images_types as $image_type) {
                    ImageManager::resize($tmpfile, $path . '-' . stripslashes($image_type['name']) . '.jpg', $image_type['width'], $image_type['height']);
                    if (in_array($image_type['id_image_type'], $watermark_types))
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                }
        }
        else {
            unlink($tmpfile);
            return false;
        }
        unlink($tmpfile);
        return true;
    }
}

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

class PcConfigurationCheckpackModuleFrontController extends ModuleFrontController
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

        $productId = $this->getProductId();

        $cover = false;

        if ($productId) {
            $product = new Product($productId);
            $img = $product->getCover($productId);
            $link = new Link;
            $cover = 'https://' . $link->getImageLink($product->link_rewrite[Context::getContext()->language->id], $img['id_image'], 'large_default');            
        }
        
        ob_end_clean();
        header('Content-Type: application/json');

        die(json_encode([
            'cover' => $cover,
            'info' => Tools::getValue('productsInfo'),
            'id' => $productId,
        ]));
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

        return false;
    }
}

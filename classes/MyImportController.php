<?php

class MyImportController extends AdminImportControllerCore {
    public static function copyImg($id_entity, $id_image = null, $url = '', $entity = 'products', $regenerate = true) {
        return parent::copyImg($id_entity, $id_image, $url, $entity, $regenerate);
    }
}
<?php
$_SERVER['HTTPS'] = 1;
require(dirname(__FILE__).'/config/config.inc.php');
require(dirname(__FILE__).'/init.php');

$apiConfig = array(
    # CONFIG
    'clientId' => $clientId,
    'apiKey'   => $apiKey,
    # METHODS
    'methods'  => array(
        'getCategoryTree' => '/v2/category/tree',
        'getCategoryAttributes' => '/v3/category/attribute',
        'getCategoryAttributeValues' => '/v2/category/attribute/values',
        'uploadProducts' => '/v2/product/import',
        'checkLimits' => '/v4/product/info/limit'
    )
);

# UNITS
// public $dimension_unit = Configuration::get('PS_DIMENSION_UNIT');
// public $weight_unit = Configuration::get('PS_WEIGHT_UNIT');

class OzonAPI {
    protected $clientId;
    protected $apiKey;
    public $methods;

    public function __construct($config) {
        $this->clientId = $config['clientId'];
        $this->apiKey = $config['apiKey'];
        $this->methods = $config['methods'];
    }
    
    /**
     * @param int    $category_id
     * @param string $language
     *  Язык в ответе:
     *  EN — английский,
     *  RU — русский,
     *  TR — турецкий,
     *  ZH_HANS — китайский.
     *
     * @return array|null
     */
    public function getCategoryTree(int $category_id = null, string $language = 'DEFAULT')
    {
        if ($category_id) {
            $data['category_id'] = $category_id;
        }
        $data['language'] = $language;

        $response = $this->curlExec($this->methods['getCategoryTree'], $data);

        if (isset($response['result'])) {
            return $response['result'];
        } else {
            # TODO
            // throw error
            // response example:
            // "code": 5,
            // "message": "Category not found",
            // "details": []
            return 'error';
        }
    }
    
    /**
     * @param mixed  $category_id
     * может быть массив до 20 id
     * @param string $attribute_type
     *  Фильтр по характеристикам:
     *  ALL — все характеристики,
     *  REQUIRED — обязательные,
     *  OPTIONAL — дополнительные.
     * @param string $language
     *  Язык в ответе:
     *  EN — английский,
     *  RU — русский,
     *  TR — турецкий.
     *
     * @return array|null
     */
    public function getCategoryAttributes($category_id = null, $attribute_type = 'ALL', $language = 'DEFAULT')
    {
        if ($category_id === null) {
            # TODO
            // throw error
            return null;
        } elseif (!is_array($category_id)) {
            $category_id = array($category_id);
        }

        $data = array(
            'category_id'    => $category_id,
            'attribute_type' => $attribute_type,
            'language'       => $language
        );

        $response = $this->curlExec($this->methods['getCategoryAttributes'], $data);

        if (isset($response)) {
            return $response;
        } else {
            # TODO
            // throw error
            // response example:
            // "code": 3,
            // "message": "proto: syntax error (line 1:16): unexpected token 17034410",
            // "details": []
            return false;
        }
    }
    
    /**
     * @param int    $attribute_id
     * @param int    $category_id
     * @param string $language
     *  Язык в ответе:
     *  EN — английский,
     *  RU — русский,
     *  TR — турецкий.
     * @param int    $last_value_id
     * Идентификатор справочника, с которого нужно начать ответ.
     * Если 10, то в ответе будут справочники, начиная с одиннадцатого.
     * @param int    $limit
     * min = 1 max = 5000
     *
     * @return array|null
     */
    public function getCategoryAttributeValues(int $attribute_id = null, int $category_id = null, string $language = 'DEFAULT', int $last_value_id = 0, int $limit = 5000)
    {
        if ($category_id === null) {
            # TODO
            // throw error
            return null;
        }

        $data = array(
            'attribute_id' => $attribute_id,
            'category_id' => $category_id,
            'language' => $language,
            'last_value_id' => $last_value_id,
            'limit' => $limit
        );

        $response = $this->curlExec($this->methods['getCategoryAttributeValues'], $data);

        if (isset($response['result'])) {
            return $response;
        } else {
            # TODO
            // throw error
            // response example:
            // "code": 3,
            // "message": "proto: syntax error (line 1:16): unexpected token 17034410",
            // "details": []
        }
    }
    
    /**
     * @param array $items
     *
     * @return array|null
     */
    public function uploadProducts($items)
    {
        $data = array(
            'items' => $items
        );

        $response = $this->curlExec($this->methods['uploadProducts'], $data);

        if (isset($response['result'])) {
            return $response['result'];
        } else {
            # TODO
            // throw error
            return 'error';
        }
    }
    
    /**
     * @return array|null
     */
    public function checkLimits()
    {

        $response = $this->curlExec($this->methods['checkLimits']);

        if ($response['total']) {
            return $response;
        } else {
            # TODO
            // throw error
            return 'error';
        }
    }

    public function curlExec($method, $data = null, $request = 'POST') {
        if ($data) {
            $data = Tools::jsonEncode($data);
        }
        $url = 'https://api-seller.ozon.ru'.$method;
        $headers = array(                                                                          
            'Content-Type: application/json',
            'Host: api-seller.ozon.ru',
            'Client-Id: '.$this->clientId,
            'Api-Key: '.$this->apiKey
        ) ;                                                                                  
        $ch = curl_init();
        switch ($request){
            case "GET":
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
               break;
            default:
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
         }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        ob_start();      // prevent any output
        $response = curl_exec($ch);
        ob_end_clean();  // stop preventing output
    
        if (!$response) {
            die("Connection Failure");
        }
    
        curl_close($ch);
        $response_decoded = Tools::jsonDecode($response, true);
    
        return $response_decoded;
    }
}

class OSync {
    /** @var OzonAPI $api */
    public $api;
    
    /** @var OzonCategoriesTree $treeDB */
    public $treeDB;
    
    /** @var OzonCategoriesAttributes $attrDB */
    public $attrDB;
    
    /** @var OzonCategoriesAttributesValues $valDB */
    public $valDB;    

    public function __construct($api, $treeDB, $attrDB, $valDB) {
        $this->api = $api;
        $this->treeDB = $treeDB;
        $this->attrDB = $attrDB;
        $this->valDB = $valDB;
    }

    /**
     * Update OZON category tree
     *
     * @return bool
     */
    public function updateOzonCategoriesTree() {
        $current_categories = $this->treeDB->getCategoriesIdList();
        $ozon_categories = $this->api->getCategoryTree();

        if ($current_categories !== false && $ozon_categories) {
            return $this->recursion($current_categories, $ozon_categories);
        } else {
            return 'error';
        }


    }

    public function recursion(array $site, array $ozon, int $pid = 0) {
        foreach ($ozon as $val) {
            /* checking childs */
            $parent = count($val['children']) > 0 ? true : false;

            /* checking category existence in DB */
            if (!in_array($val['category_id'], $site)) {
                /* add  category to DB */
                $DB = new OzonCategoriesTree();
                $DB->ozon_category_id = $val['category_id'];
                $DB->ozon_category_pid = $pid;
                $DB->ozon_category_is_parent = $parent;
                $DB->ozon_category_name = $val['title'];
                $DB->save();
            }

            /* recursion */
            if ($parent) {
                $this->recursion($site, $val['children'], $val['category_id']);
            }
        }

        return true;
    }

    /**
     * Get OZON attributes and values for category
     * @param int $cid
     * Идентификатор категории
     * @param string $language
     * 'DEFAULT' 'RU' 'EN'
     *
     * @return bool
     */
    public function getOzonReference($cid, $language = 'DEFAULT') {        
        $data = $this->api->getCategoryAttributes(array($cid));
        $result = null;

        if (!isset($data['result'])) {
            # TODO throw ERROR
            /*
            [code] => 3
            [message] => validation error: category not found
            */
            return $result = $data;
        } else {
            $attributes = $data['result'][0]['attributes'];

            foreach ($attributes as &$attribute) {
                if ($attribute['dictionary_id'] > 0) {
                    $attribute_values = array();
                    $lvid = 0;
                    $has_next = true;

                    while ($has_next) {
                        $values = $this->api->getCategoryAttributeValues($attribute['id'], $cid, $language, $lvid);
                        $attribute_values = array_merge($attribute_values, $values['result']);
                        $lvid = end($values['result'])['id'];
                        $has_next = $values['has_next'];
                    }

                    $attribute = array_merge($attribute, array('attribute_values' => $attribute_values));
                }
            }

            unset($attribute);
            $result = $attributes;
        }

        return $result;
    }

    /**
     * Update OZON attributes and attribute values
     * @param int $cid
     * Идентификатор категории
     *
     * @return bool
     */
    public function updateOzonReference($cid = null) {
        /* check 1 category by ID or full check*/
        $current_categories = isset($cid) ? array($cid) : $this->treeDB->getCategoriesIdList();

        foreach ($current_categories as $category) {
            $ozon_attributes = $this->api->getCategoryAttributes(array($category));
            if (isset($ozon_attributes['result'])) {
                // возвращает массив резульатов по категориям
                // тк мы смотрим по одной категории (чтобы не натолкнуться на удалённые)
                // то нам нужен первый и единственный массив из ответа
                $ozon_attributes = $ozon_attributes['result'][0];
            } else {
                # TODO: удалили категорию на озоне, у нас в базе есть
                /*
                [code] => 3
                [message] => validation error: category not found
                */
                continue;
            }
            
            $site_attributes = $this->attrDB->getAttributesIdList($category);
            
            foreach ($ozon_attributes['attributes'] as $attribute) {
                if (!in_array($attribute['id'], $site_attributes)) {
                    /* add attribute to DB */
                    $DB = new OzonCategoriesAttributes();
                    $DB->ozon_attribute_id = $attribute['id'];
                    $DB->ozon_category_id = $category;
                    $DB->name = $attribute['name'];
                    $DB->description = $attribute['description'];
                    $DB->type = $attribute['type'];
                    $DB->is_collection = $attribute['is_collection'];
                    $DB->is_required = $attribute['is_required'];
                    $DB->group_id = $attribute['group_id'];
                    $DB->group_name = $attribute['group_name'];
                    $DB->dictionary_id = $attribute['dictionary_id'];
                    $DB->is_aspect = $attribute['is_aspect'];
                    $DB->category_dependent = $attribute['category_dependent'];
                    $DB->save();
                }

                /* checking attribute values */
            }
        }

        if ($current_categories !== false) {
            return true;
        } else {
            #TODO throw error
            return 'error';
        }
    }

    /**
     * Update OZON attribute values
     * @param int $aid
     * Озоновский идентификатор атрибута
     *
     * @return bool
     */
    // public function updateOzonAttributeValues($aid) {
    //     $current_values = $this->valDB->getCurrentValuesIds($aid);
    //     sort($current_values, SORT_NUMERIC);
    //     $last_value_id = end($current_values) ? end($current_values) : 0;
    //     $has_next = true;

    //     while ($has_next) {
    //         $this->api->
    //     }

    //     if ($current_categories !== false) {
    //         return true;
    //     } else {
    //         #TODO throw error
    //         return 'error';
    //     }
    // }

    /**
     * Update product OZON category
     *
     * @return bool
     */
    public function updateCategory($product, $category) {
        $id = OzonCategories::getProductId($product);
        $categories = new OzonCategories($id);
        $categories->product_id = $product;
        $categories->ozon_category_id = $category;
        if ($categories->save()) {
            return true;
        } else {
            #TODO error log
            return 'error';
        }
    }

    /**
     * Prepare products for upload to OZON
     *
     * @return array
     */
    public function prepareProducts($products) {



    }

    /**
     * Upload products to OZON
     * @param array $products = array of prepared products to upload
     *
     * @return bool
     */
    public function uploadProducts($products) {
        //$products = $this->prepareProducts($products);
        $total = count($products);

        // проверим лимиты
        $limits = $this->api->checkLimits();
        $limitCreate = $limits['daily_create']['limit'] - $limits['daily_create']['usage'];
        $limitUpdate = $limits['daily_update']['limit'] - $limits['daily_update']['usage'];
        $limitTotal = $limits['total']['limit'] - $limits['total']['usage'];

        ### нужно отделить создание от обновления товара
        ### так как разные лимиты в сутки
        ### на новые товары лимит 1500, на обновление 20000
        ### максимум может быть 20000 товаров в личном кабинете
        # TODO: вынести в конфиг все лимиты
        if ($limitTotal - $total < 0) {
            # TODO превышение максимально лимита товаров в магазине
            return false;
        }
        if ($limitCreate - $total < 0) {
            # TODO превышение дневного лимита на создание новых товаров
            return false;
        }


        // проверим количество товаров (если больше ста, то разделим. можно макс 100 за раз выгружать)
        # TODO: лимиты в конфиг
        if ($total > 100) {
            $stack = array_chunk($products, 100);
            foreach ($stack as $items) {
                $result = $this->api->uploadProducts($items);
                #TODO if error
            }
        } else {
            $result = $this->api->uploadProducts($products);
            #TODO if error
        }
    }
}


class OzonCategories extends ObjectModel
{
    /**
     * Product ID
     *
     * @var int
     */
    public $product_id;

    /**
     * OZON category ID
     *
     * @var int
     */
    public $ozon_category_id;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'pm_ozon_categories',
        'primary' => 'id',
        'multilang' => false,
        'multilang_shop' => false,
        'fields' => [
            'product_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'ozon_category_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
        ],
    ];

    /**
     * Return array of collated products
     *
     * @return bool|array
     */
    public static function getProductId($product)
    {
        $sql = 'SELECT id FROM `' . _DB_PREFIX_ . 'pm_ozon_categories` WHERE product_id = ' . $product;

        $result = Db::getInstance()->executeS($sql);

        if (is_array($result)) {
            return count($result) ? $result[0]['id'] : false;
        } else {
            # TODO throw error can't get data from DB
            return false;
        }
    }
}

class OzonCategoriesTree extends ObjectModel
{
    /**
     * OZON category ID
     *
     * @var int
     */
    public $ozon_category_id;

    /**
     * OZON category parent ID
     *
     * @var int
     */
    public $ozon_category_pid;

    /**
     * OZON category is parent
     *
     * @var bool
     */
    public $ozon_category_is_parent;

    /**
     * OZON category name
     *
     * @var string
     */
    public $ozon_category_name;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'pm_ozon_categories_tree',
        'primary' => 'id',
        'multilang' => false,
        'multilang_shop' => false,
        'fields' => [
            'ozon_category_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'ozon_category_pid' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'ozon_category_is_parent' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'ozon_category_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
        ],
    ];

    /**
     * Return array of existing OZON categories
     *
     * @return bool|array
     */
    public static function getCategoriesIdList()
    {
        $sql = 'SELECT ozon_category_id FROM `' . _DB_PREFIX_ . 'pm_ozon_categories_tree`';

        $result = Db::getInstance()->executeS($sql);

        if (is_array($result)) {
            return array_column($result, 'ozon_category_id');
        } else {
            # TODO throw error can't get data from DB
            return false;
        }
    }
}

class OzonCategoriesAttributes extends ObjectModel
{
    /**
     * OZON attribute ID
     *
     * @var int
     */
    public $ozon_attribute_id;

    /**
     * OZON category ID
     *
     * @var int
     */
    public $ozon_category_id;

    /**
     * attribute name
     *
     * @var string
     */
    public $name;

    /**
     * attribute description
     *
     * @var string
     */
    public $description;

    /**
     * attribute type
     *
     * @var string
     */
    public $type;

    /**
     * attribute is collection
     *
     * @var bool
     */
    public $is_collection;

    /**
     * attribute is required
     *
     * @var bool
     */
    public $is_required;

    /**
     * attribute group ID
     *
     * @var int
     */
    public $group_id;

    /**
     * attribute group_name
     *
     * @var string
     */
    public $group_name;

    /**
     * attribute dictionary_id
     *
     * @var int
     */
    public $dictionary_id;

    /**
     * attribute is aspect
     *
     * @var bool
     */
    public $is_aspect;

    /**
     * attribute is category dependent
     *
     * @var bool
     */
    public $category_dependent;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'pm_ozon_categories_attributes',
        'primary' => 'id',
        'multilang' => false,
        'multilang_shop' => false,
        'fields' => [
            'ozon_attribute_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'ozon_category_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'description' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => false],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => false],
            'is_collection' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'is_required' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'group_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => false],
            'group_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => false],
            'dictionary_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => false],
            'is_aspect' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'category_dependent' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false]
        ],
    ];

    /**
     * Return array of existing OZON attributes
     * @param int $id
     * ID категории для выборки
     *
     * @return bool|array
     */
    public static function getAttributesIdList($id)
    {
        $sql = 'SELECT ozon_attribute_id FROM `' . _DB_PREFIX_ . 'pm_ozon_categories_attributes` WHERE ozon_category_id = ' . $id;

        $result = Db::getInstance()->executeS($sql);

        if (is_array($result)) {
            return array_column($result, 'ozon_attribute_id');
        } else {
            # TODO throw error can't get data from DB
            return false;
        }
    }
}

class OzonCategoriesAttributesValues extends ObjectModel
{

    /**
     * OZON value ID
     *
     * @var int
     */
    public $ozon_value_id;

    /**
     * OZON attribute ID
     *
     * @var int
     */
    public $ozon_attribute_id;

    /**
     * attribute value
     *
     * @var string
     */
    public $value;

    /**
     * attribute info
     *
     * @var string
     */
    public $info;

    /**
     * attribute picture
     *
     * @var string
     */
    public $picture;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'pm_ozon_categories_attributes_values',
        'primary' => 'id',
        'multilang' => false,
        'multilang_shop' => false,
        'fields' => [
            'ozon_attribute_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'ozon_category_id' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'value' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'info' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => false],
            'picture' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => false]
        ],
    ];

    /**
     * Return array of existing OZON attribute values IDs
     * @param int $id
     * OZON ID аттрибута для выборки
     *
     * @return bool|array
     */
    public static function getCurrentValuesIds($id)
    {
        $sql = 'SELECT ozon_value_id FROM `' . _DB_PREFIX_ . 'pm_ozon_categories_attributes_values` WHERE ozon_attribute_id = ' . $id;

        $result = Db::getInstance()->executeS($sql);

        if (is_array($result)) {
            return array_column($result, 'ozon_value_id');
        } else {
            # TODO throw error can't get data from DB
            return false;
        }
    }
}



### TEST ###

$api = new OzonAPI($apiConfig);
$treeDB = new OzonCategoriesTree();
$attrDB = new OzonCategoriesAttributes();
$valDB = new OzonCategoriesAttributesValues();
$OSync = new OSync($api, $treeDB, $attrDB, $valDB);
echo '<pre>';
// var_dump($OSync->uploadProducts($test));
// printer($attrDB->getAttributesIdList(33887967));
printer($OSync->getOzonReference(1000003734));
// printer($api->getCategoryAttributes([33887967,1000003734,29972924]));
// printer($api->getCategoryAttributeValues(85, 1000003734, "DEFAULT" ,115860820));
echo '</pre>';



function printer($data) {
    $res = '<pre>';
    $res.= print_r($data);
    $res.= '</pre>';
    $res.= '<br>';
    return $res;
}

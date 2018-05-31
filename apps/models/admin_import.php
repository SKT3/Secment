<?php
ini_set('memory_limit','600M');
class AdminImport extends File
{
    public $module = 'admin_import';
    public $table_name = 'imports';


    protected $has_one = array(
        'file' => array(
            'association_foreign_key' => 'id',
            'foreign_key' => 'module_id',
            'class_name' => 'file',
            'join_table' => 'imports',
            'conditions' => "module = 'admin_import' and keyname='file'"
        ));

    public function import()
    {
		
        Registry()->db = SqlFactory::factory(Config()->DSN);

        Localizer::getInstance('application');

        $category_ids = array();
        $categories = new CategoriesModel();
        $listCategories = $categories->find_all(array(
            'select' => "id, parent_cat_id, lvl, slug",
            'index_on' => 'id'
        ));
		
		foreach ($listCategories as $category) {
            $slug = $category->get_slug_breadcrumb($listCategories);
            $category_ids[$slug] = $category->id;
            $parent_ids[$slug] = $category->parent_cat_id;
        }

        $brands = (new BrandsModel())->findAllForSmarty([],'title', 'id');
        $manif = (new ManufacturersModel())->findAllForSmarty([],'title', 'id');
        $characteristics = (new CharacteristicsModel())->findAllForSmarty([],'title', 'id');
		
        $special_characteristic = (new CharacteristicsModel())->special_characteristic;
        $products = [];

        $_all = Registry()->db->select('products', 'id,nomenclature_num');
        foreach ($_all as $a) {
            $products[$a->nomenclature_num] = $a->id;
        }
		
		// ds();
		
		// echo 123213321;
		// exit;


        require(Config()->LIB_PATH . 'spreadsheet-reader/php-excel-reader/excel_reader2.php');
        require(Config()->LIB_PATH . 'spreadsheet-reader/SpreadsheetReader.php');

		
        $filename = $this->file->get_upload_path().$this->file->filename;
        $Reader = new SpreadsheetReader($filename);
		
        $start_row = 4;
        $current_row = 0;
        $Reader->seek(2);
        $all_characteristics = $Reader->current();
        $chars = array_slice($all_characteristics, 29);

        foreach ($chars as $k => $v) {
            if ( ! $characteristics[$v]) {
                $characteristic = new CharacteristicsModel;
                $characteristic->title = $v;
                $characteristic->save();
                $characteristics[$v] = $characteristic->id;
            }
        }

        foreach ($all_characteristics as $k => $v) {
            if ($characteristics[$v]) {
                $column_to_id[$k] = $characteristics[$v];
            }
        }

		
        foreach ($Reader as $key => $Row) {
			
            //$Row[0], $Row[1], $Row[2]
            $current_row++;

            if ( ! array_filter($Row)) {
                break;
            }

           if ($current_row < $start_row) {
               continue;
           }
            
           //Inflector::slugalize($Row[0].'-'.$Row[1].'-'.$Row[2])
           $slug = Inflector::slugalize($Row[0]);
           if (!$category_ids[$slug]) {
               $c = new CategoriesModel();
               $c->save([
                   'title' => $this->array_ucfirst($Row[0]),
                   'slug' => Inflector::slugalize($Row[0]),
                   'lvl' => 0,
                   'parent_cat_id' => 0
               ]);
               $category_ids[$slug] = $c->id;
               $parent_ids[$slug] = $c->parent_cat_id;
               unset($c);
           }

           $slug_1 = Inflector::slugalize($Row[0].'-'.$Row[1]);
           if (!$category_ids[$slug_1]) {
               $parent_id = isset($category_ids[$slug]) ? $category_ids[$slug] : 0;
               $c = new CategoriesModel();
               $c->save([
                   'title' => $this->array_ucfirst($Row[1]),
                   'slug' => Inflector::slugalize($Row[1]),
                   'lvl' => 1,
                   'parent_cat_id' => $parent_id
               ]);
               $category_ids[$slug_1] = $c->id;
               $parent_ids[$slug_1] = $c->parent_cat_id;
//                var_dump($category_ids[$slug]);
//                d($parent_id);exit;
               unset($c);
           }

           $slug_2 = Inflector::slugalize($Row[0].'-'.$Row[1].'-'.$Row[2]);
           if (!$category_ids[$slug_2]) {
               $parent_id = ($category_ids[$slug_1]) ? $category_ids[$slug_1] : 0;
               $c = new CategoriesModel();
               $c->save([
                   'title' => $this->array_ucfirst($Row[2]),
                   'slug' => Inflector::slugalize($Row[2]),
                   'lvl' => 2,
                   'parent_cat_id' => $parent_id
               ]);
               $category_ids[$slug_2] = $c->id;
               $parent_ids[$slug_2] = $c->parent_cat_id;
               unset($c);
           }

           if ( ! $manif[$Row[7]]) {
               $m = new ManufacturersModel();
               $m->title = $Row[7];
               $m->slug = Inflector::slugalize($Row[7]);
               if ($m->save()) {
                   $manif[$Row[7]] = $m->id;
               } else {
                   d($m->get_errors());
                   d($Row);
                   die('Manufacture sux');
               }
           }


           $has_brand = $Row[23] && isset($brands[$Row[23]]);
           if ( ! $has_brand && $Row[23]) {
               $b = new BrandsModel();
               $b->title = $Row[23];
               $b->slug = Inflector::slugalize($Row[23]);
               $b->logo = $Row[24];
               $b->manufacturer_id = $manif[$Row[7]];
               $has_brand = true;

               if ($b->save()) {
                   $brands[$Row[23]] = $b->id;
               } else {
                   d($b->get_errors());
                   d($Row);
                   die('Brand sux');
               }
           }
//  category_id show_uni_1 show_uni_2 buy_by_2_units oversized add_to_basket set to NOT NULL
           if ( ! $products[(string)$Row[5]]) {
               $p = new ProductsModel();
               $p->_skip_mirror = true;
               $p->brand_id = $has_brand ? $brands[$Row[23]] : null;
               $p->category_id = $category_ids[Inflector::slugalize($Row[0].'-'.$Row[1].'-'.$Row[2])];
               $p->versus_id = $Row[4];
               $p->nomenclature_num = $Row[5];
               $p->catalog_num = $Row[6];
               $p->title = array(
                   'bg-BG' => $Row[21],
                   'en-US' => $Row[22] ? $Row[22] : $Row[21],
               );
               $p->slug = array(
                   'bg-BG' => Inflector::slugalize($Row[21]),
                   'en-US' => $Row[22] ? Inflector::slugalize($Row[22]) : Inflector::slugalize($Row[21]),
               );

               $p->unit_1 = $this->unit_id_match($Row[8]);
               $p->unit_2 = $this->unit_id_match($Row[9]);
               $p->unit_ratio = $Row[10];
//                $p->price_unit_1 = $Row[11];
//                $p->price_unit_2 = $Row[12];
//                $p->new_price_unit_1 = $Row[13];
//                $p->new_price_unit_2 = $Row[14];

               $p->add_to_basket = ($Row[15] == 'ДА') ? 1 : 0;
               $p->buy_on_request = ($Row[16] == 'ДА') ? 1 : 0;
               $p->show_unit_1 = ($Row[17] == 'ДА') ? 1 : 0;
               $p->show_unit_2 = ($Row[18] == 'ДА') ? 1 : 0;
               $p->buy_by_2_units = ($Row[19] == 'ДА') ? 1 : 0;
               $p->oversized = ($Row[20] == 'ДА') ? 1 : 0;

               $p->new_product = ($Row[25] == 'ДА') ? 1 : 0;
               $p->accent_homepage = $Row[26];
               $p->active = ($Row[27] == 'ДА') ? 1 : 0;

               if ($p->save()) {
                   $products[$Row[5]] = $p->id;
                   unset($p);
               } else {
                   d($p->get_errors());
                   d($Row);
                   die('Product insert sux');
               }
           } else {
               $p = (new ProductsModel())->find_by_nomenclature_num((string)$Row[5]);
               $p->_skip_mirror = true;
               $p->brand_id = $has_brand ? $brands[$Row[23]] : null;
               $p->category_id = $category_ids[Inflector::slugalize($Row[0].'-'.$Row[1].'-'.$Row[2])];
               $p->versus_id = $Row[4];
               $p->nomenclature_num = $Row[5];
               $p->catalog_num = $Row[6];
               $p->title = array(
                   'bg-BG' => $Row[21],
                   'en-US' => $Row[22] ? $Row[22] : $Row[21],
               );
               $p->slug = array(
                   'bg-BG' => Inflector::slugalize($Row[21]),
                   'en-US' => $Row[22] ? Inflector::slugalize($Row[22]) : Inflector::slugalize($Row[21]),
               );

               $p->unit_1 = $this->unit_id_match($Row[8]);
               $p->unit_2 = $this->unit_id_match($Row[9]);
               $p->unit_ratio = $Row[10];
//                $p->price_unit_1 = $Row[11];
//                $p->price_unit_2 = $Row[12];
//                $p->new_price_unit_1 = $Row[13];
//                $p->new_price_unit_2 = $Row[14];

               $p->add_to_basket = ($Row[15] == 'ДА') ? 1 : 0;
               $p->buy_on_request = ($Row[16] == 'ДА') ? 1 : 0;
               $p->show_unit_1 = ($Row[17] == 'ДА') ? 1 : 0;
               $p->show_unit_2 = ($Row[18] == 'ДА') ? 1 : 0;
               $p->buy_by_2_units = ($Row[19] == 'ДА') ? 1 : 0;
               $p->oversized = ($Row[20] == 'ДА') ? 1 : 0;

               $p->new_product = ($Row[25] == 'ДА') ? 1 : 0;
               $p->accent_homepage = $Row[26];
               $p->active = ($Row[27] == 'ДА') ? 1 : 0;

               if ($p->save()) {
                   d('saved ' . $p->id);
               }
               else {
                   d('errors');
                   d($p->errors);
               }
           }

           Registry()->db->query("UPDATE products SET products.versus_id = ".$Row[4]." WHERE products.nomenclature_num = '" .  $Row[5]."'");

           if ($products[$Row[5]]) {
               Registry()->db->query('DELETE FROM characteristic_products_values WHERE product_id=' . (int)$products[$Row[5]]);
               foreach ($column_to_id as $column_number => $characteristic_id) {
                   if ($Row[$column_number] && $Row[$column_number] != '0') {
                       Registry()->db->query('INSERT INTO characteristic_products_values(characteristic_id,product_id,value) VALUES(' . $characteristic_id . ',' . $products[$Row[5]] . ', "' . Registry()->db->escape($Row[$column_number]) . '")');
                   }
               }

               Registry()->db->query('INSERT INTO characteristic_products_values(characteristic_id,product_id,value) VALUES(' . $special_characteristic . ',' . $products[$Row[5]] . ', "' . Registry()->db->escape($Row[3]) . '")');
           }
        }

        die('FINISH');
    }

   public function mb_ucfirst($str, $encoding = "UTF-8", $lower_str_end = true)
   {
       $first_letter = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding);
       $str_end = "";
       if ($lower_str_end) {
           $str_end = mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
       } else {
           $str_end = mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
       }
       $str = $first_letter . $str_end;
       return $str;
   }

   public function array_ucfirst($str){
       $ord = array_map('trim', explode(' ', $str));
       foreach ($ord as $key => $val) {
           $replaced[$key] = $this->mb_ucfirst($val);
//            var_dump($replaced[$key]);
       }
       return implode(" ",$replaced);
   }


    public function export()
    {
        Registry()->db = SqlFactory::factory(Config()->DSN);
        Localizer::getInstance('application');
        require(Config()->LIB_PATH.'spreadsheet-writer/Writer.php');


        $units = (new UnitsModel())->findAllForSmarty([], 'id', 'title');

        $brands = (new BrandsModel())->findAllForSmarty([], 'id', 'title');
        $manufacturers = (new ManufacturersModel())->findAllForSmarty([],'id', 'title');

        $brands_manufacturers_relation = (new BrandsModel())->findAllForSmarty([], 'id', 'manufacturer_id');
        $characteristics = (new CharacteristicsModel())->findAllForSmarty(['conditions' => 'id > 1'], 'title', 'id');
        $reversed_characteristics = array_flip($characteristics);
        $arr_chars = $reversed_characteristics;

        $values = [];

        $_all = Registry()->db->query('SELECT * FROM characteristic_products_values');
        foreach($_all as $a) {
            $values[$a->product_id][$a->characteristic_id] = $a->value;
        }

        $en_titles = [];

        $_all = Registry()->db->query('select i18n_foreign_key, title FROM products_i18n WHERE i18n_locale="en-US"');
        foreach($_all as $a) {
            $en_titles[$a->i18n_foreign_key] = $a->title;
        }

        $arr_fields = Array(
            'Основна група',
            'Под-група',
            'Под-под-група',
            'Продуктов Филтър',
            'ID',
            'Номенклатурен номер',
            'Каталожен номер',
            'Производител',
            'Мерна единица 1',
            'Мерна единица 2',
            'Съотношение м.ед.',
            'Стара Цена с ДДС МЕ1',
            'Стара Цена с ДДС МЕ2',
            'Промо Цена с ДДС МЕ1',
            'Промо Цена с ДДС МЕ2',
            'Бутон Добави в Количка',
            'Бутон По запитване',
            'Видима Цена в МЕ1',
            'Видима Цена в МЕ2',
            'Възможност за покупка по две МЕ?',
            'Извънгабаритна Стока',
            'Наименование',
            'English',
            'Марка',
            'Лого',
            'Нов Продукт',
            'Акцент Главна Страница',
            'Активност',
        );
        $arr_head = array_merge($arr_fields, $arr_chars);
        $weight = array_pop($arr_head);
        array_splice($arr_head, 32, 0, $weight);

        $filename = 'products.' . date('Y-m-d H_i_s') . '.xls';
        $workbook = new Spreadsheet_Excel_Writer();
        $workbook->setVersion(8);
        $contents = $workbook->addWorksheet('Logistics');
        $contents->setInputEncoding('UTF-8');

        $workbook->send($filename);

        $format = $workbook->addFormat();

        foreach($arr_head as $key => $column_head){
            $format->setColor('blue');
            $contents->write(0, $key, $column_head,$format);
        }

        $start_characteristics = array_slice($arr_head, 28);

        $sql = 'SELECT * FROM products LEFT JOIN products_i18n ON products.id = products_i18n.i18n_foreign_key AND i18n_locale="bg-BG"';
        $products = Registry()->db->query($sql);

        $ROW=1;

        foreach($products as $p){

            if($p->category_id) {
                $cat = (new CategoriesModel())->find_by_id($p->category_id);
                //  d($p->id);
                $cats = $cat->get_full_title_path();
                $cats_split = explode('/', $cats);
            }

            $key = 0;

            $manufacturer_id = $brands_manufacturers_relation[$p->brand_id];

            $contents->write($ROW,$key++, trim($cats_split[0]));
            $contents->write($ROW,$key++, trim($cats_split[1]));
            $contents->write($ROW,$key++, trim($cats_split[2]));
            $contents->write($ROW,$key++, $values[$p->id][1]);
            $contents->write($ROW,$key++, $p->versus_id);
            $contents->write($ROW,$key++, $p->nomenclature_num);
            $contents->write($ROW,$key++, $p->catalog_num);
            $contents->write($ROW,$key++, $manufacturers[$manufacturer_id]);
            $contents->write($ROW,$key++, $units[$p->unit_1]);
            $contents->write($ROW,$key++, $units[$p->unit_2]);
            $contents->write($ROW,$key++, $p->unit_ratio);
            $contents->write($ROW,$key++, $p->price_unit_1);
            $contents->write($ROW,$key++, $p->price_unit_2);
            $contents->write($ROW,$key++, $p->new_price_unit_1);
            $contents->write($ROW,$key++, $p->new_price_unit_2);
            $contents->write($ROW,$key++, $p->add_to_basket ? 'Да': 'Не');
            $contents->write($ROW,$key++, $p->buy_on_request ? 'Да': 'Не');
            $contents->write($ROW,$key++, $p->show_unit_1 ? 'Да': 'Не');
            $contents->write($ROW,$key++, $p->show_unit_2 ? 'Да': 'Не');
            $contents->write($ROW,$key++, $p->buy_by_2_units ? 'Да': 'Не');
            $contents->write($ROW,$key++, $p->oversized ? 'Да': 'Не');
            $contents->write($ROW,$key++, $p->title);
            $contents->write($ROW,$key++, $en_titles[$p->id]);// ?
            $contents->write($ROW,$key++, $brands[$p->brand_id]);
            $contents->write($ROW,$key++, $p->logo);
            $contents->write($ROW,$key++, $p->new_product ? 'Да': 'Не');
            $contents->write($ROW,$key++, $p->accent_homepage ? 'Да': 'Не');
            $contents->write($ROW,$key++, $p->active ? 'Да': 'Не');

            foreach($start_characteristics as $k=>$v) {
                $char_id = $characteristics[$v];
                $contents->write($ROW, $key+$k, $values[$p->id][$char_id]);
            }

            $ROW++;
            unset($p);
        }
        $workbook->send('setColor.xls');

        $workbook->close();
        exit;


    }


    public function unit_id_match($unit_str){
        $id = null;
        switch ($unit_str){
            case 'БРОЙ':
                $id = 6;
                break;
            case 'ПАК':
                $id = 5;
                break;
            case 'Л.М':
			case 'Л.М.':
                $id = 4;
                break;
            case 'Л':
                $id = 3;
                break;
            case 'КГ':
                $id = 2;
                break;
            case 'КВ.М.':
            case 'КВ.М':
                $id = 1;
                break;
        }

        return $id;
    }

}
?>
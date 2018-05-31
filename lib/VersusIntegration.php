<?php

class VersusIntegration
{

    private $endpoint = 'http://109.104.213.2:8080/GsREST/resources/';
    private $timeout = 100;
    private $debug = false;

    function __construct()
    {
        $p = new ProductsModel;
    }

    function make_request($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $data = curl_exec($ch);
        curl_close($ch);

        return json_decode($data);
    }

    function send_request($url, $data)
    {
        $ch = curl_init($url);
        if($this->debug) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
                'Content-Type:application/json',
                'Content-Length: '. strlen(json_encode($data)),
            )
        );

            $result = curl_exec($ch);

            if($this->debug) {
                if ($result === FALSE) {
                    printf("cUrl error (#%d): %s<br>\n", curl_errno($ch), curl_error($ch));
                }


                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                d($result);
                d($verboseLog);
                d(json_encode($data));
                exit;
            }
            curl_close($ch);

            return $result; // kato e OK da vidim kakvo ni vrushtat
    }

    /***
     *  Execute this script as cronjob every night at 4 a.m.
     */
    function sync_products()
    {
        $export_log = [
          'updated' => 0,
          'new' => [],
          'deactivated' => [], // Nqma go pri tqh - ne ni go podavat
          'no_update' => [], // Podavat cena 0
        ];
        $current_products = [];
        $_all = Registry()->db->query('select versus_id,price_unit_1,price_unit_2,new_price_unit_1,new_price_unit_2,active,show_unit_1,show_unit_2 from products');
        foreach($_all as $a) {
            $current_products[$a->versus_id] = $a->fetch();
            $current_products[$a->versus_id]['is_in_versus'] = 0;
        }

        //d($current_products);exit;
        $cnt = 0;
        if($_GET['a']=='b') {echo 'Start : '.date("H:i:s")."<br />";}
        $url = $this->endpoint.'get_artcenipg';

        $data = $this->make_request($url);


        foreach ($data as $d) {

            if(array_key_exists($d->id_art, $current_products)) {
                $data = [
//                    "unit_1" => $d->art_me1,
//                    "unit_2" => $d->art_me1,
                    "unit_ratio" => $d->art_otn,
                    "price_unit_1" => $d->cena_me1_sdds,
                    "price_unit_2" => $d->cena_me2_sdds,
                    "new_price_unit_1" => $d->promo_cena_me1_sdds,
                    "new_price_unit_2" => $d->promo_cena_me2_sdds,
                    'active' => 1
                ];
                if($d->cena_me1_sdds == 0) {
                    $export_log['no_update'][] = $d->id_art;
                }
                else {
                    $export_log['updated']++;
                    if($d->id_art == 49106){
                        $qq = $data;
                    }
                    Registry()->db->update('products',$data, array('versus_id' => $d->id_art));
                }
                $current_products[$d->id_art]['is_in_versus'] = 1;
            } else {
                // Nqma go produkta
                $export_log['new'][] = $d->id_art;
            }

            $cnt++;
        }

        foreach($current_products as $c) {
            if($c['is_in_versus'] == 0 && $c['active']==1 && $c['versus_id']) {
                $export_log['deactivated'][] = $c['versus_id'];
            }
        }
        if($export_log['deactivated'] && count($export_log['deactivated']) > 0) {
            $chunks = array_chunk($export_log['deactivated'], 500);
            foreach($chunks as $chunk) {
                $q = 'UPDATE products SET active=0 WHERE versus_id IN('.join(',',$chunk).')';
                Registry()->db->query($q);
            }


        }

        if($_GET['a']=='b') {echo 'Products in Versus : '.$cnt."<br />";}
        if($_GET['a']=='b') {echo 'Finish : '.date("H:i:s")."<br />";}

        d($qq);exit;

        $sync = new AdminSync;
        $sync->new = json_encode($export_log['new']);
        $sync->deactivated = json_encode($export_log['deactivated']);
        $sync->no_update = json_encode($export_log['no_update']);
        $sync->updated = $export_log['updated'];
        $sync->save();

    }

    /***
     *  Execute this script as cronjob every night at 4 a.m.
     */
        function send_order($order) {
            $obj = (new OrdersModel)->find($order->id);
            $url = $this->endpoint.'webdoczaq';

            $user_info = json_decode($obj->user_info);
            $company_info = json_decode($obj->company_info);
            $delivery_address = json_decode($obj->delivery_address);
            $delivery_type = $order->delivery_type;
            $payment = json_decode($obj->payment);

            $merged_data = array_merge(['delivery_type' =>$delivery_type], (array)$delivery_address, (array)$company_info, (array)$user_info, (array)$payment);
            $data = new stdClass;


            $data = new stdClass;
            $data->id = $order->id;
            $data->user_first_name = $merged_data['first_name'];
            $data->user_last_name = $merged_data['last_name'];
            $data->user_email = ($merged_data['email']) ? $merged_data['email'] : '';
            $data->uuser_phone = ($merged_data['phone']) ? $merged_data['phone'] : '';
            $data->delivery_type = $order->delivery_type;
            $data->delivery_city = ($merged_data['city']) ? $merged_data['city'] : '';
            $data->delivery_zip_code = ($merged_data['zip_code']) ? $merged_data['zip_code'] : '';
            $data->delivery_address_name = ($merged_data['address_name']) ? $merged_data['address_name'] : '';
            $data->delivery_receiver = ($merged_data['receiver']) ? $merged_data['receiver'] : '';
            $data->delivery_note = ($merged_data['delivery_note']) ? $merged_data['delivery_note'] : '';
            $data->company_name = ($merged_data['company_name']) ? $merged_data['company_name'] : '';
            $data->company_eik = ($merged_data['bulstat']) ? $merged_data['bulstat'] : '';
            $data->company_vat_no = '';
            $data->company_mol = ($merged_data['mol']) ? $merged_data['mol'] : '';
            $data->company_city =  '';
            $data->company_address = ($merged_data['company_address']) ? $merged_data['company_address'] : '';
            $data->payment = $merged_data['payment_type'];
            $data->promocode = ($merged_data['promo_code']) ? true : false;
            $data->promocode_value = ($merged_data['promo_code']) ? $merged_data['promo_code'] : '0';
            $data->clientcard = ($merged_data['gs_card']) ? true : false;
            $data->clientcard_value = ($merged_data['gs_card']) ? $merged_data['gs_card'] : '0';
            $data->created_at = $order->created_at;
            $data->updated_at = $order->updated_at;
            $data->oversized = $obj->oversized;
            $data->delivery_price = $merged_data['delivery_price'];


            $data->webDocZaqR = [];
            foreach($obj->order_products as $key => $order_product){
                $p = new stdClass;
                $p->id = $order_product->id;
                $p->order_id = $order_product->order_id;
                $p->product_id = $order_product->versus_id;
                $p->quantity = $order_product->quantity;
                $p->price = sprintf('%.2f',$order_product->price);
                $p->total_price = sprintf('%.2f',$order_product->total_price);
                $p->original_price = sprintf('%.2f',$order_product->original_price);
                $p->is_promo = $order_product->is_promo;
                $data->webDocZaqR[] = $p;
            }


            $this->send_request($url,$data);
        }

    /***
     *  Products availability for single category
     *
     */
    function get_category_availability($category_id = 0)
    {
        $url = $this->endpoint.'get_artnalpg?id_wg='.$category_id;
        $data = $this->make_request($url);
        if ($data) {
            foreach ($data as $d) {
                Registry()->db->update('products', array('available_quantity' => $d->nalichno),
                    array('versus_id' => $d->id_art));
            }
        }
    }

    /***
     *  Single Product availability (used in basket)
     */
    function get_product_availability($product_id)
    {
        $url = $this->endpoint.'get_oneartnalpg?id_art='.$product_id;
        $data = $this->make_request($url);

        if ($data) {
            Registry()->db->update('products', array('available_quantity' => $data['0']->nalichno),
                array('versus_id' => $data['0']->id_art));
        }
    }

    /***
     *  Get Product availability in stores
     */
    function get_product_stores($product_id = 0)
    {
        $url = $this->endpoint.'get_artnallocationpg?id_art='.$product_id;

        $data = $this->make_request($url);

        $arr = [];

        foreach ($data as $d) {
            if ($d->nalichno == 1) {
                $arr[] = $d->id_location;
            }
        }

        return $arr;
    }

    /***
     *  Get Product availability in stores
     */
    function gs_card($card_number)
    {
        $url = $this->endpoint.'get_klikartapg?nomer='.$card_number;
        $data = current($this->make_request($url));

        return $data ? [
            'number' => $data->nomer,
            'percent' => $data->proc,
            'error' => $data->nomer === 'err',
        ] : ['error' => true];
    }

}

?>

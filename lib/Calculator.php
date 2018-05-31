<?php


class Calculator
{

    private static $PROJECT_TYPES = [
        ['value' => 1, 'title' => 'Flat', 'price' => 18],
        ['value' => 2, 'title' => 'House', 'price' => 22],
        ['value' => 3, 'title' => 'Public', 'price' => 18],
        ['value' => 4, 'title' => 'Office', 'price' => 16]
    ];

    private static $SERVICE_TYPES = [
        ['value' => 1, 'title' => 'Standard', 'price' => 1],
        ['value' => 2, 'title' => 'Online', 'price' => 0],
        ['value' => 3, 'title' => 'Economy', 'price' => -3],
        ['value' => 4, 'title' => 'VIP', 'price' => 15]
    ];

    private static $ARCHITECTURE_TYPES = [
        ['value' => 1, 'title' => 'Round', 'price' => 1],
        ['value' => 2, 'title' => 'Standard', 'price' => 0],
        ['value' => 3, 'title' => 'Bend', 'price' => 1],
        ['value' => 4, 'title' => 'Canted', 'price' => 1]
    ];

    private static $ROOM_CHANGES_TYPES = [
        ['value' => 1, 'title' => 'Without changes', 'price' => 0],
        ['value' => 2, 'title' => 'With changes', 'price' => 1]
    ];

    private static $STYLE_TYPES = [
        ['value' => 1, 'title' => 'Modern', 'price' => 0],
        ['value' => 2, 'title' => 'Classic', 'price' => 4]
    ];

    private static $required_attributes = ['project', 'service', 'architecture', 'room_changes', 'style', 'square_meters'];
    private static $price_pieces = [];
    private static $square_meters = 0;
    private static $final_price = 0;

    public static function getPrice(array $params)
    {
        $required_count = count(static::$required_attributes);
        $count_available = count(array_intersect_key(array_flip(static::$required_attributes), $params));
    
        if ($required_count === $count_available) {
            static::$square_meters = $params['square_meters'];
            static::applyPricesForTypes($params);
            static::applyDiscountForSquareMeters();

            static::$final_price = static::$square_meters * array_sum(static::$price_pieces);
        }

        return static::$final_price;
    }

    private static function applyPricesForTypes(array $params)
    {
        $vars = get_class_vars('Calculator');
        $map = [
            'project' => 'PROJECT_TYPES',
            'service' => 'SERVICE_TYPES',
            'architecture' => 'ARCHITECTURE_TYPES',
            'room_changes' => 'ROOM_CHANGES_TYPES',
            'style' => 'STYLE_TYPES'
        ];

        foreach ($map as $type => $key) {
            if (isset($vars[$key])) {
                $current_value = isset($params[$type]) ? $params[$type] : 0;
                $data = ars($vars[$key])->toSmartySelect('value', 'price');

                if (isset($data[$current_value])) {
                    static::$price_pieces[] = $data[$current_value];
                }
            }
        }
    }

    private static function applyDiscountForSquareMeters()
    {
        if (static::$square_meters > 150) {
            static::$price_pieces[] = -1;
        }
    }

}
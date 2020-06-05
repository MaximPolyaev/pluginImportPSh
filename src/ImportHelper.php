<?php


namespace MaximCode\ImportPalmira;


class ImportHelper
{
    public static function optimize_matching($data, $keys)
    {
        $data = array_map(function ($data_item) use ($keys) {
            $new_data_item = array_combine($keys, $data_item);
            $new_data_item = array_filter($new_data_item, function($key) {
                return $key !== 'no';
            }, ARRAY_FILTER_USE_KEY);

            return $new_data_item;
        }, $data);


        return $data;
    }
}

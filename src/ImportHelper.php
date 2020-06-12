<?php


namespace MaximCode\ImportPalmira;


class ImportHelper
{
    public static function optimize_matching($data, $keys)
    {
        $data = array_map(function ($data_item) use ($keys) {
            $new_data_item = array_combine($keys, $data_item);
            $new_data_item = array_filter($new_data_item, function ($key) {
                return $key !== 'no';
            }, ARRAY_FILTER_USE_KEY);

            return $new_data_item;
        }, $data);


        return $data;
    }

    public static function addUnsavedFile($file_path)
    {
        $json_path = _PS_MODULE_DIR_ . 'importpalmira/json/unsavedfiles.json';
        $json_content = json_decode(file_get_contents($json_path), true);
        $json_content[] = $file_path;
        file_put_contents($json_path, json_encode($json_content));
    }

    public static function cleanUnsavedFiles()
    {
        $json_path = _PS_MODULE_DIR_ . 'importpalmira/json/unsavedfiles.json';
        $json_content = json_decode(file_get_contents($json_path), true);

        foreach ($json_content as $path) {
            \Tools::deleteFile($path);
        }

        file_put_contents($json_path, json_encode([]));
    }
}

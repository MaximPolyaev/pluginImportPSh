<?php
/**
 * 2007-2020 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace MaximCode\ImportPalmira;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\VarDumper\VarDumper;

class XmlFileReader
{
    private $file_path;
    private $fs;
    private $entity_name;
    private $headers = [];
    private $data;
    private $from;
    private $limit;
    private $num_skip_rows;
    private $errors = [];

    private $file_xml;
    private $header_is_unique_parse;

    public function __construct($file_path)
    {
        $this->file_path = $file_path;
        $this->fs = new FileSystem();

        return $this;
    }

    public function init()
    {
        $this->entity_name = 'offer';
        $this->header_is_unique_parse = false;
        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getHeaders()
    {
        if (empty($this->headers) || is_null($this->headers)) {
            $this->readHeaders();

            if (empty($this->headers) || is_null($this->headers)) {
                $this->errors[] = 'Error read headers';
                return false;
            }
        }

        return $this->headers;
    }

    public function getData($from = 0, $limit = 0, $num_skip_rows = 1)
    {
        if ($from < 0 || $limit < 0) {
            return null;
        }

        $this->from = $from;
        $this->limit = $limit;
        $this->num_skip_rows = $num_skip_rows;

        $this->readData();


        if (empty($this->data) || is_null($this->data)) {
            return null;
        }

        return $this->data;
    }

    private function readHeaders()
    {
        $this->file_xml = new \XMLReader();
        $this->file_xml->open($this->file_path);

        while ($this->file_xml->read() && $this->file_xml->name != $this->entity_name) {
            ;
        }

        while ($this->file_xml->name == $this->entity_name) {
            $element = new \SimpleXMLElement($this->file_xml->readOuterXml());

            foreach ($element as $key => $value) {
                if (!empty($value->attributes())) {
                    foreach ($value->attributes() as $valueAttrKey => $valueAttrValue) {
                        if (!in_array($key . "_" . $valueAttrKey . "_" . self::rus_translate($valueAttrValue), $this->headers)) {
                            $this->headers[] = $key . "_" . $valueAttrKey . "_" . self::rus_translate($valueAttrValue);
                        }
                    }
                    continue;
                } else if (!in_array($key, $this->headers)) {
                    $this->headers[] = $key;
                }
            }

            // array_merge(current arr, current->attributes)
            if (!empty($element->attributes())) {
                foreach ($element->attributes() as $attr => $attrval) {
                    if(!in_array($attr, $this->headers)) {
                        $this->headers[] = $attr;
                    }
                }
            }

            if (!$this->header_is_unique_parse) break;

            $this->file_xml->next($this->entity_name);
            unset($element);
        }

        $this->file_xml->close();
    }

    private function readData()
    {
        $this->file_xml = new \XMLReader();
        $this->file_xml->open($this->file_path);

        while ($this->file_xml->read() && $this->file_xml->name != $this->entity_name) {
            ;
        }

        $counter = 0;
        $this->from += (int)$this->num_skip_rows;
        $to = $this->from + $this->limit;

        while ($this->file_xml->name == $this->entity_name) {
            $element = new \SimpleXMLElement($this->file_xml->readOuterXml());

            $is_get_info = false;
            if ($this->from === 0 && $to === 0) {
                $is_get_info = true;
            } else if ($this->from === 0 && $this->limit > 0) {
                if ($counter < $to) {
                    $is_get_info = true;
                } else {
                    break;
                }
            } else if ($this->from > 0 && $this->limit === 0 && $counter >= $this->from) {
                $is_get_info = true;
            } else if ($this->from > 0 && $this->limit > 0 && $counter >= $this->from) {
                if ($counter < $to) {
                    $is_get_info = true;
                } else {
                    break;
                }
            }

            if ($is_get_info) {
                foreach ($element as $key => $value) {
                    if (!empty($value->attributes())) {
                        foreach ($value->attributes() as $valueAttrKey => $valueAttrValue) {
                            $this->data[$counter][$key . "_" . $valueAttrKey . "_" . self::rus_translate($valueAttrValue)] = (string)$value;
                        }
                        continue;
                    }

                    if (isset($this->data[$counter]) && !empty($this->data[$counter]) && key_exists($key, $this->data[$counter])) {
                        $this->data[$counter][$key] = $this->data[$counter][$key] . ',' . trim((string)$value);
                        continue;
                    }
                    $this->data[$counter][$key] = (string)$value;
                }

                // array_merge(current arr, current->attributes)
                if (!empty($element->attributes())) {
                    foreach ($element->attributes() as $attr => $attrval) {
                        $this->data[$counter][$attr] = (string)$attrval;
                    }
                }
            }

            $counter++;
            $this->file_xml->next($this->entity_name);
        }

        $this->file_xml->close();
    }

    private static function rus_translate($str)
    {
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        ];
        return strtr($str, $converter);
    }
}

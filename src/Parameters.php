<?php

namespace src;

use \src\XmlUtils;

class Parameters
{
    protected $parameters;
    protected $filePath;
    protected $extention;

    public function __construct($filePath, $extention = 'xml')
    {
        $this->filePath = $filePath;
        $this->extention = $extention;

        if (Parameters::getFileExtension($filePath) !== $extention || !file_exists($filePath)) {
            echo 'Invalid filename';
            exit();
        }

        $this->parameters = array();
        switch ($extention) {
            case 'xml':
                $xml = XmlUtils::loadFile($filePath);
                // process parameters
                foreach ($xml->documentElement->childNodes as $node) {
                    if (!$node instanceof \DOMElement) {
                        continue;
                    }

                    $parameter = XmlUtils::convertDomElementToArray($node);
                    foreach($parameter as $key=>$val){
                        if(empty($val)){
                            $parameter[$key] = '';
                        }
                    }

                    $this->parameters[] = $parameter;
                }
                break;
        }
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getExtention()
    {
        return $this->extention;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public static function getFileExtension($filename)
    {
        return substr(strrchr($filename, '.'), 1);
    }
} 
<?php namespace FastImageTV;
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 02.06.2016
 * Time: 6:01
 */
include_once (MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
include_once (MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
include_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
include_once (MODX_BASE_PATH . 'assets/lib/Helpers/Assets.php');
include_once (MODX_BASE_PATH . 'assets/tvs/FastImage/core/data.php');

class TV {
    protected $modx = null;
    protected $data = null;
    protected $fs = null;
    protected $DLTemplate = null;
    protected $assets = null;
    protected $tv = array();
    protected $documentData = array();
    public    $tpl = 'assets/tvs/FastImage/tpl/tv.tpl';
    public    $jsListDefault = 'assets/tvs/FastImage/js/scripts.json';
    public    $cssListDefault = 'assets/tvs/FastImage/css/scripts.json';
    public    $customTvName = 'Fast Image Custom TV';

    function __construct($modx, $tv, $documentData){
        $this->modx = $modx;
        $this->tv = $tv;
        $this->documentData = $documentData;
        $this->data = new Data($modx);
        $this->data->loadConfig($this->tv['name']);
        $this->DLTemplate = \DLTemplate::getInstance($modx);
        $this->fs = \Helpers\FS::getInstance();
        $this->assets = \AssetsHelper::getInstance($modx);
        if (!$this->data->checkTable()) $this->data->createTable();
    }

    public function prerender() {
        $output = $this->assets->registerJQuery();
        $tpl = MODX_BASE_PATH.$this->tpl;
        if($this->fs->checkFile($tpl)) {
            $output .= file_get_contents($tpl);
        } else {
            $this->modx->logEvent(0, 3, "Cannot load {$this->tpl} .", $this->customTvName);
            return false;
        }
        return $output;
    }

    public function loadAssets($file,$ph = array()) {
        $output = '';
        $scripts = MODX_BASE_PATH.$file;
        if($this->fs->checkFile($scripts)) {
            $scripts = @file_get_contents($scripts);
            $scripts = $this->DLTemplate->parseChunk('@CODE:'.$scripts,$ph);
            $scripts = json_decode($scripts,true);
            if ($scripts) {
                $output = $this->assets->registerScriptsList($scripts);
            } else {
                $this->modx->logEvent(0, 3, "Cannot load assets from {$file}.", $this->customTvName);
            }
        } else {
            $this->modx->logEvent(0, 3, "Cannot load assets from {$file}.", $this->customTvName);
        }
        return $output;
    }

    public function getTplPlaceholders() {
        $image = $this->data->getThumbnail($this->tv['value']);
        $imageNotExists = empty($this->tv['value']) || !$this->fs->checkFile(MODX_BASE_PATH.$this->tv['value']);
        if (!$imageNotExists) $image = $this->tv['value'];
        $ph = array (
            'js'         => $this->loadAssets($this->jsListDefault),
            'css'        => $this->loadAssets($this->cssListDefault),
            'tv_id'      => $this->tv['id'],
            'tv_value'   => $imageNotExists ? '' : $this->tv['value'],
            'tv_name'    => $this->tv['name'],
            'disabled'   => $imageNotExists ? ' disabled' : '',
            'site_url'   => $this->modx->config['site_url'],
            'documentData' => json_encode($this->documentData),
            'image'      => $imageNotExists ? $this->modx->config['site_url'].'assets/tvs/FastImage/images/noimage.png' : $this->modx->config['site_url'].$image
        );
        return $ph;
    }

    public function render() {
        $output = $this->prerender();
        if ($output !== false) {
            $ph = $this->getTplPlaceholders();
            $output = $this->DLTemplate->parseChunk('@CODE:'.$output,$ph);
        }
        return $output;
    }
}
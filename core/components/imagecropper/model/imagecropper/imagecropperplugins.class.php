<?php

/**
 * ImageCropper
 *
 * Copyright 2019 by Sterc <modx@sterc.nl>
 */

require_once __DIR__ . '/imagecropper.class.php';

class ImageCropperPlugins extends ImageCropper
{
    /**
     * @access public.
     * @param Array $properties.
     */
    public function onManagerPageInit(array $properties = [])
    {
        $this->modx->regClientCSS($this->config['assets_url'] . 'libs/cropperjs/cropper.min.css');
        $this->modx->regClientStartupScript($this->config['assets_url'] . 'libs/cropperjs/cropper.min.js');

        $this->modx->regClientCSS($this->config['css_url'] . 'mgr/imagecropper.css');
        $this->modx->regClientStartupScript($this->config['js_url'] . 'mgr/imagecropper.js');

        $this->modx->regClientStartupHTMLBlock('<script type="text/javascript">
            Ext.onReady(function() {
                ImageCropper.config = '. json_encode($this->config) .';
                
                Ext.applyIf(MODx.lang, ' . json_encode($this->modx->lexicon->loadCache($this->config['namespace'])).');
            });
        </script>');
    }

    /**
     * @access public.
     * @param Array $properties.
     */
    public function onTVInputRenderList(array $properties = [])
    {
        $this->modx->event->output($this->config['elements_path'] . 'tvs/input/');
    }

    /**
     * @access public.
     * @param Array $properties.
     */
    public function onTVInputPropertiesList(array $properties = [])
    {
        $this->modx->event->output($this->config['elements_path'] . 'tvs/options/');
    }

    /**
     * @access public.
     * @param Array $properties.
     */
    public function OnClientSettingsRegisterSettings(array $properties = [])
    {
        $this->modx->controller->addLexiconTopic('imagecropper:clientsettings');

        if (isset($properties['settings'])) {
            $properties['settings']['imagecropper'] = [
                'xtype'         => 'imagecropper-combo-browser',
                'name'          => $this->modx->lexicon('clientsettings.imagecropper.name'),
                'fields'        => [[
                    'xtype'         => 'textfield',
                    'name'          => 'sizes',
                    'title'         => $this->modx->lexicon('clientsettings.imagecropper.label_sizes'),
                    'description'   => $this->modx->lexicon('clientsettings.imagecropper.label_sizes_desc'),
                    'value'         => '{"desktop": {"name": "Desktop", "size": "1000x500"}, "mobile": {"name": "mobile", "size": "768x500"}}'
                ], [
                    'xtype'         => 'modx-combo-boolean',
                    'name'          => 'previews',
                    'title'         => $this->modx->lexicon('clientsettings.imagecropper.label_previews'),
                    'description'   => $this->modx->lexicon('clientsettings.imagecropper.label_previews_desc'),
                    'value'         => 1
                ]]
            ];
        }
    }
}

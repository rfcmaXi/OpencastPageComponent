<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

use ILIAS\DI\Container;
use srag\CustomInputGUIs\OpencastPageComponent\TableGUI\TableGUI;
use srag\DIC\OpencastPageComponent\DICTrait;
use srag\DIC\OpencastPageComponent\Exception\DICException;
use srag\Plugins\OpencastPageComponent\Authorization\TokenRepository;
use srag\Plugins\OpencastPageComponent\Config\Config;
use srag\Plugins\OpencastPageComponent\Utils\OpencastPageComponentTrait;

/**
 * Class ilOpencastPageComponentPluginGUI
 *
 * Generated by srag\PluginGenerator v0.13.8
 *
 * @author            studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author            studer + raimann ag - Team Custom 1 <info@studer-raimann.ch>
 *
 * @ilCtrl_isCalledBy ilOpencastPageComponentPluginGUI: ilPCPluggedGUI
 */
class ilOpencastPageComponentPluginGUI extends ilPageComponentPluginGUI
{

    use DICTrait;
    use OpencastPageComponentTrait;
    const PLUGIN_CLASS_NAME = ilOpencastPageComponentPlugin::class;
    const CMD_CANCEL = "cancel";
    const CMD_CREATE = "create";
    const CMD_CREATE_PLUG = "create_plug";
    const CMD_EDIT = "edit";
    const CMD_INSERT = "insert";
    const CMD_UPDATE = "update";
    const CMD_APPLY_FILTER = "applyFilter";
    const CMD_RESET_FILTER = "resetFilter";
    const CUSTOM_CMD = 'ocpc_cmd';
    const PROP_EVENT_ID = 'event_id';
    const PROP_WIDTH = 'width';
    const PROP_HEIGHT = 'height';
    const PROP_AS_IFRAME = 'as_iframe';
    const POST_SIZE = 'size';
    const MODE_EDIT = 'edit';
    const MODE_PRESENTATION = 'presentation';
    /**
     * @var Container
     */
    protected $dic;


    /**
     * ilOpencastPageComponentPluginGUI constructor
     */
    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        xoctConf::setApiSettings();
        parent::__construct();
    }


    /**
     *
     */
    public function executeCommand()
    {
        try {
            $next_class = $this->dic->ctrl()->getNextClass();
            $cmd = $this->dic->ctrl()->getCmd();

            switch ($next_class) {
                default:
                    if ($cmd == self::CMD_INSERT && $_GET[self::CUSTOM_CMD]) {
                        $cmd = $_GET[self::CUSTOM_CMD];
                        $this->performCommand($cmd);
                        break;
                    } else {
                        $cmd = $this->dic->ctrl()->getCmd();
                        $this->performCommand($cmd);
                        break;
                    }
            }
        } catch (ilException $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            $this->dic->ctrl()->returnToParent($this);
        }
    }


    /**
     * @param string $cmd
     */
    public function performCommand(string $cmd)
    {
        switch ($cmd) {
            case self::CMD_CANCEL:
            case self::CMD_CREATE:
            case self::CMD_EDIT:
            case self::CMD_INSERT:
            case self::CMD_UPDATE:
            case self::CMD_APPLY_FILTER:
            case self::CMD_RESET_FILTER:
                $this->{$cmd}();
                break;
            default:
                break;
        }
    }


    /**
     * @return TableGUI
     * @throws DICException
     */
    protected function getTable() : TableGUI
    {
        $this->dic->ctrl()->clearParameterByClass(self::class, self::CUSTOM_CMD);
        $command_url = $this->dic->ctrl()->getLinkTarget($this, self::CMD_CREATE);
        $this->dic->ctrl()->setParameter($this, self::CUSTOM_CMD, self::CMD_APPLY_FILTER);
        $table = new VideoSearchTableGUI($this, self::CMD_INSERT, $this->dic, $command_url);
        $table->setFilterCommand(self::CMD_INSERT);

        $this->dic->ctrl()->setParameter($this, self::CUSTOM_CMD, self::CMD_RESET_FILTER);
        $reset_filter_url = $this->dic->ctrl()->getLinkTarget($this, self::CMD_INSERT);
        $reset_filter = $this->lng->txt('reset_filter');
        $this->dic->ui()->mainTemplate()->addOnLoadCode('OpencastPageComponent.overwriteResetButton("' . $reset_filter . '", "' . $reset_filter_url . '");');

        $this->dic->ctrl()->clearParameters($this);

        return $table;
    }


    /**
     * @return ilPropertyFormGUI
     */
    protected function getForm() : ilPropertyFormGUI
    {
        $this->dic->ui()->mainTemplate()->addJavaScript($this->getPlugin()->getDirectory() . '/node_modules/ion-rangeslider/js/ion.rangeSlider.min.js');
        $this->dic->ui()->mainTemplate()->addCss($this->getPlugin()->getDirectory() . '/node_modules/ion-rangeslider/css/ion.rangeSlider.min.css');
        $this->dic->ui()->mainTemplate()->addJavaScript($this->getPlugin()->getDirectory() . '/templates/js/form.min.js');
        $this->dic->ui()->mainTemplate()->addOnLoadCode('OpencastPageComponent.initForm();');

        $form = new ilPropertyFormGUI();
        $prop = $this->getProperties();
        $xoctEvent = xoctEvent::find($prop[self::PROP_EVENT_ID]);

        // thumbnail
        $thumbnail = new ilNonEditableValueGUI($this->dic->language()->txt('preview'), '', true);
        $thumbnail->setValue('<img width="' . $prop['width'] . 'px" height="' . $prop['height'] . 'px" id="ocpc_thumbnail" src="' . $xoctEvent->getThumbnailUrl() . '">');
        $form->addItem($thumbnail);

        // width height
        $width_height = new ilWidthHeightInputGUI($this->dic->language()->txt("cont_width") .
            " / " . $this->dic->language()->txt("cont_height"), self::POST_SIZE);
        $width_height->setConstrainProportions(true);
        $width_height->setRequired(true);
        $width_height->setValueByArray([self::POST_SIZE => array_merge($prop, ['constr_prop' => true])]);
        $form->addItem($width_height);

        // slider
        $slider = new ilNonEditableValueGUI('', '', true);
        $slider_tpl = $this->getPlugin()->getTemplate('html/slider_input.html', false, false);
        $slider_tpl->setVariable('CONFIG', json_encode($this->getRangeSliderConfig()));
        $slider->setValue($slider_tpl->get());
        $form->addItem($slider);

        // as iframe
        $as_iframe = new ilCheckboxInputGUI($this->getPlugin()->txt(self::PROP_AS_IFRAME), self::PROP_AS_IFRAME);
        $as_iframe->setInfo($this->getPlugin()->txt(self::PROP_AS_IFRAME . '_info'));
        $as_iframe->setChecked($prop[self::PROP_AS_IFRAME]);
        $form->addItem($as_iframe);

        $form->addCommandButton(self::CMD_UPDATE, $this->dic->language()->txt("save"));
        $form->addCommandButton(self::CMD_CANCEL, $this->dic->language()->txt("cancel"));
        $form->setTitle($this->getPlugin()->txt("form_title"));

        $form->setFormAction($this->dic->ctrl()->getFormAction($this));

        return $form;
    }


    /**
     *
     */
    public function insert()
    {
        $table = $this->getTable();
        self::output()->output($table->getHTML());

        return;
    }


    /**
     * @throws DICException
     */
    protected function applyFilter()
    {
        $table = $this->getTable();
        $table->setFilterCommand(self::CMD_INSERT);
        $table->resetOffset();
        $table->writeFilterToSession();
        $this->redirect(self::CMD_INSERT);
    }


    /**
     *
     */
    public function resetFilter()
    {
        $table = $this->getTable();
        $table->resetOffset();
        $table->resetFilter();
        $this->redirect(self::CMD_INSERT);
    }


    /**
     *
     */
    public function create()
    {
        $event_id = filter_input(INPUT_GET, VideoSearchTableGUI::GET_PARAM_EVENT_ID, FILTER_SANITIZE_STRING);
        $properties = [
            self::PROP_EVENT_ID  => $event_id,
            self::PROP_HEIGHT    => Config::getField(Config::KEY_DEFAULT_HEIGHT),
            self::PROP_WIDTH     => Config::getField(Config::KEY_DEFAULT_WIDTH),
            self::PROP_AS_IFRAME => (bool) Config::getField(Config::KEY_DEFAULT_AS_IFRAME)
        ];
        $this->createElement($properties);

        $this->edit();
    }


    /**
     *
     */
    public function edit()
    {
        $form = $this->getForm();

        self::output()->output($form);
    }


    /**
     *
     */
    public function update()
    {
        $form = $this->getForm();

        $form->setValuesByPost();

        if (!$form->checkInput()) {
            self::output()->output($form);

            return;
        }

        $properties = $this->getProperties();

        $size = $form->getInput(self::POST_SIZE);
        $properties[self::PROP_HEIGHT] = $size[self::PROP_HEIGHT];
        $properties[self::PROP_WIDTH] = $size[self::PROP_WIDTH];
        $properties[self::PROP_AS_IFRAME] = $form->getInput(self::PROP_AS_IFRAME);

        $this->updateElement($properties);

        $this->returnToParent();
    }


    /**
     *
     */
    public function cancel()
    {
        $this->returnToParent();
    }


    /**
     * @param string $a_mode
     * @param array  $a_properties
     * @param string $plugin_version
     *
     * @return string
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function getElementHTML($a_mode, array $a_properties, $plugin_version) : string
    {
        try {
            $xoctEvent = xoctEvent::find($a_properties[self::PROP_EVENT_ID]);
        } catch (Exception $e) {
            return $this->getExceptionHTML($a_properties);
        }
        $as_iframe = (bool) $a_properties[self::PROP_AS_IFRAME];
        if ($as_iframe && ($a_mode == self::MODE_PRESENTATION)) {
            return $this->getIframeHTML($a_properties, $xoctEvent);
        } else {
            return $this->getStandardElementHTML($a_mode, $a_properties, $xoctEvent);
        }
    }


    /**
     * @param $cmd
     */
    public function redirect($cmd)
    {
        $this->dic->ctrl()->setParameter($this, self::CUSTOM_CMD, $cmd);
        $this->dic->ctrl()->redirect($this, self::CMD_INSERT);
    }


    /**
     * @param array     $properties
     *
     * @param xoctEvent $xoctEvent
     *
     * @return string
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function getIframeHTML(array $properties, xoctEvent $xoctEvent) : string
    {
        $tpl = $this->getPlugin()->getTemplate('html/component_as_iframe.html');
        $tpl->setVariable('SRC', $this->getPlayerLink($xoctEvent));
        $tpl->setVariable('WIDTH', $properties[self::PROP_WIDTH]);
        $tpl->setVariable('HEIGHT', $properties[self::PROP_HEIGHT]);

        return $tpl->get();
    }


    /**
     * @param string    $mode
     * @param array     $properties
     *
     * @param xoctEvent $xoctEvent
     *
     * @return string
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function getStandardElementHTML(string $mode, array $properties, xoctEvent $xoctEvent) : string
    {
        $renderer = new xoctEventRenderer($xoctEvent);
        $use_modal = (xoctConf::getConfig(xoctConf::F_USE_MODALS));
        $tpl = $this->getPlugin()->getTemplate('html/component_as_link.html');
        $tpl->setVariable('HEIGHT', $properties[self::PROP_HEIGHT]);
        $tpl->setVariable('WIDTH', $properties[self::PROP_WIDTH]);
        $tpl->setVariable('THUMBNAIL_URL', $xoctEvent->getThumbnailUrl());
        if ($mode == self::MODE_PRESENTATION) {
            $tpl->setVariable('TARGET', '_blank');
            $tpl->setVariable('VIDEO_LINK', $use_modal ? '#' : $this->getPlayerLink($xoctEvent));
            $tpl->touchBlock('overlay');
            $this->dic->ui()->mainTemplate()->addCss($this->getPlugin()->getDirectory() . '/templates/css/presentation.css');
            if ($use_modal) {
                $tpl->setVariable('MODAL', $renderer->getPlayerModal()->getHTML());
                $tpl->setVariable('MODAL_LINK', $renderer->getModalLink());
            }
        } else {
            $tpl->setVariable('VIDEO_LINK', '#');
        }

        return $tpl->get();
    }


    /**
     * @param array $properties
     *
     * @return string
     */
    protected function getExceptionHTML(array $properties) : string
    {
        return '<img src="Services/WebAccessChecker/templates/images/access_denied.png" ' .
            'height="' . $properties[self::PROP_HEIGHT] . 'px" ' .
            'width="' . $properties[self::PROP_WIDTH] . 'px">';
    }

    /**
     * @return array
     */
    protected function getRangeSliderConfig() : array
    {
        return [
            'skin' => 'modern',
            'min' => 0,
            'max' => 100,
            'from' => 50,
            'from_min' => 10,
            'step' => 1,
            'grid' => true,
            'postfix' => '%',
        ];
    }


    /**
     * @param xoctEvent $xoctEvent
     *
     * @return string
     * @throws xoctException
     */
    protected function getPlayerLink(xoctEvent $xoctEvent) : string
    {
        if (xoctConf::getConfig(xoctConf::F_INTERNAL_VIDEO_PLAYER) || $xoctEvent->isLiveEvent()) {
            $token = (new TokenRepository())->create(self::dic()->user()->getId(), $xoctEvent->getIdentifier());
            self::dic()->ctrl()->clearParametersByClass(xoctPlayerGUI::class);
            self::dic()->ctrl()->setParameterByClass(ocpcRouterGUI::class, ocpcRouterGUI::TOKEN, $token->getToken());
            self::dic()->ctrl()->setParameterByClass(xoctPlayerGUI::class, xoctPlayerGUI::IDENTIFIER, $xoctEvent->getIdentifier());
            return self::dic()->ctrl()->getLinkTargetByClass([ilObjPluginDispatchGUI::class, ocpcRouterGUI::class, xoctPlayerGUI::class], xoctPlayerGUI::CMD_STREAM_VIDEO);
        }
        if (!isset($this->player_url)) {
            $url = $xoctEvent->getFirstPublicationMetadataForUsage(xoctPublicationUsage::find(xoctPublicationUsage::USAGE_PLAYER))->getUrl();
            if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS)) {
                $this->player_url = xoctSecureLink::sign($url);
            } else {
                $this->player_url = $url;
            }
        }

        return $this->player_url;
    }

    /**
     * @return ilOpencastPageComponentPlugin
     */
    public function getPlugin()
    {
        return parent::getPlugin();
    }
}

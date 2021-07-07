<?php
/**
 * 2019-2021 Team Ever
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
 *  @author    Team Ever <https://www.team-ever.com/>
 *  @copyright 2019-2021 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Everpsimgimport extends Module
{
    private $html;
    private $postErrors = array();
    private $postSuccess = array();

    public function __construct()
    {
        $this->name = 'everpsimgimport';
        $this->tab = 'administration';
        $this->version = '1.0.2';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Ever PS products image Import');
        $this->description = $this->l('Import images to products based on EAN13 or product reference');
        $this->isSeven = Tools::version_compare(_PS_VERSION_, '1.7', '>=') ? true : false;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('EVERPSIMGIMPORT_EAN13', false);
        Configuration::updateValue('EVERPSIMGIMPORT_PREFIX', '');
        Configuration::updateValue('EVERPSIMGIMPORT_PRODUCT_PREFIX', '');
        Configuration::updateValue('EVERPSIMGIMPORT_FOLDER', '');

        return parent::install();
    }

    public function uninstall()
    {
        Configuration::deleteByName('EVERPSIMGIMPORT_EAN13');
        Configuration::deleteByName('EVERPSIMGIMPORT_PREFIX');
        Configuration::deleteByName('EVERPSIMGIMPORT_PRODUCT_PREFIX');
        Configuration::deleteByName('EVERPSIMGIMPORT_FOLDER');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitEverpspdfimportModule')) == true) {
            $this->postValidation();

            if (!count($this->postErrors)) {
                $this->postProcess();
            }
        }
        if (((bool)Tools::isSubmit('submitImportPdf')) == true) {
            $this->postValidation();

            if (!count($this->postErrors)) {
                $files_dir = glob(
                    _PS_ROOT_DIR_
                    .'/'
                    .Configuration::get('EVERPSIMGIMPORT_FOLDER')
                    .'/*'
                );
                foreach ($files_dir as $file) {
                    if (is_file($file)
                        && !strpos(basename($file), 'index')
                    ) {
                        // && pathinfo($file, PATHINFO_EXTENSION) == 'jpg'
                        // $fileExist = false;
                        // $fileExist = Db::getInstance()->getValue('SELECT file
                        //     FROM `'._DB_PREFIX_.'image`
                        //     WHERE file_name = "'.basename($file).'"');
                        // if ($fileExist) {
                        //     continue;
                        // }
                        $this->addImageImport(
                            $file,
                            basename($file)
                        );
                    }
                }
            }
        }        

        // Display errors
        if (count($this->postErrors)) {
            foreach ($this->postErrors as $error) {
                $this->html .= $this->displayError($error);
            }
        }

        // Display confirmations
        if (count($this->postSuccess)) {
            foreach ($this->postSuccess as $success) {
                $this->html .= $this->displayConfirmation($success);
            }
        }

        $this->context->smarty->assign(array(
            'everpsimgimport_dir' => $this->_path
        ));

        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEverpspdfimportModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'buttons' => array(
                    'importPdf' => array(
                        'name' => 'submitImportPdf',
                        'type' => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon' => 'process-icon-refresh',
                        'title' => $this->l('Import product images')
                    ),
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Files are ean13 named'),
                        'desc' => $this->l('Set yes for ean13.pdf named files'),
                        'hint' => $this->l('Else product reference will be used'),
                        'name' => 'EVERPSIMGIMPORT_EAN13',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Enter images folder name'),
                        'desc' => $this->l('Place all your images on this folder'),
                        'hint' => $this->l('If folder doesn\'t exist, import won\'t work'),
                        'name' => 'EVERPSIMGIMPORT_FOLDER',
                        'required' => true,
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Enter images file prefix'),
                        'desc' => $this->l('You can set a specific prefix to pdf names'),
                        'hint' => $this->l('Leave empty for not use'),
                        'name' => 'EVERPSIMGIMPORT_PREFIX',
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Enter product reference prefix'),
                        'desc' => $this->l('You can set a specific prefix to product references'),
                        'hint' => $this->l('Leave empty for not use'),
                        'name' => 'EVERPSIMGIMPORT_PRODUCT_PREFIX',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EVERPSIMGIMPORT_EAN13' => Configuration::get(
                'EVERPSIMGIMPORT_EAN13'
            ),
            'EVERPSIMGIMPORT_PREFIX' => Configuration::get(
                'EVERPSIMGIMPORT_PREFIX'
            ),
            'EVERPSIMGIMPORT_PRODUCT_PREFIX' => Configuration::get(
                'EVERPSIMGIMPORT_PRODUCT_PREFIX'
            ),
            'EVERPSIMGIMPORT_FOLDER' => Configuration::get(
                'EVERPSIMGIMPORT_FOLDER'
            ),
        );
    }

    private function postValidation()
    {
        if (Tools::isSubmit('submitEverpspdfimportModule')) {
            if (Tools::getValue('EVERPSIMGIMPORT_EAN13')
                && !Validate::isBool(Tools::getValue('EVERPSIMGIMPORT_EAN13'))
            ) {
                $this->postErrors[] = $this->l('Error: ean13 or reference is not valid');
            }

            if (!Tools::getValue('EVERPSIMGIMPORT_FOLDER')
                || !Validate::isDirName(Tools::getValue('EVERPSIMGIMPORT_FOLDER'))
            ) {
                $this->postErrors[] = $this->l('Error: folder name is not valid');
            }

            if (Tools::getValue('EVERPSIMGIMPORT_FOLDER')
                && Validate::isDirName(Tools::getValue('EVERPSIMGIMPORT_FOLDER'))
            ) {
                $img_folder = Tools::getValue('EVERPSIMGIMPORT_FOLDER');
                $valid = false;
                foreach(glob(_PS_ROOT_DIR_.'/*', GLOB_ONLYDIR) as $dir) {
                    $dirname = basename($dir);
                    if ($dirname == $img_folder) {
                        $valid = true;
                    }
                }
                if (!$valid) {
                    $this->postErrors[] = $this->l('Error: folder does not exists');
                }
            }

            if (Tools::getValue('EVERPSIMGIMPORT_PREFIX')
                && !Validate::isString(Tools::getValue('EVERPSIMGIMPORT_PREFIX'))
            ) {
                $this->postErrors[] = $this->l('Error: prefix is not valid');
            }

            if (Tools::getValue('EVERPSIMGIMPORT_PRODUCT_PREFIX')
                && !Validate::isString(Tools::getValue('EVERPSIMGIMPORT_PRODUCT_PREFIX'))
            ) {
                $this->postErrors[] = $this->l('Error: product prefix is not valid');
            }
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Import and attach  files to product
     * @param string filename, string name, string description
     */
    public function addImageImport($filename, $description)
    {
        if (Configuration::get('EVERPSIMGIMPORT_EAN13')) {
            $ref = 'ean13';
        } else {
            $ref = 'reference';
        }

        // Save product reference with prefix or not
        if (Configuration::get('EVERPSIMGIMPORT_PRODUCT_PREFIX')) {
            $based_data = Configuration::get('EVERPSIMGIMPORT_PRODUCT_PREFIX')
            .pathinfo($filename, PATHINFO_FILENAME);
        } else {
            $based_data = pathinfo($filename, PATHINFO_FILENAME);
        }

        // Save base_data using prefix or not
        if (Configuration::get('EVERPSIMGIMPORT_PREFIX')) {
            $based_data = str_replace(
                Configuration::get('EVERPSIMGIMPORT_PREFIX'),
                '',
                $based_data
            );
        }

        // Remove trailing slash
        $img_folder = rtrim(
            Configuration::get('EVERPSIMGIMPORT_FOLDER'),
            '/'
        );
        $folder_url = Tools::getHttpHost(true)
        .__PS_BASE_URI__
        .$img_folder;

        foreach(glob(_PS_ROOT_DIR_.'/*', GLOB_ONLYDIR) as $dir) {
            $dirname = basename($dir);
            if ($dirname == $img_folder) {
                $valid = true;
            }
        }

        if (!$valid) {
            die('folder not found. Please add folder '.$img_folder.' to root folder');
        }

        if (Configuration::get('EVERPSIMGIMPORT_EAN13')) {
            $id_product = Product::getIdByEan13($based_data);
        } else {
            $id_product = Db::getInstance()->getValue('SELECT id_product
                FROM `'._DB_PREFIX_.'product`
                WHERE reference = "'.pSQL($based_data).'"');
        }

        if (!(int)$id_product || empty($id_product)) {
            return;
        }

        // Create image
        $image = new Image();
        $image->id_product = (int)$id_product;
        $image->cover =  true;
        $image->position = Image::getHighestPosition($id_product) + 1;
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            $image->legend[$language['id_lang']] = strval(basename($based_data));
        }

        $image->add();

        if (!$this->copyImgImport($id_product, $image->id, $filename, 'products', !Tools::getValue('regenerate'))) {
            $image->delete();
        }
    }

    private function copyImgImport($id_entity, $id_image, $url, $entity = 'products', $regenerate = true) {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));

        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image(
                    (int)$id_image
                );
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int)$id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int)$id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int)$id_entity;
                break;
        }
        $url = str_replace(' ', '%20', trim($url));


        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (!ImageManager::checkImageMemoryLimit($url))
            return false;


        // 'file_exists' doesn't work on distant file, and getimagesize makes the import slower.
        // Just hide the warning, the processing will be the same.
        if (Tools::copy($url, $tmpfile)) {
            ImageManager::resize($tmpfile, $path . '.jpg');
            $images_types = ImageType::getImagesTypes($entity);

            if ($regenerate) {
                foreach ($images_types as $image_type) {
                    ImageManager::resize($tmpfile, $path . '-' . stripslashes($image_type['name']) . '.jpg', $image_type['width'], $image_type['height']);
                    if (in_array($image_type['id_image_type'], $watermark_types))
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                }
            }
            unlink($url);
        }
        else {
            unlink($tmpfile);
            return false;
        }
        unlink($tmpfile);
        return true;
    }
}

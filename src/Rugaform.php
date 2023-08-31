<?php

declare(strict_types=1);

namespace Ruga\Rugaform;

use Laminas\Form\Element\Hidden;
use Laminas\Form\Form;
use Laminas\Form\FormInterface;

class Rugaform implements ConfigurationInterface
{
    const UNIQUEID = 'rugaform_uniqueid';
    const SUBMIT_REASON = 'rugaform_submit_reason';
    const FAVOURITE = 'rugaform_favourite';
    const SUCCESS_URI = 'rugaform_successuri';
    const RESULT = 'rugaform_result';
    const ERROR = 'rugaform_error';
    const ERROR_EXCEPTION = 'rugaform_error_exception';
    const ERROR_TRACE = 'rugaform_error_trace';
    const QUERY = 'rugaform_query';
    const DATA = 'rugaform_data';
    
    use ConfigurationTrait;
    
    /** @var string Id of the html element. */
    private $id;
    
    /** @var string Database uniqueid */
    private $uniqueid;
    
    /** @var FormInterface */
    private FormInterface $form;
    
    
    
    public function __construct(array $config = [], ?FormInterface $form = null, ?string $uniqueid = null)
    {
        // Set rowId to "uniqueid" by default
        $config['rowId'] = $config['rowId'] ?? 'uniqueid';
        
        $this->setConfig($config);
        
        $this->id = $config['id'] ?? null;
        
        $this->uniqueid = $uniqueid ?? $this->getConfig('uniqueid') ?? null;
        
        
        if ($form === null) {
            $form = new Form($this->getId());
        }
        $this->updateForm($form);
        $this->form = $form;
    }
    
    
    
    public function updateForm(FormInterface $form)
    {
        $form->setAttribute('action', $this->getConfig('ajax.url'));
        $form->setAttribute('method', \Fig\Http\Message\RequestMethodInterface::METHOD_POST);
        $form->setAttribute('name', $this->getId());
        $form->setAttribute('id', $this->getId());
        
        if ($form->hasAttribute('rugaform_uniqueid')) {
            $form->setAttribute('rugaform_uniqueid', $this->uniqueid);
        } else {
            $form->add([
                           'type' => Hidden::class,
                           'name' => 'rugaform_uniqueid',
                           'value' => $this->uniqueid,
                       ]
            );
        }
        
        foreach ($form->getElements() as $element) {
            $element->setAttribute('class', 'form-control');
            $element->setAttribute('placeholder', $element->getLabel());
        }
    }
    
    
    
    /**
     * Return id for the html element.
     * If no id is given, create random id.
     *
     * @return string
     */
    public function getId($suffix = '')
    {
        if (!$this->id) {
            $this->id = 'rugalib_rugaform_' . preg_replace(
                    '#[^A-Za-z0-9\-_]+#',
                    '',
                    md5('rugalib_rugaform_' . $this->getConfig('ajax.url', '') . date('c'))
                );
        }
        return $this->id . ($suffix ? '-' . $suffix : '');
    }
    
    
    
    public function renderHtml()
    {
        $str = '<div id="' . $this->getId('debug') . '" ';
        $str .= '>';
        
        $bugicon = file_get_contents(__DIR__ . '/../public/bug-outline.svg');
        $debug = <<<HTML
<button type="button" class="btn btn-xs btn-default" style="padding: 5px; border: 2px; width: 2.2em" disabled="disabled" data-bs-toggle="modal" data-bs-target="#{$this->getId(
            'debug-modal'
        )}">
{$bugicon}
</button>
<div class="modal fade" id="{$this->getId('debug-modal')}" tabindex="-1" aria-labelledby="{$this->getId(
            'debug-modal-label'
        )}" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{$this->getId('debug-modal-label')}">Debug information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="pre-scrollable text-small" id="{$this->getId('debug-modal-body')}"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
HTML;
        
        return $str . ($this->getConfig('debug', true) ? $debug : '');
    }
    
    
    
    public function renderJavascript()
    {
        $customSqlData = json_encode($this->getConfig('customSqlData', null));
        
        $str = /** @lang JavaScript */
            <<<"JS"
(function($, window, document) {
    const customSqlData=''+{$customSqlData};
    
    $(function() {
        const rugaform_{$this->getId()} = $('#{$this->getId()}').rugaform({
                debug: {$this->getConfigAsJsBoolean('debug', false)},
                requestedit: {$this->getConfigAsJsBoolean('requestedit', true)},
                alwayseditable: {$this->getConfigAsJsBoolean('alwayseditable', false)},
                btn_startedit: '{$this->getConfig('btn_startedit', 'button[name=switchtoeditmode]')}',
                btn_delete: '{$this->getConfig('btn_delete', 'button[name=delete]')}',
                btn_favourite: '{$this->getConfig('btn_favourite', '[data-widget=favourite]')}',
                row: {$this->getConfig('row', '{}')},
                uniqueid: '{$this->uniqueid}',
                validation_options: {
                    ignore: ':hidden, [type=date]'
                }
            });
    });
}(window.jQuery, window, document));
JS;
        return $str;
    }
    
    
    
    /**
     * Returns the datatable as string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->renderHtml() . $this->renderJavascript();
    }
    
    
    
    public function openTag(): string
    {
        $helper = new \Laminas\Form\View\Helper\Form();
        return $helper()->openTag($this->form);
    }
    
    
    
    public function closeTag(): string
    {
        return '</form>';
    }
    
}
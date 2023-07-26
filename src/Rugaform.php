<?php

declare(strict_types=1);

namespace Ruga\Rugaform;

use Laminas\Form\Element\Hidden;
use Laminas\Form\FormInterface;
use Laminas\Form\View\Helper\FormHidden;
use Ruga\Rugaform\ConfigurationTrait;

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
    
    
    
    public function __construct(FormInterface $form, string $uniqueid, array $config = [])
    {
        // Set rowId to "uniqueid" by default
        $config['rowId'] = $config['rowId'] ?? 'uniqueid';
        
        $this->setConfig($config);
        
        $this->id = $config['id'] ?? null;
        
        $this->uniqueid = $uniqueid;
        
        $this->updateForm($form);
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
            <<<JS
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
                uniqueid: '{$this->uniqueid}',
                validation_options: {
                    ignore: ":hidden, [type=date]"
                }
            });

        
        
        
        
        var form_{$this->getId()}=$("#{$this->getId()}")
            .on('preXhr.dt', function (e, settings, data) {
                // Disable debug button
                $('button[data-bs-target="#{$this->getId('debug-modal')}"]').removeClass(['text-danger', 'text-success']);
                $('button[data-bs-target="#{$this->getId('debug-modal')}"]').prop('disabled', true);
                
                data.customSqlData=customSqlData;

                if( filterFormSelector !== '' ) {
                    // Convert form data to Object
                    // https://stackoverflow.com/questions/41431322/how-to-convert-formdatahtml5-object-to-json
                    const formData = Array.from(new FormData($(filterFormSelector)[0]));
                    const obj = formData.reduce((o, [k, v]) => {
                        let a = v, b, i, m = k.split('['), n = m[0], l = m.length;
                        if (l > 1) {
                            a = b = o[n] || [];
                            for (i = 1; i < l; i++) {
                                m[i] = (m[i].split(']')[0] || b.length) * 1;
                                b = b[m[i]] = ((i + 1) == l) ? v : b[m[i]] || [];
                            }
                        }
                        return { ...o, [n]: a };
                        }, {});
                    
                    // Disable filter form
                    $(':input', filterFormSelector).prop('disabled', true);
    
                    data.filter=obj;
    
                    if(data.draw === 1) {
                        data.filter=initialFilter;
                    }
                }
            })
            .on('xhr.dt', function(e, settings, data, xhr) {
                if((typeof data !== "object") || (data === null)) {
                    data={
                        query: '',
                        error: xhr.status + ' ' + xhr.statusText,
                        errorBody: xhr.responseText
                    };
                }
                
                // Store debug information to the modal
                $('#{$this->getId('debug')}').html(
                    (data.error ? ('<div class="alert alert-danger" role="alert">' + data.error + '</div>') : '')
                    +
                    (data.errorBody ? ('<pre class="small text-wrap"><code>' + data.errorBody + '</code></pre>') : '')
                    +
                    (data.query ? ('<pre class="small text-wrap"><code>' + data.query + '</code></pre>') : '')
                    );
                // Enable debug button
                $('button[data-bs-target="#{$this->getId('debug-modal')}"]').prop('disabled', false);
                
                if(data.error) {
                    $('button[data-bs-target="#{$this->getId('debug-modal')}"]').addClass('text-danger');
                } else if(data.query) {
                    $('button[data-bs-target="#{$this->getId('debug-modal')}"]').addClass('text-success');
                }
                
                if( filterFormSelector !== '' ) {
                    // Enable filter form
                    $(':input', filterFormSelector).prop('disabled', false);
                    $(filterFormSelector).trigger('reset');
                    
                    // Populate form from filter object
                    populate($(filterFormSelector)[0], data.filter);
                }
            })
            

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
}
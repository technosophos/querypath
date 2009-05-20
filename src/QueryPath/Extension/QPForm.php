<?php
/**
 * QPForm is an HTML forms library for QueryPath.
 *
 *
 * @package QueryPath
 * @subpackage Extension
 * @author M Butcher <matt@aleph-null.tv>
 * @license http://opensource.org/licenses/lgpl-2.1.php LGPL or MIT-like license.
 * @see QueryPathExtension
 * @see QueryPathExtensionRegistry::extend()
 * @since 1.3
 */

class QPForm implements QueryPathExtension {
  
  protected $q = NULL;
  
  public function __construct(QueryPath $q) {
    $this->q = $q;
  }
  
  /**
   * Create a form and then select that form.
   *
   * This function creates a new form and then sets that form as the currently
   * wrapped element. This way, you can do things such as:
   *
   * <code>
   *  qp(QueryPath::HTML_STUB, 'body')->form()->textfield('test')->writeHTML();
   * </code>
   *
   * The above creates a new form and adds a textfield (named 'test') to that form.
   *
   * @param string $action
   *  The form action.
   * @param string $id
   *  The optional ID for this element. One will automatically be created if this is
   *  not set.
   * @param array $options
   *  An associative array of configuration options. The main option in this and in 
   *  other <code>$options</code> values is the $option['attributes'] option. This 
   *  option is assumed to contain HTML attributes. Here, for example, you can 
   *  set the HTTP methos that the form will use by doing this:
   *  <code> 
   *  $options['attributes']['method'] = 'POST';
   *  </code>
   *  This will result in a form element that contains <code>method="POST"</code>.
   *
   *  Supported options are:
   *  - attributes: an associative array of attributes.
   */
  public function form($action = '#', $id = NULL, $options = array()) {
    if (empty($id)) {
      // Simple ID that is not likely to collide:
      $id = time() . '-' . rand(0, 999);
    }
    $txt = '<form action="' . $action . '" method="' . $method . '" id="' . $id . '"/>';
    $this->q->append($txt)->find($id);
    if (!empty($options['attributes'])) {
      $this->q->attr($options['attributes']);
    }
    
    return $this->q;
  }
  
  /**
   * Create a new fieldset form item.
   *
   * This will automatically select the fieldset as the active item. So 
   * a call to $form->fieldset('Test')->textfield('Foo') will place the 
   * textfield inside of the fieldset.
   *
   * @param string $legend
   *  The legend (title, description) for this fieldset.
   * @param array $options
   *  Currently supported options:
   *  - 'attributes': Associative array of HTML attributes.
   * @return QueryPath
   *  The QueryPath object with the fieldset selected.
   */
  public function fieldset($legend = '', $options = array()) {
    $this->q->append('<fieldset></fieldset>');
    $this->q->children('fieldset:last');
    if (is_array($options['attributes'])) {
      foreach ($options['attributes'] as $name=>$val) {
        $this->q->attr($name, $val);
      }
    }
    if (!empty($legend)) {
      $this->q->append('<legend>' . $legend . '</legend>');
    }
    return $this->q;
  }
  
  /**
   * Generic form element.
   *
   *
   * @param string $type
   *  The input type for this element (e.g. text, radio)
   * @param $name
   *  The name of the element. This will become the value of the name attribute.
   * @param $label
   *  The label of the element.
   * @param array $options
   *  Currently supported options:
   *  - 'attributes': Associative array of HTML attributes.
   *  - 'default': String default value.
   */
  public function input($type, $name = '', $label = '', $options = array()) {
    $input = '';
    if (!empty($label)) {
      $input .= '<label for="' . $name . '">' . $label . '</label>';
    }

    $input .= '<input type="' . $type . '" name="' . $name . '" ';
    
    if (!empty($options['default'])) {
      $input .= 'value="' . $options['default'] . '" ';
    }
    
    $format = '%s="%v"';
    if (is_array($options['attributes'])) {
      $attrs = array();
      foreach ($options['attributes'] as $k => $v) {
        $attrs[] = sprintf($format, $k, $v);
      }
      $input .= implode(' ', $attrs);
    }
    $this->q->append($input);
    return $this->q;
  }
  
  /**
   * Create a text field.
   *
   * @param string $name 
   *  The name of the field.
   * @param string $label
   *  The label for the field.
   * @param array $options
   *  Array of options. See {@link input()} for a list of supported options.
   * @return QueryPath
   * The QueryPath Object.
   */
  public function textfield($name, $label = '', $options = array()) {
    return $this->input('text', $label, $options)
  }
  
  /**
   * Create a password field.
   *
   * @param string $name
   *  The name of the field.
   * @param string $label
   *  The label on the field
   * @param array $options
   *  The array of options. See {@link input()}.
   * @return QueryPath
   *  A QueryPath object.
   */
  public function password($name, $label = '', $options = array()) {
    return $this->input('password', $label, $options)
  }
  
  /**
   * Create a text area.
   *
   * @param string $name 
   *  Field name
   * @param string $label
   *  The label for this textarea
   * @param array $options
   *  Currently supported options:
   *  - attributes: Associative array of attributes. Use this to set cols and rows.
   */
  public function textarea($name, $label = '', $options) {
    $out = qp('<?xml version="1.0"?><textarea name="' . $name . '"></textarea>');
    if (!empty($options['attributes'])) {
      $out->attr($options['attributes']); // Set array all at once.
    }
    
    if (!empty($label)) {
      $txt = '<label for="' . $name . '">' . $label . '</label>' . $out;
      $this->q->append($txt);
    }
    
    $this->q->append($out)
    return $this->q;
  }
  
  public function hidden($name, $value) {
    $this->q->append(sprintf('<input type="hidden" name="%s" value="%s"/>', $name, $value));
    return $this->q;
  }
  
  public function storedValue() {
    
  }
  
  /**
   * A generic wrapper for handling radios and checkboxes.
   */
  protected function multibutton($type, $name, $buttons, $label = '', $options = array()) {
    if (!empty($label)) {
      $out->append('<label for="' . $name . '">' . $label . '</label>');
    }
    // This should be an array of names
    $checked = $options['checked'];
    
    foreach ($buttons as $k => $v) {
      $copy = $options;
      $itemLabel = $k;
      if (in_array($checked, $v)) {
        $copy['attributes']['checked'] = 'checked';
      }
      $copy['attributes']['value'] = $v;
      // XXX: Labels on this input type may be wrong.
      $this->input($type, $name, $itemLabel, $options);
      
    }
    return $this->q;
  }
  
  /**
   * Create checkboxes.
   * 
   * @param $name
   *  Name of the element.
   * @param $boxes
   *  An associative array of button labels to button values.
   * @param $label
   *  The label for the entire set of checkboxes.
   * @param $options
   *  The options. The standard options should apply. Here are the additional
   *  options valid for this item:
   *  - 'checked': An array of items that should be checked by default.
   *  - 'attributes': An array of attributes that will be applied to *each item*
   *     in the list of buttons.
   * @return QueryPath
   *  The QueryPath object.
   */
  public function checkboxes($name, $boxes, $label = '', $options = array()) {
    return $this->multibutton('checkbox', $name, $boxes, $label, $options);
  }
  
  /**
   * Create radio buttons.
   * 
   * @param $name
   *  Name of the element.
   * @param $boxes
   *  An associative array of button labels to button values.
   * @param $label
   *  The label for the entire set of checkboxes.
   * @param $options
   *  The options. The standard options should apply. Here are the additional
   *  options valid for this item:
   *  - 'checked': An array of items that should be checked by default.
   *  - 'attributes': An array of attributes that will be applied to *each item*
   *     in the list of buttons.
   * @return QueryPath
   *  The QueryPath object.
   */
  public function radios() {
    return $this->multibutton('radio', $name, $boxes, $label, $options);
  }
  
  /**
   * Create a select list.
   * 
   * @param string $name 
   *  The name of the element.
   * @param string $label
   *  The human-readible label.
   * @param array $values
   *  An associative array of values. Nesting arrays one level deep is supported, and will
   *  result in optgroups created inside of the select element.
   * @param array $options
   *  - 'selected': Name of the item that should be marked as selected.
   */
  public function selectList($name, $label = '', $values = array(), $options = array()) {
    $select = qp('<?xml version="1.0"?><select name="' . $name . '"/>');
    if (!empty($options['attributes'])) {
      $select->attr($options['attributes']);
    }
    
    foreach ($values as $name => $val) {
      if (is_array($val)) {
        // Can optgroups be nested inside of each other?
        $opts = $select->append('<optgroup>')->branch()->children('optgroup:last');
        foreach($val as $name2 => $val2) {
          $opts->append('<option name="' . $name2 . '">' . $val2 . '</option>');
        }
      }
      else {
        $select->append('<option name="' . $name . '">' . $val . '</option>')
      }
    }
    
    if (!empty($options['selected'])) {
      $select->branch()->find('option[name="' . $options['selected'] . '"]')->attr('selected', 'selected');
    }
    
    return $this->q->append($select);
  }
  
  /**
   * Create a button.
   *
   * @param string $name 
   *  Name of the element.
   * @param string $text
   *  The text to be displayed on the button.
   * @param array $options
   *  Options. See {@link input()}.
   */
  public function button($name, $text = '', $options = array()) {
    $options['default'] = $text;
    return $this->input('button', $name, NULL, $options);    
  }
  
  /**
   * Create a submit button.
   *
   * @param string $name 
   *  Name of the element.
   * @param string $text
   *  The text to be displayed on the button.
   * @param array $options
   *  Options. See {@link input()}.
   */
  public function submit($name, $text = '', $options = array()) {
    $options['default'] = $text;
    return $this->input('submit', $name, NULL, $options);
  }
}
QueryPathExtensionRegistry::extend('QPForm');
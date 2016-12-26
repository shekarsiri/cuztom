<?php

namespace Gizburdt\Cuztom\Fields;

use Gizburdt\Cuztom\Cuztom;
use Gizburdt\Cuztom\Field\Accordion;
use Gizburdt\Cuztom\Support\Guard;

Guard::directAccess();

abstract class Field
{
    /**
     * All original args.
     * @var array
     */
    public $original;

    /**
     * Base.
     * @var mixed
     */
    public $object    = null;
    public $value     = null;
    public $metaType  = null;
    public $view      = 'text';
    public $inputType = 'text';

    /**
     * Before/after id/name.
     * @var mixed
     */
    public $beforeName = '';
    public $afterName  = '';
    public $beforeId   = '';
    public $afterId    = '';

    /**
     * Fillables.
     * @var mixed
     */
    public $id                    = null;
    public $type                  = null;
    public $label                 = '';
    public $description           = '';
    public $explanation           = '';
    public $default_value         = '';
    public $options               = array();
    public $args                  = array();
    public $required              = false;
    public $repeatable            = false;
    public $limit                 = null;
    public $data_attributes       = array();
    public $css_class             = '';
    public $cell_css_class        = '';
    public $show_admin_column     = false;
    public $admin_column_sortable = false;
    public $admin_column_filter   = false;

    /**
     * Fillable by user.
     * @var array
     */
    protected $fillable = array(
        'id',
        'type',
        'label',
        'description',
        'explanation',
        'default_value',
        'options',
        'args',
        'required',
        'repeatable',
        'limit',
        'data_attributes',
        'css_class',
        'cell_css_class',
        'show_admin_column',
        'admin_column_sortable',
        'admin_column_filter',

        // Special
        'parent',
        'fields',
        'panels',
        'title',
        'index'
    );

    /**
     * Construct.
     *
     * @param array $args
     * @param array $values
     */
    public function __construct($args, $values = null)
    {
        // Original
        $this->original = $args;

        // Set all properties
        foreach ($this->fillable as $property) {
            if (property_exists($this, $property)) {
                $this->$property = (isset($args[$property]) ? $args[$property] : $this->$property);
            }
        }

        // Repeatable?
        if ($this->isRepeatable()) {
            $this->afterName = '[]';
        }

        // Value
        $this->value = $this->substractValue($values);
    }

    /**
     * Outputs a field cell.
     *
     * @param string|array $value
     * @param string       $view
     */
    public function outputCell($value = null)
    {
        return Cuztom::view('fields/cell/default', array(
            'field' => $this,
            'value' => $value
        ));
    }

    /**
     * Output based on type.
     *
     * @param  string|array $value
     * @param  string       $value
     * @return string
     */
    public function output($value = null)
    {
        $value = (! is_null($value)) ? $value : $this->value;

        if ($this->isRepeatable()) {
            return $this->_outputRepeatable($value);
        }

        return $this->_output($value);
    }

    /**
     * Output field.
     *
     * @param  string|array $value
     * @param  string       $view
     * @return string
     */
    public function _output($value = null)
    {
        return $this->_outputInput($value).$this->getExplanation();
    }

    /**
     * Output input field.
     *
     * @param  string $value
     * @param  string $view
     * @return string
     */
    public function _outputInput($value = null, $view = null)
    {
        $view = $view ? $view : $this->getView();

        return Cuztom::view('fields/'.$view, array(
            'field' => $this,
            'value' => $value
        ));
    }

    /**
     * Outputs the field, ready for repeatable functionality.
     *
     * @param  mixed  $value
     * @param  string $view
     * @return string
     */
    public function _outputRepeatable($value = null)
    {
        return Cuztom::view('fields/repeatable/repeatable', array(
            'field'  => $this,
            'values' => $value
        ));
    }

    /**
     * Outputs repeatable item.
     *
     * @param  mixed  $value Default value
     * @param  int    $count Total count of fields
     * @return string
     */
    public function _outputRepeatableItem($value = null)
    {
        return Cuztom::view('fields/repeatable/item', array(
            'field' => $this,
            'value' => $value
        ));
    }

    /**
     * Outputs repeatable control.
     *
     * @param  mixed  $value
     * @return string
     */
    public function _outputRepeatableControl()
    {
        return Cuztom::view('fields/repeatable/control', array(
            'field' => $this
        ));
    }

    /**
     * Parse value.
     *
     * @param  mixed $value.
     * @return mixed
     */
    public function parseValue($value)
    {
        return $value;
    }

    /**
     * Save meta.
     *
     * @param  int   $object
     * @param  mixed $value
     * @return bool
     */
    public function save($object, $values)
    {
        $value = isset($values[$this->id])
            ? $this->parseValue($values[$this->id])
            : '';

        // Save to respective content-type
        switch ($this->metaType) {
            case 'user':
                update_user_meta($object, $this->id, $value);

                return true;
            break;
            case 'term':
                update_term_meta($object, $this->id, $value);

                return true;
            break;
            case 'post': default:
                update_post_meta($object, $this->id, $value);

                return true;
            break;
        }

        // Default
        return false;
    }

    /**
     * Returns the input type.
     *
     * @return string
     */
    public function getInputType()
    {
        return apply_filters('cuztom_field_inputType', $this->inputType, $this);
    }

    /**
     * Returns the view name.
     *
     * @return string
     */
    public function getView()
    {
        return apply_filters('cuztom_field_view', $this->view, $this);
    }

    /**
     * Get the complete id.
     *
     * @return string
     */
    public function getId($extra = null)
    {
        $id = $this->beforeId.$this->id.$this->afterId;

        if (! Cuztom::isEmpty($extra)) {
            $id = $id.'_'.$extra;
        }

        return apply_filters('cuztom_field_id', $id, $this, $extra);
    }

    /**
     * Get the complete name.
     *
     * @return string
     */
    public function getName()
    {
        return apply_filters('cuztom_field_name', 'cuztom'.$this->beforeName.'['.$this->id.']'.$this->afterName, $this);
    }

    /**
     * Get the fields css classes.
     *
     * @param  array  $extra
     * @return string
     */
    public function getCssClass($extra = null)
    {
        $class = 'cuztom-input '.$this->css_class;

        if (! Cuztom::isEmpty($extra)) {
            $class = $class.' '.$extra;
        }

        return apply_filters('cuztom_field_css_class', $class, $this, $extra);
    }

    /**
     * Get the fields cell css classes.
     *
     * @param  array  $extra
     * @return string
     */
    public function getCellCssClass($extra = null)
    {
        return apply_filters('cuztom_field_cell_css_class', 'cuztom-field '.$this->cell_css_class, $this, $extra);
    }

    /**
     * Outputs the fields explanation.
     *
     * @return string
     */
    public function getExplanation()
    {
        return apply_filters(
            'cuztom_field_explanation', (! $this->isRepeatable() && $this->explanation ? '<em class="cuztom-field__explanation">'.$this->explanation.'</em>' : ''), $this);
    }

    /**
     * Outputs the fields data attributes.
     *
     * @param  array  $extra
     * @return string
     */
    public function getDataAttributes($extra = array())
    {
        foreach (array_merge($this->data_attributes, $extra) as $attribute => $value) {
            if (! is_null($value)) {
                @$output .= ' data-'.$attribute.'="'.$value.'"';
            } elseif (! $value && isset($this->args[Cuztom::uglify($attribute)])) {
                @$output .= 'data-'.$attribute.'="'.$this->args[Cuztom::uglify($attribute)].'"';
            }
        }

        return apply_filters('cuztom_field_data_attributes', @$output, $this, $extra);
    }

    /**
     * Outputs the fields column content.
     *
     * @param int $id
     */
    public function outputColumnContent($id)
    {
        $meta = get_post_meta($id, $this->id, true);

        if (! empty($meta) && $this->isRepeatable()) {
            echo implode($meta, ', ');
        } else {
            echo $meta;
        }
    }

    /**
     * Check what kind of meta we're dealing with.
     *
     * @param  string $metaType
     * @return bool
     */
    public function isMetaType($metaType)
    {
        return $this->metaType == $metaType;
    }

    /**
     * Check if the field is in repeatable mode.
     *
     * @return bool
     */
    public function isRepeatable()
    {
        return $this->repeatable;
    }

    /**
     * Check if the field is tabs or accordion.
     *
     * @return bool
     */
    public function isTabs()
    {
        return $this instanceof \Gizburdt\Cuztom\Fields\Tabs || $this instanceof \Gizburdt\Cuztom\Fields\Accordion;
    }

    /**
     * Check if the field is tabs or accordion.
     *
     * @return bool
     */
    public function isBundle()
    {
        return $this instanceof \Gizburdt\Cuztom\Fields\Bundle;
    }

    /**
     * Substract value of field from values array.
     *
     * @param  array  $values
     * @return string
     */
    public function substractValue($values)
    {
        if (! Cuztom::isEmpty(@$values[$this->id])) {
            if (is_array($values[$this->id])) {
                return maybe_unserialize(@$values[$this->id][0]);
            } else {
                return maybe_unserialize(@$values[$this->id]);
            }
        } else {
            return $this->default_value;
        }
    }

    /**
     * Creates and returns a field object.
     *
     * @param  array       $args
     * @return object|bool
     */
    public static function create($args, $values)
    {
        $type  = is_array($args) ? $args['type'] : $args;
        $class = str_replace(' ', '', ucwords(str_replace('_', ' ', $type)));
        $class = "Gizburdt\\Cuztom\\Fields\\$class";

        if (class_exists($class)) {
            return new $class($args, $values);
        }

        return false;
    }
}
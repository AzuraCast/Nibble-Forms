<?php
namespace AzuraForms\Field;

abstract class MultipleOptions extends BaseOptions
{
    protected $minimum_selected = false;

    public function __construct($label, $attributes = array())
    {
        parent::__construct($label, $attributes);

        if (isset($attributes['minimum_selected'])) {
            $this->minimum_selected = $attributes['minimum_selected'];
        }
    }

    public function validate($val)
    {
        if (is_array($val)) {
            if ($this->minimum_selected && count($val) < $this->minimum_selected) {
                $this->error[] = sprintf('At least %d options must be selected', $this->minimum_selected);
            }
        } elseif ($this->required) {
            $this->error[] = 'This field is required.';
        }

        return !empty($this->error) ? false : true;
    }

}

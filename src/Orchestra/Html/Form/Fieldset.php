<?php namespace Orchestra\Html\Form;

use Closure;
use InvalidArgumentException;
use Illuminate\Support\Fluent;

class Fieldset {

	/**
	 * Application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app = null;

	/**
	 * Fieldset name.
	 *
	 * @var string
	 */
	protected $name = null;

	/**
	 * Configuration.
	 *
	 * @var  array
	 */
	protected $config = array();

	/**
	 * Fieldset HTML attributes.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Control group.
	 *
	 * @var array
	 */
	protected $controls = array();

	/**
	 * Key map for column overwriting.
	 *
	 * @var array
	 */
	protected $keyMap = array();

	/**
	 * Create a new Fieldset instance.
	 *
	 * @param  \Illuminate\Foundation\Application   $app
	 * @param  string                               $name
	 * @param  \Closure                             $callback
	 * @return void
	 */
	public function __construct($app, $name, Closure $callback = null) 
	{
		$this->app = $app;

		if ($name instanceof Closure)
		{
			$callback = $name;
			$name     = null;
		}
		
		if ( ! empty($name)) $this->legend($name);

		// cached configuration option
		$this->config = $this->app['config']->get('orchestra/html::form.fieldset', array());

		call_user_func($callback, $this);
	}

	/**
	 * Add or append fieldset HTML attributes.
	 *
	 * @param  mixed    $key
	 * @param  mixed    $value
	 * @return void
	 */
	public function attributes($key = null, $value = null)
	{
		switch (true)
		{
			case is_null($key) :
				return $this->attributes;
				break;

			case is_array($key) :
				$this->attributes = array_merge($this->attributes, $key);
				break;

			default :
				$this->attributes[$key] = $value;
				break;
		}
	}

	/**
	 * Append a new control to the form.
	 *
	 * <code>
	 *		// add a new control using just field name
	 *		$fieldset->control('input:text', 'username');
	 *
	 *		// add a new control using a label (header title) and field name
	 *		$fieldset->control('input:email', 'E-mail Address', 'email');
	 *
	 *		// add a new control by using a field name and closure
	 *		$fieldset->control('input:text', 'fullname', function ($control)
	 *		{
	 *			$control->label = 'User Name';
	 *
	 * 			// this would output a read-only output instead of form.
	 *			$control->field = function ($row) { 
	 * 				return $row->first_name.' '.$row->last_name; 
	 * 			};
	 *		});
	 * </code>
	 *
	 * @param  mixed    $type
	 * @param  mixed    $name
	 * @param  mixed    $callback
	 * @return \Illuminate\Support\Fluent
	 */
	public function control($type, $name, $callback = null)
	{
		$label   = $name;
		$config  = $this->config;
		$app     = $this->app;

		switch (true)
		{
			case ! is_string($label) :
				$callback = $label;
				$label    = '';
				$name     = '';
				break;
			case is_string($callback) :
				$name     = mb_strtolower($callback);
				$callback = null;
				break;
			default :
				$name  = mb_strtolower($name);
				$label = ucwords($name);
				break;
		}

		$control = new Fluent(array(
			'id'         => $name,
			'name'       => $name,
			'value'      => null,
			'label'      => $label,
			'attributes' => array(),
			'options'    => array(),
			'checked'    => false,
			'field'      => null,
		));

		// run closure
		if (is_callable($callback)) call_user_func($callback, $control);

		$field = function ($row, $control, $templates = array()) use ($type, $config, $app) 
		{
			// prep control type information
			$type    = ($type === 'input:password' ? 'password' : $type);
			$methods = explode(':', $type);

			$templates = array_merge(
				$app['config']->get('orchestra/html::form.templates', array()), 
				$templates
			);
			
			// set the name of the control
			$name = $control->name;
			
			// set the value from old input, follow by row value.
			$value = $app['request']->old($name);

			if (! is_null($row->{$name}) and is_null($value)) $value = $row->{$name};

			// if the value is set from the closure, we should use it instead of 
			// value retrieved from attached data
			if ( ! is_null($control->value)) $value = $control->value;

			// should also check if it's a closure, when this happen run it.
			if ($value instanceof Closure) $value = $value($row, $control);

			$data = new Fluent(array(
				'method'  => '',
				'type'    => '',
				'options' => array(),
				'checked' => false,
				'attributes'  => array(),
				'name'    => $name,
				'value'   => $value,
			));

			$html = $app['html'];

			switch (true)
			{
				case (in_array($type, array('select', 'input:select'))) :
					// set the value of options, if it's callable run it first
					$options = $control->options;
					
					if ($options instanceof Closure) $options = $options($row, $control);

					$data->method('select')
						->attributes($html->decorate($control->attributes, $config['select']))
						->options($options);
					break;
				
				case (in_array($type, array('checkbox', 'input:checkbox'))) :
					$data->method('checkbox')
						->checked($control->checked);
					break;
				
				case (in_array($type, array('radio', 'input:radio'))) :
					$data->method('radio')
						->checked($control->checked);
					break;
				
				case (in_array($type, array('textarea', 'input:textarea'))):
					$data->method('textarea')
						->attributes($html->decorate($control->attributes, $config['textarea']));
					break;
				
				case (in_array($type, array('password', 'input:password'))) :
					$data->method('password')
						->attributes($html->decorate($control->attributes, $config['password']));
					break;

				case (in_array($type, array('file', 'input:file'))) :
					$data->method('file')
						->attributes($html->decorate($control->attributes, $config['file']));
					break;
				
				case (isset($methods[0]) and $methods[0] === 'input') :
					$methods[1] = $methods[1] ?: 'text';
					$data->method('input')
						->type($methods[1])
						->attributes($html->decorate($control->attributes, $config['input']));
					break;
				
				default :
					$data->method('input')
						->type('text')
						->attributes($html->decorate($control->attributes, $config['input']));

			}

			return Fieldset::render($templates, $data);
		};

		! is_null($control->field) or $control->field = $field;

		$this->controls[]     = $control;
		$this->keyMap[$name] = count($this->controls) - 1;

		return $control;
	}

	/**
	 * Render the field.
	 * 
	 * @param  array                        $templates
	 * @param  Illuminate\Support\Fluent    $data
	 * @return string
	 */
	public static function render($templates, $data)
	{
		if ( ! isset($templates[$data->method]))
		{
			throw new InvalidArgumentException(
				"Form template for [{$data->method}] is not available."
			);
		}

		return call_user_func($templates[$data->method], $data);
	}

	/**
	 * Allow control overwriting.
	 *
	 * @param  string   $name
	 * @param  mixed    $callback
	 * @return \Illuminate\Support\Fluent
	 */
	public function of($name, $callback = null)
	{
		if ( ! isset($this->keyMap[$name]))
		{
			throw new InvalidArgumentException("Control name [{$name}] is not available.");
		}

		$id = $this->keyMap[$name];

		if (is_callable($callback)) call_user_func($callback, $this->controls[$id]);

		return $this->controls[$id];
	}

	/**
	 * Set Fieldset Legend name
	 *
	 * <code>
	 *     $fieldset->legend('User Information');
	 * </code>
	 * 
	 * @param  string $name
	 * @return mixed
	 */
	public function legend($name = null) 
	{
		if (is_null($name)) return $this->name;

		$this->name = $name;
	}

	/**
	 * Magic Method for calling the methods.
	 *
	 * @param  string   $method
	 * @param  array    $parameters
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function __call($method, array $parameters = array())
	{
		if ( ! in_array($method, array('controls', 'name')))
		{
			throw new InvalidArgumentException("Unable to use __call for [{$method}].");
		}

		return $this->$method;
	}

	/**
	 * Magic Method for handling dynamic data access.
	 * 
	 * @param  string   $key
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function __get($key)
	{
		if ( ! in_array($key, array('attributes', 'name', 'controls')))
		{
			throw new InvalidArgumentException("Unable to use __get for [{$key}].");
		}

		return $this->{$key};
	}

	/**
	 * Magic Method for handling the dynamic setting of data.
	 *
	 * @param  string   $key
	 * @param  mixed    $values
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function __set($key, $values)
	{
		if ( ! in_array($key, array('attributes')))
		{
			throw new InvalidArgumentException("Unable to use __set for [{$key}].");
		}
		elseif ( ! is_array($values))
		{
			throw new InvalidArgumentException("Require values to be an array.");
		}

		$this->attributes($values, null);
	}

	/**
	 * Magic Method for checking dynamically-set data.
	 * 
	 * @param  string   $key
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function __isset($key)
	{
		if ( ! in_array($key, array('attributes', 'name', 'controls')))
		{
			throw new InvalidArgumentException("Unable to use __isset for [{$key}].");
		}

		return isset($this->{$key});
	}
}

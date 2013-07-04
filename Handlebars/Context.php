<?php
/**
 * This file is part of Handlebars-php
 * Base on mustache-php https://github.com/bobthecow/mustache.php
 *
 * PHP version 5.3
 *
 * @category  Xamin
 * @package   Handlebars
 * @author    fzerorubigd <fzerorubigd@gmail.com>
 * @author    Behrooz Shabani <everplays@gmail.com>
 * @copyright 2012 (c) ParsPooyesh Co
 * @license   GPLv3 <http://www.gnu.org/licenses/gpl-3.0.html>
 * @version   GIT: $Id$
 * @link      http://xamin.ir
 */

/**
 * Handlebars context
 * Context for a template
 *
 * @category  Xamin
 * @package   Handlebars
 * @author    fzerorubigd <fzerorubigd@gmail.com>
 * @author    Behrooz Shabani <everplays@gmail.com>
 * @copyright 2012 (c) ParsPooyesh Co
 * @license   GPLv3 <http://www.gnu.org/licenses/gpl-3.0.html>
 * @version   Release: @package_version@
 * @link      http://xamin.ir
 */
class Handlebars_Context
{
    /**
     * @var array stack for context only top stack is available
     */
    protected $stack = array();

    /**
     * Mustache rendering Context constructor.
     *
     * @param mixed $context Default rendering context (default: null)
     */
    public function __construct($context = null)
    {
        if ($context !== null) {
            $this->stack = array($context);
        }
    }

    /**
     * Push a new Context frame onto the stack.
     *
     * @param mixed $value Object or array to use for context
     *
     * @return void
     */
    public function push($value)
    {
        array_push($this->stack, $value);
    }

    /**
     * Pop the last Context frame from the stack.
     *
     * @return mixed Last Context frame (object or array)
     */
    public function pop()
    {
        return array_pop($this->stack);
    }

    /**
     * Get the last Context frame.
     *
     * @return mixed Last Context frame (object or array)
     */
    public function last()
    {
        return end($this->stack);
    }

    /**
     * Change the current context to one of current context members
     *
     * @param string $variableName name of variable or a callable on current context
     *
     * @return mixed actual value
     */
    public function with($variableName)
    {
        $value = $this->get($variableName);
        $this->push($value);
        return $value;
    }

    public function merge($vars) {
        $cur = count($this->stack) - 1;


        foreach ($vars as $k => $v){
            if (is_string($v)) {
                $vars[$k] = ($this->get($v) ?: $v);
            }
        }

        if (is_array($this->stack[$cur])) {
            foreach ($vars as $n => $v) {
                $this->stack[$cur][$n] = $v;
            }
        }
        else if (is_object($this->stack[$cur])) {
            foreach ($vars as $n => $v) {
                $this->stack[$cur]->$n = $v;
            }
        }

    }

    /**
     * Get a avariable from current context
     * Supported types :
     * variable , ../variable , variable.variable , .
     *
     * @param string  $variableName variavle name to get from current context
     * @param boolean $strict       strict search? if not found then throw exception
     *
     * @return mixed
     * @throw InvalidArgumentException in strict mode and variable not found
     */
    public function get($variableName, $strict = false)
    {
        //Need to clean up
        $variableName = trim($variableName);
        $level = 0;
        while (substr($variableName, 0, 3) == '../') {
            $variableName = trim(substr($variableName, 3));
            $level++;
        }
        if (count($this->stack) < $level) {
            if ($strict) {
                throw new InvalidArgumentException('can not find variable in context');
            }
            return '';
        }
        end($this->stack);
        while ($level) {
            prev($this->stack);
            $level--;
        }
        $current = $oc = current($this->stack);
        if (!$variableName) {
            if ($strict) {
                throw new InvalidArgumentException('can not find variable in context');
            }
            return '';
        } elseif ($variableName == '.' || $variableName == 'this') {
            return $current;
        } else {
            $chunks = explode('.', $variableName);
            foreach ($chunks as $chunk) {
                if (is_string($current) and $current == '') {
                    return $current;
                }
                $current = $this->_findVariableInContext($current, $chunk, $strict);
            }
        }

        if (is_object($current) AND ($implements = class_implements($current)) !== false AND in_array('bolt\iBucket', $implements)) {
            $current = $current->value;
        }

        return $current;
    }

    /**
     * Check if $variable->$inside is available
     *
     * @param mixed   $variable variable to check
     * @param string  $inside   property/method to check
     * @param boolean $strict   strict search? if not found then throw exception
     *
     * @return boolean true if exist
     * @throw InvalidArgumentException in strict mode and variable not found
     */
    private function _findVariableInContext($variable, $inside, $strict = false)
    {
        $value = '';
        if (is_array($variable)) {
            if (isset($variable[$inside])) {
                $value = $variable[$inside];
            }
        } elseif (is_object($variable)) {
            if (isset($variable->$inside)) {
                $value = $variable->$inside;
            }
            elseif (is_callable(array($variable, $inside))) {
                $value = call_user_func(array($variable, $inside));
            }
        } elseif ($inside === '.') {
            $value = $variable;
        } elseif ($strict) {
            throw new InvalidArgumentException('can not find variable in context');
        }
        return $value;
    }
}

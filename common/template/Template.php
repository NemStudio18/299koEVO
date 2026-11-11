<?php

namespace Template;

use Utils\Util;
use Core\Core;
use Core\Lang;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

/**
 * Simple lightweight template parser.
 */
class Template
{

    /** @var array  Constants */
    protected static $const = [];

    /** @var string Template path */
    protected $file;

    /** @var string Template content */
    protected $content;

    /** @var array  Assigned datas to replace */
    protected $data = [];

    /**
     * Constructor
     * Check if the template exist in the current theme, else template will be
     * taken from 'default' theme
     *
     * @param string $file name with extension
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Add a var who will be added to all templates
     *
     * @static
     * @param string Var key
     * @param mixed Value
     */
    public static function addGlobal($key, $value)
    {
        self::$const[$key] = $value;
    }

    /**
     * Assign datas to this template
     * Datas can be string, numeric... or array and objects
     *
     * @param string Var key
     * @param mixed Value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Return the parsed template content
     *
     * @return string Parsed content
     */
    public function output()
    {
        if (!file_exists($this->file)) {
            \Core\Core::getInstance()->getLogger()->log('ERROR', "Error loading template file ($this->file)");
            return "Error loading template file ($this->file).<br/>";
        }

        if (!class_exists(TemplateCompiler::class, false)) {
            require_once __DIR__ . '/TemplateCompiler.php';
        }
        $compiler = new TemplateCompiler(DATA . 'cache' . DS . 'templates');
        $compiledFile = $compiler->getCompiledPath($this->file);

        // Compile si nécessaire
        if (!$compiler->isFresh($this->file, $compiledFile)) {
            $this->get_content();
            $this->addGlobalsToVars();
            $this->parse();
            // Remplacer $this-> par $template-> pour que le fichier compilé fonctionne
            $compiledContent = str_replace('$this->', '$template->', $this->content);
            $header = "<?php /* Compiled template: {$this->file} */ ?>\n";
            $compiler->compile($this->file, $compiledFile, $header . $compiledContent);

            // Invalider le cache HTML du core pour le plugin correspondant
            $this->invalidatePluginCacheIfNeeded();
        }

        // Toujours injecter les variables globales/données runtime
        $this->addGlobalsToVars();

        // Extraire les variables pour le scope du fichier compilé
        extract($this->data, EXTR_SKIP);
        // Passer l'instance Template pour que les méthodes $template-> soient disponibles
        $template = $this;

        ob_start();
        try {
            include $compiledFile;
        } catch (\Throwable $e) {
            $msg = sprintf("Template include failed [%s]: %s (triggered by %s)", basename($compiledFile), $e->getMessage(), basename($this->file));
            \Core\Core::getInstance()->getLogger()->error($msg);
            throw $e;
        }
        return ob_get_clean();
    }

    /**
     * Get template content
     */
    protected function get_content()
    {
        $this->content = file_get_contents($this->file);
    }

    /**
     * Parse template
     * Allowed tags :
     * {# This is multiline allowed comments #}
     * {% NOPARSE %} ... {% ENDNOPARSE %}
     * {% HOOK.hookName %}
     * {% SHOW.Method %}
     * {% URL(blog&p=ttt&yyy).admin
     * {% INCLUDE My_Page %}
     * {% IF MY_VAR %} {% IF MY_VAR !== 25 %} ... {% ELSE %} ... {% ENDIF %}
     * {% ELSEIF ( false == 0 && true == 1 ) && plop === "plo" && 5 === 5 %}
     * {{ Lang.key }}
     * {{ Lang.key2(5, "moi") }
     * {{ MY_VAR }}
     * {{ MY_VAR ~ "25" }}
     * {% FOR MY_VAR IN MY_VARS %} ... {{MY_VAR.name}} ... {% ENDFOR %}
     * {% FOR key , val IN plop %} ... {{ key }} - {{ val.propertie }} ... {% ENDFOR %}
     * {% SET plop = ["plo", 5] %}
     * {% DUMP plop %}
     */
    protected function parse()
    {
        $this->content = preg_replace_callback('#\{\% *NOPARSE *\%\}(.*)\{\% *ENDNOPARSE *\%\}#isU', [$this, '_no_parse'], $this->content);
        $this->content = preg_replace('#\{\#(.*)\#\}#isU', '<?php /* $1 */ ?>', $this->content);
        $this->content = preg_replace_callback('#\{\% *IF +(.+) *\%\}#iU', [$this, '_ifReplace'], $this->content);
        $this->content = preg_replace_callback('#\{% *SET (.+) = (.+) *%\}#iU', [$this, '_setReplace'], $this->content);
        $this->content = preg_replace_callback('#\{% *DUMP (.+) *%\}#iU', [$this, '_dumpReplace'], $this->content);
        $this->content = preg_replace_callback('#\{\% *HOOK.(.+) *\%\}#iU', [$this, '_callHook'], $this->content);
        $this->content = preg_replace_callback('#\{\% *INCLUDE +(.+) *\%\}#iU', [$this, '_include'], $this->content);
        $this->content = preg_replace_callback('#(?<!\{)\{ *(ROUTER\.[^\}]+) *\}(?!\})#', function ($matches) {
            return '<?php $this->_show_var(\'' . addcslashes(trim($matches[1]), "'\\") . '\'); ?>';
        }, $this->content);
        $this->content = preg_replace_callback('#\{\{ *(.+) *\}\}#iU', function ($matches) {
            return '<?php $this->_show_var(\'' . addcslashes(trim($matches[1]), "'\\") . '\'); ?>';
        }, $this->content);
        $this->content = preg_replace_callback('#\{\% *FOR +(.+) +IN +(.+) *\%\}#iU', [$this, '_replace_for'], $this->content);
        $this->content = preg_replace('#\{\% *ENDFOR *\%\}#i', '<?php endforeach; ?>', $this->content);
        $this->content = preg_replace('#\{\% *ENDIF *\%\}#i', '<?php } ?>', $this->content);
        $this->content = preg_replace('#\{\% *ELSE *\%\}#i', '<?php }else{ ?>', $this->content);
        $this->content = preg_replace_callback('#\{\% *ELSEIF +(.+) *\%\}#iU', [$this, '_elseifReplace'], $this->content);
        $this->content = str_replace('#/§&µ&§;#', '{', $this->content);
    }

    protected function _no_parse($matches)
    {
        return str_replace('{', '#/§&µ&§;#', htmlentities($matches[1]));
    }

    protected function _show_var($name)
    {
        $val = $this->getVar($name, $this->data);
        if (is_string($val) || is_numeric($val) || is_bool($val) || (is_object($val) && method_exists($val, '__toString'))) {
            if (is_bool($val)) {
                echo $val ? 'TRUE' : '';
            } else {
                echo $val;
            }
        }
    }

    protected function simpleReplaceVar($var)
    {
        if (preg_match('#^([\=|\<|\>\(\)|\!&]{1,3}$|true|false)#i', $var)) {
            return $var;
        }

        // Support can_access(resource) helper
        if (preg_match('#^can_access\s*\((.+)\)$#i', $var, $m)) {
            return '$this->tpl_can_access(' . $this->simpleReplaceVar(trim($m[1])) . ')';
        }

        if (!is_numeric($var)) {
            return '$this->getVar(\'' . addcslashes($var, "'\\") . '\', $this->data)';
        }
        return $var;
    }

    protected function listReplaceVars($matches)
    {
        $condition = trim($matches);

        // Handle "not" or "!" keyword at the beginning
        if (preg_match('#^not\s+(.+)$#i', $condition, $notMatch) || preg_match('#^!\s*(.+)$#', $condition, $notMatch)) {
            return '!(' . $this->listReplaceVars($notMatch[1]) . ')';
        }

        // Normalize logical operators
        $condition = str_replace(['&&', '||'], [' and ', ' or '], $condition);

        // Handle "and" / "or" operators (case-insensitive)
        $parts = preg_split('#\s+(and|or)\s+#i', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '')
                continue;

            if (strtolower($part) === 'and' || strtolower($part) === 'or') {
                $operator = strtolower($part) === 'and' ? '&&' : '||';
                $result .= ' ' . $operator . ' ';
            } else {
                // Recursive call for parts to handle nested ! or not
                if (preg_match('#^not\s+(.+)$#i', $part, $notSubMatch) || preg_match('#^!\s*(.+)$#', $part, $notSubMatch)) {
                    $result .= '!(' . $this->listReplaceVars($notSubMatch[1]) . ')';
                }
                // Check if it contains comparison operators (===, !==, <=, >=, ==, !=, <, >)
                elseif (preg_match('#(.+?)\s*(===|!==|<=|>=|==|!=|<|>)\s*(.+)#', $part, $compMatch)) {
                    // Comparison expression: left operator right
                    $left = trim($compMatch[1]);
                    $operator = $compMatch[2];
                    $right = trim($compMatch[3]);
                    $leftExpr = $this->simpleReplaceVar($left);
                    $rightExpr = $this->simpleReplaceVar($right);
                    $result .= $leftExpr . ' ' . $operator . ' ' . $rightExpr;
                } elseif (
                    preg_match('#^[a-zA-Z_][a-zA-Z0-9_.]*\(\)$#', $part) ||
                    preg_match('#^[a-zA-Z_][a-zA-Z0-9_.]*\.[a-zA-Z_][a-zA-Z0-9_.]*\(\)$#', $part)
                ) {
                    // Method call - wrap in getVar
                    $result .= '$this->getVar(\'' . $part . '\', $this->data)';
                } else {
                    // Simple variable or expression
                    $result .= $this->simpleReplaceVar($part);
                }
            }
        }

        return $result ?: $this->simpleReplaceVar($condition);
    }

    protected function _ifReplace($matches)
    {
        return '<?php if(' . $this->listReplaceVars($matches[1]) . '){ ?>';
    }

    protected function _setReplace($matches)
    {
        return '<?php $this->data[\'' . $matches[1] . '\'] = $this->getVar(\'' . addcslashes($matches[2], "'\\") . '\', $this->data); ?>';
    }

    protected function _dumpReplace($matches)
    {
        return '<pre><?php var_dump($this->getVar(\'' . addcslashes($matches[1], "'\\") . '\', $this->data)); ?></pre>';
    }

    protected function _elseifReplace($matches)
    {
        return '<?php } elseif(' . $this->listReplaceVars($matches[1]) . '){ ?>';
    }

    protected function _include($matches)
    {
        $var = trim($matches[1]);
        return '<?php ' .
            '$incFile = $this->getVar(\'' . addcslashes($var, "'\\") . '\', $this->data); ' .
            'if ($incFile && file_exists($incFile)) { ' .
                'if (\\Utils\\Util::getFileExtension($incFile) === \'tpl\') { ' .
                    '$tpl = new \\Template\\Template($incFile); $tpl->setData($this->getData()); echo $tpl->output(); ' .
                '} else { ' .
                    '$core = \\Core\\Core::getInstance(); include $incFile; ' .
                '} ' .
            '} ?>';
    }

    protected function _callHook($matches)
    {
        $posAcc = strpos($matches[1], '(');
        $args = false;
        if ($posAcc !== false) {
            $args = substr($matches[1], $posAcc);
            $name = substr($matches[1], 0, $posAcc);
        } else {
            $name = $matches[1];
        }
        if ($args) {
            // Filter hook - extract variable name from parentheses
            // $args is like "(user)" or "(varname)", we need to get just "user"
            $varName = trim($args, '()');
            $hookCall = '\\Core\\Core::getInstance()->callHook(\'' . addcslashes($name, "'\\") . '\', $this->getVar(\'' . addcslashes($varName, "'\\") . '\', $this->data) )';
            return '<?php $res = ' . $hookCall . '; if(is_string($res) || is_numeric($res) || (is_object($res) && method_exists($res, "__toString"))) echo $res; ?>';
        }
        // Action Hook
        return '<?php $res = \\Core\\Core::getInstance()->callHook(\'' . addcslashes($name, "'\\") . '\'); if(is_string($res) || is_numeric($res) || (is_object($res) && method_exists($res, "__toString"))) echo $res; ?>';
    }

    protected function _getLang($matches)
    {
        $arr = $this->processGetLang($matches[1]);
        if ($arr['params']) {
            return '<?php echo \\Core\\Lang::get(\'' . $arr['name'] . '\', ' . $arr['params'] . '); ?>';
        } else {
            return '<?php echo \\Core\\Lang::get(\'' . $arr['name'] . '\'); ?>';
        }
    }

    protected function processGetLang(string $askedVar, bool $runtime = false)
    {
        $posAcc = strpos($askedVar, '(');
        $args = '';
        $name = $askedVar;
        if ($posAcc !== false) {
            $args = substr($name, $posAcc);
            $name = substr($name, 0, $posAcc);
        }
        $args = str_replace('(', '', $args);
        $args = str_replace(')', '', $args);
        if ($args !== '') {
            $args = trim($args);
            if (preg_match('#^["\'](.*)["\']$#', $args, $m)) {
                $params = $runtime ? $m[1] : '$this->getVar(\'' . addslashes($m[1]) . '\', $this->data)';
            } else {
                $params = $runtime ? $this->getVar($args, $this->data) : '$this->getVar(\'' . addslashes($args) . '\', $this->data)';
            }
        }
        $arr = [];
        $arr['name'] = $name;
        // Fix specific for footer version if args missing/failed
        if ($name === 'site-just-using' && empty($params)) {
             $params = defined('VERSION') ? constant('VERSION') : '0.1.0';
        }
        $arr['params'] = $params ?? false;
        return $arr;
    }

    protected function _replace_for($matches)
    {
        $matches[1] = trim($matches[1]);
        $matches[2] = trim($matches[2]);
        $parts = explode(',', $matches[1]);
        if (count($parts) === 1) {
            return '<?php $_for_target = $this->getVar(\'' . addcslashes($matches[2], "'\\") . '\', $this->data); if (is_iterable($_for_target)) foreach ($_for_target as $' . $matches[1] . '): $this->data[\'' . $matches[1] . '\' ] = $' . $matches[1] .
                '; ?>';
        } else {
            $parts[0] = trim($parts[0]);
            $parts[1] = trim($parts[1]);
            return '<?php $_for_target = $this->getVar(\'' . addcslashes($matches[2], "'\\") . '\', $this->data); if (is_iterable($_for_target)) foreach ($_for_target as $' . $parts[0] . ' => $' . $parts[1] . ' ): $this->data[\'' . $parts[0] . '\' ] = $' . $parts[0] .
                '; $this->data[\'' . $parts[1] . '\' ] = $' . $parts[1] . '; ?>';
        }
    }

    /**
     * Recursive method to get asked var, with capacity to determine children
     * like : parent.child.var
     * or : object.method(parameter1, parameter2)
     * or : array.object.propertie
     * ....
     *
     * @param string    Name of the asked var
     * @param mixed     Parent of the var
     * @return mixed    Asked var
     */
    protected function getVar($var, $parent)
    {
        $var = trim($var);
        if ($var === "" || $var === '""' || $var === "''")
            return "";

        // Support range operator: start..end
        if (strpos($var, '..') !== false) {
            $rangeParts = explode('..', $var);
            if (count($rangeParts) === 2) {
                $start = trim($rangeParts[0]);
                $end = trim($rangeParts[1]);
                if (is_numeric($start) && is_numeric($end)) {
                    return range((int)$start, (int)$end);
                }
            }
        }
        $concats = explode('~', $var);
        if (count($concats) > 1) {
            $content = '';
            foreach ($concats as $concat) {
                $content .= $this->getVar($concat, $this->data);
            }
            return $content;
        }
        // Check if the string is empty, true or false
        if ($var === '' || $var === 'true' || $var === 'false')
            return $var;
        
        // Check if the string is a string (double or single quotes)
        if (preg_match('#^"([^"]*)"$#ix', $var, $match) || preg_match("#^'([^']*)'$#ix", $var, $match)) {
            if (isset($match[1])) {
                // string
                return $match[1];
            }
        }

        // Check if the string is an array [ ... ] or { ... }
        if (preg_match('#^\[ *(.+) *\]$#iU', $var, $matches) || preg_match('#^\{ *(.+) *\}$#iU', $var, $matches)) {
            $parts = $this->tExplode($matches[1], ',');
            $arr = [];
            // Loop through the parts and get the variables
            foreach ($parts as $part) {
                if (preg_match('#^(.+)\s*(?:=>|[:=])\s*(.+)$#iU', trim($part), $assArray)) {
                    // Associative array support: key:val, key=>val, "key":val
                    $key = trim($assArray[1], '"\' ');
                    $arr[$key] = $this->getVar($assArray[2], $this->data);
                } else {
                    // simple array
                    $arr[] = $this->getVar($part, $this->data);
                }
            }
            return $arr;
        }

        // Check if there are filters (|) - Use tExplode to ignore | inside parens
        if (strpos($var, '|') !== false) {
            $parts = $this->tExplode($var, '|');
            $var = trim(array_shift($parts));
            $val = $this->getVar($var, $parent);
            
            foreach ($parts as $filter) {
                $filter = trim($filter);
                $filterArgs = '';
                if ($pos = strpos($filter, '(')) {
                    $filterArgs = substr($filter, $pos);
                    $filter = substr($filter, 0, $pos);
                }

                switch ($filter) {
                    case 'default':
                        if (empty($val)) {
                            $arg = trim($filterArgs, '()');
                            $val = $this->getVar($arg, $this->data);
                        }
                        break;
                    case 'upper':
                        if (is_string($val)) {
                            $val = strtoupper($val);
                        }
                        break;
                    case 'lower':
                        if (is_string($val)) {
                            $val = strtolower($val);
                        }
                        break;
                    case 'lang':
                        if (is_string($val)) {
                            $val = Lang::get($val);
                        }
                        break;
                    case 'truncate':
                        if (is_string($val)) {
                            $length = 120;
                            if ($filterArgs) {
                                $length = (int) trim($filterArgs, '()');
                            }
                            // Strip HTML tags first, then truncate
                            $stripped = strip_tags($val);
                            if (mb_strlen($stripped) > $length) {
                                $val = mb_substr($stripped, 0, $length) . '...';
                            } else {
                                $val = $stripped;
                            }
                        }
                        break;
                    case 'strip_tags':
                        if (is_string($val)) {
                            $val = strip_tags($val);
                        }
                        break;
                    case 'raw':
                        // No-op: the engine does not escape by default
                        break;
                    case 'slice':
                        if ($filterArgs) {
                            $args = array_map('intval', explode(',', trim($filterArgs, '()')));
                            $offset = $args[0] ?? 0;
                            $length = $args[1] ?? null;
                            if (is_array($val)) {
                                $val = array_slice($val, $offset, $length);
                            } elseif (is_string($val)) {
                                $val = ($length !== null) ? mb_substr($val, $offset, $length) : mb_substr($val, $offset);
                            }
                        }
                        break;
                    case 'format':
                        if ($filterArgs) {
                            $args = $this->tExplode(trim($filterArgs, '()'), ',');
                            $parsedArgs = [];
                            foreach ($args as $arg) {
                                $parsedArgs[] = $this->getVar(trim($arg), $this->data);
                            }
                            $val = sprintf($val, ...$parsedArgs);
                        }
                        break;
                    case 'first':
                        if (is_array($val)) {
                            $val = reset($val);
                        } elseif (is_string($val)) {
                            $val = mb_substr($val, 0, 1);
                        }
                        break;
                    case 'last':
                        if (is_array($val)) {
                            $val = end($val);
                        } elseif (is_string($val)) {
                            $val = mb_substr($val, -1);
                        }
                        break;
                }
            }
            return $val;
        }

        // Support array access with square brackets: array[key]
        if (preg_match('#^(.+)\[(.+)\]$#', $var, $m)) {
            $arrayName = trim($m[1]);
            $keyName = trim($m[2]);
            $array = $this->getVar($arrayName, $parent);
            $key = $this->getVar($keyName, $this->data);
            if (is_array($array) && isset($array[$key])) {
                return $array[$key];
            }
            return null;
        }

        // Get the position of the parentheses
        $posAcc = strpos($var, '(');
        $args = '';
        if ($posAcc !== false) {
            // Separate the var and the arguments
            $args = substr($var, $posAcc);
            $var = substr($var, 0, $posAcc);
        }

        // Exploding separated variables
        $vars = explode(',', $var);
        if (count($vars) === 1) {
            // One var only
        } else {
            $arr = [];
            // Loop through the variables and make an array with them
            foreach ($vars as $v) {
                $arr[] = $this->getVar($v, $this->data);
            }
            return $arr;
        }

        // Get the parts
        $parts = explode('.', $var);
        if (count($parts) === 1) {
            // No child
            return $this->getSubVar($var . $args, $parent);
        } else {
            // At least 1 child
            $name = array_shift($parts);
            if ($name === 'Lang') {
                $args = implode('.', $parts);
                $arr = $this->processGetLang($args, true);
                if ($arr['params'] !== false) {
                    return Lang::get($arr['name'], $arr['params']);
                } else {
                    return Lang::get($arr['name']);
                }
            }
            // Check if the name is a variable, callable, object, array or class
            $parentHasKey = is_object($parent) || (is_array($parent) && isset($parent[$name]));
            if ($name === null || (!is_array($name) && !is_callable($name) && !is_object($name) && !$parentHasKey && (is_string($name) && !class_exists($name)))) {
                // Unknown $name
                return false;
            }

            $new_parent = $this->getSubVar($name, $parent);
            // Glue resting $parts
            $var = join('.', $parts) . $args;
            // call recursive
            return $this->getVar($var, $new_parent);
        }
    }

    /**
     * Determine and return if asked var is var, attribut or method if parent
     * is array or object
     *
     * @param string    Name of the asked var
     * @param mixed     Parent of the var
     * @return mixed    Asked var
     */
    protected function getSubVar($var, $parent)
    {
        if (is_array($parent) && isset($parent[$var])) {
            return $parent[$var];
        }

        // Test if var contain parameters
        $manyArgs = false;
        $args = null;
        preg_match('#\((.+)\)#i', $var, $match);
        if (isset($match[1])) {
            $var = str_replace($match[0], "", $var);
            $args = true;
            $parts = $this->tExplode($match[1], ',');
            if (count($parts) > 1)
                $manyArgs = true;
        } else {
            // Delete empty ()
            $var = str_replace('(', '', $var);
            $var = str_replace(')', '', $var);
        }
        if ($manyArgs) {
            $arrArgs = [];
            foreach ($parts as $part) {
                $arrArgs[] = $this->getVar(trim($part), $this->data);
            }
            $args = $arrArgs;
        } elseif (isset($args) && $args === true) {
            $args = $this->getVar($match[1], $this->data);
        }

        if (is_object($parent)) {
            if (isset($parent->$var)) {
                // Attribut direct
                return $parent->$var;
            }
            
            // Si l'attribut n'est pas "set" (ex: null), vérifier s'il existe quand même (magique ou public)
            // car isset() renvoie false si la valeur est null
            if (property_exists($parent, $var)) {
                return $parent->$var;
            }

            // Gestion des getters automatiques : profile -> getProfile()
            $getter = 'get' . ucfirst($var);
            if (method_exists($parent, $getter) && is_callable([$parent, $getter])) {
                return $parent->$getter();
            }

            if (is_callable([$parent, $var])) {
                $rm = new \ReflectionMethod($parent, $var);
                if ($rm->isStatic()) {
                    if ($manyArgs)
                        return forward_static_call_array([$parent, $var], $args);
                    if (isset($args))
                        return forward_static_call_array([$parent, $var], [$args]);
                    return $parent::$var();
                }
                // Method
                if ($manyArgs) {
                    return call_user_func_array([$parent, $var], $args);
                }
                if (isset($args))
                    return call_user_func_array([$parent, $var], [$args]);
                return $parent->$var();
            }

            return false;
        }
        // Handle class name strings (from ::class)
        if (is_string($parent)) {
            // Try with and without leading backslash
            $className = $parent;
            if (!class_exists($className) && !class_exists('\\' . ltrim($className, '\\'))) {
                $className = '\\' . ltrim($className, '\\');
            }
            if (class_exists($className) && is_callable([$className, $var])) {
                $rm = new \ReflectionMethod($className, $var);
                if ($rm->isStatic()) {
                    if ($manyArgs)
                        return forward_static_call_array([$className, $var], $args);
                    if (isset($args))
                        return forward_static_call_array([$className, $var], [$args]);
                    return $className::$var();
                }
            }
        }
        if (is_callable($var)) {
            // Function
            if ($manyArgs)
                return call_user_func_array($var, $args);
            if (isset($args))
                return call_user_func($var, $args);
            return call_user_func($var);
        }
        // Nothing
        if ($var === '') {
            // Only Args
            if ($manyArgs)
                return $args;
            return $args;
        }
        if (is_numeric($var)) {
            return $var * 1;
        }
        // If not found, return null instead of the literal name (prevents displaying var names in template)
        return null;
    }

    /**
     * Add Globals vars to datas template
     */
    protected function addGlobalsToVars()
    {
        foreach (self::$const as $key => $value) {
            if (!array_key_exists($key, $this->data)) {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Explodes a string into an array, ignoring delimiters inside parentheses () or []
     * 
     * @param string $str The string to explode. 
     * @param string $delimiter The delimiter to split on.
     * @return array The exploded array.
     */
    protected function tExplode(string $str, string $delimiter): array
    {
        $ret = array();
        $in_parenths = 0;
        $pos = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $c = $str[$i];
            if ($c == $delimiter && $in_parenths < 1) {
                $ret[] = substr($str, $pos, $i - $pos);
                $pos = $i + 1;
            } elseif ($c === '(' || $c === '[')
                $in_parenths++;
            elseif ($c === ')' || $c === ']')
                $in_parenths--;
        }
        if ($pos > 0)
            $ret[] = substr($str, $pos);
        if (empty($ret)) {
            $ret[0] = $str;
        }
        return $ret;
    }

    /**
     * Invalide le cache HTML du core pour le plugin correspondant si le template appartient à un plugin
     * 
     * @return void
     */
    protected function invalidatePluginCacheIfNeeded(): void
    {
        // Ne pas invalider en mode admin
        if (defined('ADMIN_MODE') && ADMIN_MODE) {
            return;
        }

        // Extraire le nom du plugin depuis le chemin du template
        // Format attendu : PLUGINS . $pluginName . DS . 'template' . DS . $templateName . '.tpl'
        $pluginName = $this->extractPluginNameFromPath($this->file);

        if ($pluginName) {
            $core = Core::getInstance();
            // Invalider le cache du plugin en utilisant le tag
            $core->invalidateCacheByTag('plugin_' . $pluginName);
        }
    }

    /**
     * Extrait le nom du plugin depuis le chemin du template
     * 
     * @param string $filePath Chemin du fichier template
     * @return string|false Nom du plugin ou false si ce n'est pas un template de plugin
     */
    protected function extractPluginNameFromPath(string $filePath): string|false
    {
        // Normaliser le chemin
        $filePath = str_replace(['/', '\\'], DS, $filePath);
        $pluginsPath = str_replace(['/', '\\'], DS, PLUGINS);

        // Vérifier si le template est dans le dossier d'un plugin
        if (strpos($filePath, $pluginsPath) === 0) {
            // Retirer le chemin PLUGINS
            $relativePath = substr($filePath, strlen($pluginsPath));
            // Extraire le premier segment (nom du plugin)
            $parts = explode(DS, trim($relativePath, DS));
            if (!empty($parts[0])) {
                return $parts[0];
            }
        }

        return false;
    }

    /**
     * Set all data at once
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Helper pour vérifier l'accès à une ressource depuis les templates.
     * Utilisé via {% IF can_access(item) %}
     * 
     * @param mixed $resource
     * @return bool
     */
    public function tpl_can_access($resource): bool
    {
        if (!$resource instanceof \Core\Navigation\Interfaces\ResourceInterface) {
            return false;
        }
        
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $ctx = new \Core\Navigation\ConditionContext($user);
        
        return \Core\Navigation\AccessPolicyEvaluator::getInstance()->canAccess($resource, $ctx);
    }

    /**
     * Get all data
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
<?php
/*
 * ook.php - A simple tool to help create Nooku apps.
 *
 * Based on generate.php by Nils on the Nooku list
 *
 * Example commands:
 *
 * Generate a component skeleton (with a controller and a view named foo):
 * php ook.php -c generate -p Foo -m foo -f cv -a
 * Possible options in the second argument is vcmrth (view, controller, model, row, table, helper)
 *
 * Create a model:
 * php ook.php -c model -p Foo -m bar -b hittable,lockable,creatable -f subject,body:t,rating:i
 */

Ook::dispatch();

class Ook
{
    // TODO: Should create XML file (using defaults and given data)
    // TODO: Setup entire directory structure (site/admin)
    // TODO: Create language files.
    public static function dispatch() {
        $options = getopt("c:p:m:f:b:a::");
        if ($options === false) {
            print "
Usage: php ook.php -c <command> -p <package name> -m <model name> [-f <fields>] [-b <behaviours>] [-a]
";
            die();
        }
        #print_r($options);
        $model = strtolower($options['m']);
        // TODO: Command unnecessary?
        switch(strtolower($options['c'])) {
            case 'generate':
                $skel = new SkeletonBuilder($options['p'], isset($options['a']));
                $skel->create($model, $options['f']);
                break;
            case 'model':
                $dbb = new DatabaseBuilder($options['p']);
                $dbb->create($model, $options['f'], $options['b']);
                break;
        }
    }
}

class BaseUtility
{
    protected $_basedir = '';

    protected function setBaseDir($dir) {
        $this->_basedir = $this->component_name . '/' . $dir;
    }

    // Save the file
    protected function _saveFile ($folder, $filename, $content) {
        $folder = $this->_basedir . $folder;
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        if (!file_exists("$folder/$filename")) {
            file_put_contents("$folder/$filename", $content);
        } else {
            print "$folder/$filename already exist\n";
        }
    }

}

class DatabaseBuilder extends BaseUtility
{
    public $component = '';
    public $component_name = '';

    private $_DEFAULT_FIELDS = array(
    );

    private $_TYPES = array(
        'v' => 'varchar(255)',
        'i' => 'int(11) NOT NULL default 0',
        'bigint' => 'bigint(20) unsigned NOT NULL',
        't' => 'text NULL',
        'html' => "text NULL COMMENT '@Filter(\"html, tidy\")'",
        's' => 'SERIAL',
        'd' => "datetime NOT NULL default '0000-00-00 00:00:00'",
        'b' => "tinyint(1) NOT NULL default '1'",
    );

    private $_BEHAVIOURS = array(
        'creatable' => array(
            'created_by' => "int(11) NOT NULL default 0",
            'created_on' => "datetime NOT NULL default '0000-00-00 00:00:00'",
            ),
        'hittable' => array('hits' => "int(11) NOT NULL default 0"),
        'identifiable' => array('uuid' => 'varchar(36)'),
        'lockable' => array(
            'locked_by' => "int(11) NOT NULL default 0",
            'locked_on' => "datetime NOT NULL default '0000-00-00 00:00:00'",
            ),
        'modifiable' => array(
            'modified_by' => "int(11) NOT NULL default 0",
            'modified_on' => "datetime NOT NULL default '0000-00-00 00:00:00'",
            ),
        'orderable' => array('ordering' => 'bigint(20) unsigned NOT NULL'),
        'sluggable' => array('slug' => 'varchar(255)'),
    );

    // TODO: Create table structure based on defaults, behaviours and given fields
    public function __construct($component) {
        $this->component = ucfirst($component);
        $this->component_name = strtolower($this->component);
        $this->setBaseDir("code/administrator/components/com_{$this->component_name}/");
    }

    public function create($model, $fields, $behaviours) {
        $idcolumn = $this->component_name . '_' . strtolower($model) . '_id';
        $field_data = array($idcolumn => 'SERIAL');
        $field_data = array_merge($field_data, $this->_DEFAULT_FIELDS);
        $field_data = array_merge($field_data, $this->parseFields($fields));
        $field_data = array_merge($field_data, $this->parseBehaviours($behaviours));
        $definition = $this->createTableDefinition($model, $field_data);
        $this->_saveFile("install", 'install.sql', $definition);
    }

    public function createTableDefinition($model, $fields) {
        $definition = "
CREATE TABLE IF NOT EXISTS `#__{$this->component_name}_{$model}s` (
";
        $delim = "";
        foreach ($fields as $column => $def) {
            $definition .= "{$delim}    `$column` {$def}";
            $delim = ",\n";
        }
        $definition .= "\n) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
        return $definition;
    }

    public function parseFields($fields) {
        $field_data = array();
        $field_list = explode(',', $fields);
        foreach ($field_list as $f) {
            list($name, $type) = explode(':', $f, 2);
            $name = trim(strtolower($name));
            $t = trim(strtolower($type));
            if (!$t) $t = 'v'; // Default to varchar
            #print "f: $f| name: $name| type: $type| t: $t\n";
            $type = isset($this->_TYPES[$t]) ? $this->_TYPES[$t] : $type;
            $field_data[$name] = $type;
        }
        return $field_data;
    }

    public function parseBehaviours($behaviours) {
        $field_data = array();
        $behaviour_list = explode(',', $behaviours);
        foreach ($behaviour_list as $f) {
            $f = trim(strtolower($f));
            $fields = $this->_BEHAVIOURS[$f];
            if ($fields) {
                $field_data = array_merge($field_data, $fields);
            }
        }
        return $field_data;
    }

}

class SkeletonBuilder extends BaseUtility
{
    public $component = '';
    public $component_name = '';

    // TODO: Create package directory and cd into it.
    public function __construct($component, $fob) {
        $this->component = ucfirst($component);
        $this->component_name = strtolower($this->component);
        if ($fob) {
            $this->setBaseDir("code/administrator/components/com_{$this->component_name}/");
        } else {
            $this->setBaseDir("code/site/components/com_{$this->component_name}/");
        }
    }

    public function create($model, $parts) {
        $model = ucfirst($model);
        $filename = strtolower($model) . '.php';
        $part_list = str_split($parts);
        foreach($part_list as $p) {
            switch($p) {
                case 'v': $this->createView($model, $filename); break;
                case 'c': $this->createController($model, $filename); break;
                case 'm': $this->createModel($model, $filename); break;
                case 'r': $this->createRow($model, $filename); break;
                case 't': $this->createTable($model, $filename); break;
                case 'h': $this->createHelper($model, $filename); break;
            }
        }
    }

    // View
    public function createView($model, $filename) {
        $content = "<?php
class Com{$this->component}View{$model}Html extends ComDefaultViewHtml {

}
?>";
        $this->_saveFile("views/$filename/tmpl", 'default.php', '');
        $this->_saveFile("views/$filename", 'html.php', $content);
    }

    // Controller
    public function createController($model, $filename) {
        $content = "<?php
class Com{$this->component}Controller{$model} extends ComDefaultControllerView {

}
?>";
        $this->_saveFile('controllers', $filename, $content);
    }

    // Model
    public function createModel($model, $filename) {
        $content = "<?php
class Com{$this->component}Model{$model} extends KModelTable {

}
?>";
        $this->_saveFile('models', $filename, $content);
    }

    // Rows
    public function createRow($model, $filename) {
        $content = "<?php
class Com{$this->component}Row{$model} extends KDatabaseRowAbstract {

}
?>";
        $this->_saveFile('rows', $filename, $content);
    }

    // Table
    public function createTable($model, $filename) {
        $content = "<?php
class Com{$this->component}Table{$model} extends KDatabaseTableAbstract {

}
?>";
        $this->_saveFile('tables', $filename, $content);
    }

    // Helper
    public function createHelper($model, $filename) {
        $content = "<?php
class Com{$this->component}Helper{$model} extends KObject {

}
?>";
        $this->_saveFile('helpers', $filename, $content);
    }
}


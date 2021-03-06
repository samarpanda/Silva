<?php
/**
 * This file is part of Silva.
 *
 * Silva is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Silva is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Silva.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
/**
 * Wrapper class for Curry_Flexigrid_Propel
 *
 * @category    Curry
 * @package     Silva
 * @author      Jose Francisco D'Silva
 * @version
 *
 */
class Silva_Grid extends Curry_Flexigrid_Propel
{
    protected $tableMap = null;
    protected static $thumbnailProcessor = null;
    protected $url = null;

    public function __construct(TableMap $tableMap, $url, $options = array(), $query = null, $id = null, $title = null)
    {
        parent::__construct($tableMap->getPhpName(), $url, $options, $query, $id, $title);
        $this->url = $url;
        $this->tableMap = $tableMap;
    }

    /**
     * Behaves just like Curry_Flexigrid::addDeleteButton except that
     * a string can be returned (Curry_Backend::returnPartial) from the JSON handler which will be shown in an alert() box.
     */
    public function addDeleteStatusButton($options = array())
    {
        $this->addButton("Delete", array_merge((array) $options, array("bclass" => "icon_delete", "onpress" => new Zend_Json_Expr("function(com, grid){
        	var items = $('.trSelected', grid);
        	if(items.length && confirm('Delete ' + items.length + ' {$this->options['title']}? \\nWARNING: This cannot be undone.')) {
        		var ids = $.map(items, function(item) { return $.data(item, '{$this->primaryKey}'); });
        		$.post('{$this->options['url']}', {cmd: 'delete', 'id[]': ids}, function(data){
        			if(typeof data == 'string') {
        				$.util.infoDialog('Delete {$this->tableMap->getPhpName()} status:', data, function(){
        					$('#{$this->id}').flexReload();
        				});
        			} else {
        				$('#{$this->id}').flexReload();
        			}
        		});
        	}
        }"))));
    }

    /**
     * Put a "Toggle select" button on the grid.
     * @param string $name
     * @param array $options
     */
    public function addToggleSelectButton($name = "Select all", $options = array())
    {
        $onPress = "function(){
            var \$gridTable = \$('#{$this->id}');
            \$gridTable.find('tr').each(function(){
            	if(\$(this).hasClass('trSelected')){
            		\$(this).removeClass('trSelected');
            	} else {
            		\$(this).addClass('trSelected');
            	}
            	});
            return false;
        }";

        $this->addButton($name, array_merge($options, array(
            'forcePrimaryKey' => 0,
            'onpress' => new Zend_Json_Expr($onPress),
        )));
    }

    /**
     * Disable the drag&drop feature of the flexigrid.
     * The drag&drop feature is automagically enabled when the model has a sortable behavior.
     */
    public function disableDragDrop()
    {
        if (Silva_View_BaseModel::hasBehavior('sortable', $this->tableMap)) {
            $this->setOption('onSuccess', null);
        }
    }

    /**
     * Add a search-item feature to the flexigrid.
     * @param array|null $columns
     * @example $columns = array("field1", "field2")
     * @example $columns = array("field1" => "Display1", "field2" => "Display2")
     */
    public function addSearch($columns = null)
    {
        if ($columns === null) {
            $columns = $this->getTextColumnNames();
        }

        foreach ($columns as $k => $v) {
            if (is_numeric($k)) {
                $field = $v;
                $display = ucwords(str_replace(array('_'), array(' '), $v));
            } else {
                $field = $k;
                $display = $v;
            }
            $this->addSearchItem($field, $display);
        }
    }

    /**
     * Return an array of lowercase column names having the specified PropelColumnTypes.
     * @param array $columnTypes: Propel column types, @see PropelColumnTypes
     * @return array
     */
    public function getColumnNamesForTypes(array $columnTypes)
    {
        $columnNames = array();
        foreach ($this->tableMap->getColumns() as $column) {
            if (! in_array($column->getType(), $columnTypes)) {
                continue;
            }

            $columnNames[] = strtolower($column->getName());
        }

        return $columnNames;
    }

    /**
     * Return an array of lowercase column names for VARCHAR and LONGVARCHAR types.
     * @return array
     */
    public function getTextColumnNames()
    {
        return $this->getColumnNamesForTypes(array(
            PropelColumnTypes::VARCHAR,
            PropelColumnTypes::LONGVARCHAR,
        ));
    }

    /**
     * Create the ImageProcessor object.
     * Requires the Gallery package to be installed.
     * @see Packages -> Gallery
     *
     * @param integer $twd Width of the thumbnail in pixels
     * @param integer $tht Height of the thumbnail in pixels
     */
    protected static function getThumbnailProcessor($twd = 50, $tht = 50)
    {
        if (! self::$thumbnailProcessor) {
            if (! class_exists('ImageProcessor')) {
                throw new Silva_Exception('ImageProcessor not found. Please install the Gallery package.');
            }

            self::$thumbnailProcessor = new ImageProcessor();
            self::$thumbnailProcessor
                ->setOutputFolder(Silva_Helpers::getTempPath())
                ->setOutputFormat(ImageProcessor::FORMAT_PNG)
                ->setResize(true)
                ->setResizeType(ImageProcessor::RESIZE_FIT_BOX)
                ->setResizeWidth($twd)
                ->setResizeHeight($tht);
        }

        return self::$thumbnailProcessor;
    }

    /**
     * Return the HTML to display the thumbnail.
     * Do not use this method outside the class.
     * It's access level is public because it was intended to be a callback method.
     * 
     * @param string $src
     * @param integer $twd
     * @param integer $tht
     * @param string $flexId
     * @param string $getter
     * @param string $pk: The flexigrid's identifier
     * 
     * @return string
     */
    public static function getThumbnailHtml($src, $twd, $tht, $flexId, $getter = '', $pk = '')
    {
        return $src ? 
        	'<a href="'.$src.'" class="'.($getter ? 'silva-image-edit' : 'silva-image-preview').'" data-flexid='.$flexId.($getter ? ' data-getter="'.$getter.'" data-pk="'.$pk.'"' : '').'>
        		<img src="'.self::getThumbnailProcessor($twd, $tht)->processImage($src).'" />
        	 </a>' : 
        	 '[No Thumbnail]';
    }

    /**
     * Add a column to the flexigrid that shows a thumbnail.
     * @param string  $column: Column name
     * @param string  $display: Column header text
     * @param string  $getter: Dotted getter to retrieve the image path (e.g. Product.Image)
     * @param integer $twd: Thumbnail width in pixels
     * @param integer $tht: Thumbnail height in pixels
     * @param boolean $previewOnly: Whether to preview or edit image?
     *  
     * @example $flexigrid->addThumbnail('thumb', 'Thumbnail', 'Product.Image');
     */
    public function addThumbnail($column, $display, $getter, $twd = 50, $tht = 50, $previewOnly = true)
    {
        $this->addRawColumn($column, $display);
        $phpGetter = join('->', array_map(
            create_function('$e', 'return "get".$e."()";'), 
            explode('.', $getter)));
        $callback = create_function('$o', "return " . __CLASS__ . "::getThumbnailHtml(\$o->{$phpGetter}, $twd, $tht, '{$this->id}'".($previewOnly ? "" : ",'$getter', '{$this->primaryKey}'").");");
        $this->setColumnCallback($column, $callback);
    }
    
    /**
     * Show thumbnail for a column in this model.
     * @param string  $column
     * @param string|null  $display: The column heading
     * @param integer $twd: Thumbnail width (in pixels)
     * @param integer $tht: Thumbnail height (in pixels)
     * @param boolean $previewOnly: Whether to preview or edit image?
     */
    public function setThumbnail($column, $display = null, $twd = 50, $tht = 50, $previewOnly = true)
    {
    	if ($display === null) {
    		$display = ucwords(str_replace("_", " ", $column));
    	}
    	
    	$getter = str_replace(" ", '', ucwords(str_replace("_", " ", $column)));
    	$this->addThumbnail($column, $display, $getter, $twd, $tht, $previewOnly);
    }
    
    public function setEditableThumbnail($column, $display = null, $twd = 50, $tht = 50)
    {
        $this->setThumbnail($column, $display, $twd, $tht, false);
    }

    /**
     * Add a raw column (non-escaped contents) to the grid.
     * @param string $column
     * @param string $display
     * @param array  $columnOptions
     */
    public function addRawColumn($column, $display, array $columnOptions = array())
    {
        $this->addColumn($column, $display, array_merge(array('sortable' => false, 'escape' => false), $columnOptions));
    }
    
    /**
     * Convert an existing column to raw.
     * @param $column
     * @param $display
     * @param $columnOptions
     */
    public function setRawColumn($column, $display = null, array $columnOptions = array())
    {
    	if ($display === null) {
    		$display = ucwords(str_replace("_", " ", $column));
    	}
    	
    	$this->addRawColumn($column, $display, $columnOptions);
    }

    /**
     * Return the url passed to the Curry_Flexigrid_Propel constructor
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }


} //Silva_Grid

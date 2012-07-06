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
 * Reusable general purpose methods.
 *
 * @category	Curry
 * @package		Silva
 * @author		Jose Francisco D'Silva
 * @version
 *
 */
abstract class Silva_Helpers
{
    const TEMP_PATH = 'content/temp/';

    public function __construct()
    {
        throw new Exception(__CLASS__ . " should not be instantiated.");
    }

    /**
     * Return the temporary path.
     * If path does not exist it will be created.
     * @param boolean $fullPath Return the full path instead of the relative path
     * @return string
     */
    public static function getTempPath($fullPath = false)
    {
        $fullTempPath = Curry_Core::$config->curry->wwwPath . '/' . self::TEMP_PATH;
        if (! file_exists($fullTempPath)) {
            mkdir($fullTempPath);
        }

        return $fullPath ? $fullTempPath : self::TEMP_PATH;
    }

    /**
     * Whether $object belongs to $class
     * @param $object
     * @param string $class
     * @return boolean
     */
    public static function is_class($object, $class)
    {
        return (is_object($object) && (get_class($object)==$class));
    }

} //Silva_Helpers

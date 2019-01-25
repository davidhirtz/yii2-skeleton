<?php
namespace davidhirtz\yii2\skeleton\helpers;

/**
 * Class ArrayHelper.
 * @package davidhirtz\yii2\skeleton\helpers
 */
class ArrayHelper extends \yii\helpers\BaseArrayHelper
{
	/**
	 * @param array $array
	 * @param string $value
	 * @param string $replacement
	 */
	public static function replaceValue(&$array, $value, $replacement)
	{
		if(($key=array_search($value, $array))!==false)
		{
			$array[$key]=$replacement;
		}
	}

	/**
	 * @param array $array
	 * @param string $key
	 * @param mixed $value
	 */
	public static function setDefaultValue(&$array, $key, $value)
	{
		if(!static::keyExists($key, $array))
		{
			$array[$key]=$value;
		}
	}

	/**
	 * @param array $array
	 * @param array $values
	 */
	public static function setDefaultValues(&$array, $values)
	{
		foreach($values as $key=>$value)
		{
			static::setDefaultValue($array, $key, $value);
		}
	}

	/**
	 * @param array $array
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public static function simplify($array, $key, $value)
	{
		$result=[];

		foreach($array as $index=>$element)
		{
			$result[$key ? $element[$key] : $index]=static::getValue($element, $value);
		}

		return $result;
	}

	/**
	 * Adds unique keys to comma separated string.
	 *
	 * @param array|string $string
	 * @param array|string $values
	 * @param string $separator
	 * @return array
	 */
	public static function cacheStringToArray($string, $values=[], $separator=',')
	{
		if(!is_array($string))
		{
			$string=explode($separator, $string);
		}

		if(!is_array($values))
		{
			$values=[$values];
		}

		return array_unique(array_filter(array_merge($string, $values)));
	}

	/**
	 * Creates a comma separated cache string from array.
	 *
	 * @param array $array
	 * @param string $separator
	 * @param mixed $default
	 *
	 * @return string
	 */
	public static function createCacheString($array, $separator=',', $default=null)
	{
		return $array ? implode($separator, $array) : $default;
	}

	/**
	 * Reads a SimpleXML object into an array.
	 *
	 * @param \SimpleXMLElement $xml
	 * @return array
	 */
	public static function simpleXmlToArray($xml)
	{
		$namespace=$xml->getDocNamespaces(true);
		$namespace[null]=null;

		$children=[];
		$attributes=[];

		$name=(string)$xml->getName();
		$text=trim((string)$xml);

		if(strlen($text)<=0)
		{
			$text=null;
		}

		if(is_object($xml))
		{
			foreach($namespace as $ns=>$nsUrl)
			{
				// Attributes.
				$_attributes=$xml->attributes($ns, true);

				foreach($_attributes as $attribute=>$value)
				{
					$attribute=trim((string)$attribute);
					$value=trim((string)$value);

					if(!empty($ns))
					{
						$attribute=$ns.':'.$attribute;
					}

					$attributes[$attribute]=$value;
				}

				// Children.
				$_children=$xml->children($ns, true);

				foreach($_children as $child=>$value)
				{
					$child=(string)$child;

					if(!empty($ns))
					{
						$child=$ns.':'.$child;
					}

					$children[$child][]=static::simpleXmlToArray($value);
				}
			}
		}

		return [
			'name'=>$name,
			'text'=>$text,
			'attributes'=>$attributes,
			'children'=>$children
		];
	}
}
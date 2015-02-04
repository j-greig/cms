<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\dates\DateTime;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\ModelHelper;
use craft\app\helpers\StringHelper;
use yii\db\ActiveRecord;

/**
 * Active Record base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseRecord extends ActiveRecord
{
	// Constants
	// =========================================================================

	const RESTRICT = 'RESTRICT';
	const CASCADE = 'CASCADE';
	const NO_ACTION = 'NO ACTION';
	const SET_DEFAULT = 'SET DEFAULT';
	const SET_NULL = 'SET NULL';

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_attributeConfigs;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string $scenario
	 *
	 * @return BaseRecord
	 */
	public function __construct($scenario = 'insert')
	{
		// If Craft isn't installed, this model's table won't exist yet, so just create an instance of the class,
		// for use by the installer
		if ($scenario !== 'install')
		{
			parent::__construct($scenario);
		}
	}

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		ModelHelper::populateAttributeDefaults($this);

		$this->attachEventHandler('onAfterFind', [$this, 'prepAttributesForUse']);
		$this->attachEventHandler('onBeforeSave', [$this, 'prepAttributesForSave']);
		$this->attachEventHandler('onAfterSave', [$this, 'prepAttributesForUse']);
	}

	/**
	 * Returns an instance of the specified model
	 *
	 * @param string $class
	 *
	 * @return BaseRecord|object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model(get_called_class());
	}

	/**
	 * @inheritdoc
	 *
	 * @return string[]
	 */
	public static function primaryKey()
	{
		return ['id'];
	}

	/**
	 * Returns this record's normalized attribute configs.
	 *
	 * @return array
	 */
	public function getAttributeConfigs()
	{
		if (!isset($this->_attributeConfigs))
		{
			$this->_attributeConfigs = [];

			foreach ($this->defineAttributes() as $name => $config)
			{
				$this->_attributeConfigs[$name] = ModelHelper::normalizeAttributeConfig($config);
			}
		}

		return $this->_attributeConfigs;
	}

	/**
	 * Defines this model's database table indexes.
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [];
	}

	/**
	 * Prepares the model's attribute values to be saved to the database.
	 *
	 * @return null
	 */
	public function prepAttributesForSave()
	{
		$attributes = $this->getAttributeConfigs();
		$attributes['dateUpdated'] = ['type' => AttributeType::DateTime, 'column' => ColumnType::DateTime, 'required' => true];
		$attributes['dateCreated'] = ['type' => AttributeType::DateTime, 'column' => ColumnType::DateTime, 'required' => true];

		foreach ($attributes as $name => $config)
		{
			$value = $this->getAttribute($name);

			if ($config['type'] == AttributeType::DateTime)
			{
				// Leaving this in because we want to allow plugin devs to save a timestamp or DateTime object.
				if (DateTimeHelper::isValidTimeStamp($value))
				{
					$value = new DateTime('@'.$value);
				}
			}

			$this->setAttribute($name, ModelHelper::packageAttributeValue($value, true));
		}

		// Populate dateCreated and uid if this is a new record
		if ($this->getIsNewRecord())
		{
			$this->dateCreated = DateTimeHelper::currentTimeForDb();
			$this->uid = StringHelper::UUID();
		}

		// Update the dateUpdated
		$this->dateUpdated = DateTimeHelper::currentTimeForDb();
	}

	/**
	 * Return the attribute values to the formats we want to work with in the code.
	 *
	 * @return null
	 */
	public function prepAttributesForUse()
	{
		$attributes = $this->getAttributeConfigs();
		$attributes['dateUpdated'] = ['type' => AttributeType::DateTime, 'column' => ColumnType::DateTime, 'required' => true];
		$attributes['dateCreated'] = ['type' => AttributeType::DateTime, 'column' => ColumnType::DateTime, 'required' => true];

		foreach ($attributes as $name => $config)
		{
			$value = $this->getAttribute($name);

			switch ($config['type'])
			{
				case AttributeType::DateTime:
				{
					if ($value)
					{
						// TODO: MySQL specific.
						$dateTime = DateTime::createFromFormat(DateTime::MYSQL_DATETIME, $value);

						$this->setAttribute($name, $dateTime);
					}

					break;
				}
				case AttributeType::Mixed:
				{
					if (is_string($value) && StringHelper::length($value) && StringHelper::containsAny($value[0], array('[', '{')))
					{
						$this->setAttribute($name, JsonHelper::decode($value));
					}

					break;
				}
			}
		}
	}

	/**
	 * @inheritdoc
	 * @return ActiveQuery The newly created [[ActiveQuery]] instance.
	 */
	public static function find()
	{
		return Craft::createObject(ActiveQuery::className(), [get_called_class()]);
	}

	// Model and ActiveRecord methods

	/**
	 * Returns this model's validation rules.
	 *
	 * @return array
	 */
	public function rules()
	{
		return ModelHelper::getRules($this);
	}

	/**
	 * Returns the attribute labels.
	 *
	 * @return array
	 */
	public function attributeLabels()
	{
		return ModelHelper::getAttributeLabels($this);
	}

	/**
	 * Sets the named attribute value. You may also use $this->AttributeName to set the attribute value.
	 *
	 * @param string $name  The attribute name.
	 * @param mixed  $value The attribute value.
	 *
	 * @return bool Whether the attribute exists and the assignment is conducted successfully.
	 */
	public function setAttribute($name, $value)
	{
		if (property_exists($this, $name))
		{
			$this->$name = $value;
		}
		else if (isset($this->getMetaData()->columns[$name]))
		{
			$this->_attributes[$name] = $value;
		}
		else
		{
			return false;
		}

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Defines this model's attributes.
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [];
	}

	// Private Methods
	// =========================================================================

	/**
	 * Normalizes a relation's config
	 *
	 * @param string $name
	 * @param array  &$config
	 *
	 * @return null
	 */
	private function _normalizeRelation($name, &$config)
	{
		// Add the namespace to the class name
		if (!StringHelper::contains($config[1], '\\'))
		{
			$config[1] = __NAMESPACE__.'\\'.$config[1];
		}

		switch ($config[0])
		{
			case static::BELONGS_TO:
			{
				// Add the foreign key
				if (empty($config[2]))
				{
					array_splice($config, 2, 0, $name.'Id');
				}
				break;
			}

			case static::MANY_MANY:
			{
				$config[2] = Craft::$app->getDb()->tablePrefix.$config[2];
				break;
			}
		}
	}
}

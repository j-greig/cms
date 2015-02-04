<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserPermission_UserGroup record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserPermission_UserGroup extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%userpermissions_usergroups}}';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'permission' => [static::BELONGS_TO, 'UserPermission', 'required' => true, 'onDelete' => static::CASCADE],
			'group'      => [static::BELONGS_TO, 'UserGroup',      'required' => true, 'onDelete' => static::CASCADE],
		];
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['permissionId', 'groupId'], 'unique' => true],
		];
	}
}

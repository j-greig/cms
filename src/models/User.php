<?php
namespace Blocks;

/**
 *
 */
class User extends BaseModel
{
	public function getTableName()
	{
		return 'users';
	}

	protected function getProperties()
	{
		return array(
			'username'                         => array(PropertyType::Varchar, 'maxLength' => 100, 'required' => true, 'unique' => true),
			'firstName'                        => array(PropertyType::Varchar, 'maxLength' => 100),
			'lastName'                         => array(PropertyType::Varchar, 'maxLength' => 100),
			'email'                            => array(PropertyType::Email, 'required' => true, 'unique' => true),
			'password'                         => PropertyType::Char,
			'encType'                          => array(PropertyType::Char, 'maxLength' => 10),
			'authSessionToken'                 => array(PropertyType::Char, 'maxLength' => 100),
			'admin'                            => PropertyType::Boolean,
			'passwordResetRequired'            => PropertyType::Boolean,
			'status'                           => array(PropertyType::Enum, 'values' => array('locked', 'suspended', 'pending', 'active', 'archived'), 'default' => 'pending'),
			/* BLOCKSPRO ONLY */
			'language'                         => array(PropertyType::Language, 'default' => Blocks::getLanguage()),
			/* end BLOCKSPRO ONLY */
			'emailFormat'                      => array(PropertyType::Enum, 'values' => array('text', 'html'), 'default' => 'text', 'required' => true),
			'lastLoginDate'                    => PropertyType::Int,
			'lastLoginFailedDate'              => PropertyType::Int,
			'lastPasswordChangeDate'           => PropertyType::Int,
			'lastLockoutDate'                  => PropertyType::Int,
			'failedPasswordAttemptCount'       => array(PropertyType::TinyInt, 'unsigned' => true),
			'failedPasswordAttemptWindowStart' => PropertyType::Int,
			'cooldownStart'                    => PropertyType::Int,
			'verificationCode'                 => array(PropertyType::Char, 'maxLength' => 36),
			'verificationCodeIssuedDate'       => PropertyType::Int,
			'verificationCodeExpiryDate'       => PropertyType::Int,
			'archivedUsername'                 => array(PropertyType::Varchar, 'maxLength' => 100),
			'archivedEmail'                    => PropertyType::Email,
		);
	}

	protected function getRelations()
	{
		return array(
			/* BLOCKSPRO ONLY */
			'blocks'  => array(static::HAS_MANY, 'UserBlock', 'userId'),
			'content' => array(static::HAS_ONE, 'UserContent', 'userId'),
			/* end BLOCKSPRO ONLY */
			'widgets' => array(static::HAS_MANY, 'Widget', 'userId'),
		);
	}

	/**
	 * String representation of a user
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->getFullName();
	}

	/**
	 * Returns the user's full name (first+last name), if it's available.
	 *
	 * @return string
	 */
	public function getFullName()
	{
		return $this->firstName . ($this->firstName && $this->lastName ? ' ' : '') . $this->lastName;
	}

	/**
	 * Returns whether this is the current logged-in user
	 *
	 * @return bool
	 */
	public function getIsCurrent()
	{
		return (!$this->getIsNewRecord() && $this->id == blx()->accounts->getCurrentUser()->id);
	}

	/**
	 * @return mixed
	 */
	public function getRemainingCooldownTime()
	{
		return blx()->accounts->getRemainingCooldownTime($this);
	}
}

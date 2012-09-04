<?php
namespace Blocks;

/**
 * Handles settings from the control panel.
 */
class SettingsController extends BaseController
{
	/**
	 * All settings actions require the user to be logged in
	 */
	public function init()
	{
		$this->requireLogin();
	}

	/**
	 * Saves the general settings.
	 */
	public function actionSaveGeneralSettings()
	{
		$this->requirePostRequest();

		$generalSettingsForm = new GeneralSettingsForm();
		$generalSettingsForm->siteName = blx()->request->getPost('siteName');
		$generalSettingsForm->siteUrl = blx()->request->getPost('siteUrl');
		/* BLOCKSPRO ONLY */
		$generalSettingsForm->licenseKey = blx()->request->getPost('licenseKey');
		/* end BLOCKSPRO ONLY */

		if ($generalSettingsForm->validate())
		{
			$info = Info::model()->find();
			$info->siteName = $generalSettingsForm->siteName;
			$info->siteUrl = $generalSettingsForm->siteUrl;
			/* BLOCKSPRO ONLY */
			$info->licenseKey = $generalSettingsForm->licenseKey;
			/* end BLOCKSPRO ONLY */
			$info->save();

			blx()->user->setNotice(Blocks::t('General settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save general settings.'));
			$this->renderRequestedTemplate(array('post' => $generalSettingsForm));
		}
	}

	/**
	 * Saves the email settings.
	 */
	public function actionSaveEmailSettings()
	{
		$this->requirePostRequest();

		$emailSettings = new EmailSettingsForm();
		$gMailSmtp = 'smtp.gmail.com';

		$emailSettings->protocol                    = blx()->request->getPost('protocol');
		$emailSettings->host                        = blx()->request->getPost('host');
		$emailSettings->port                        = blx()->request->getPost('port');
		$emailSettings->smtpAuth                    = (bool)blx()->request->getPost('smtpAuth');

		if ($emailSettings->smtpAuth)
		{
			$emailSettings->username                = blx()->request->getPost('smtpUsername');
			$emailSettings->password                = blx()->request->getPost('smtpPassword');
		}
		else
		{
			$emailSettings->username                = blx()->request->getPost('username');
			$emailSettings->password                = blx()->request->getPost('password');
		}

		$emailSettings->smtpKeepAlive               = (bool)blx()->request->getPost('smtpKeepAlive');
		$emailSettings->smtpSecureTransportType     = blx()->request->getPost('smtpSecureTransportType');
		$emailSettings->timeout                     = blx()->request->getPost('timeout');
		$emailSettings->emailAddress                = blx()->request->getPost('emailAddress');
		$emailSettings->senderName                  = blx()->request->getPost('senderName');

		// Validate user input
		if ($emailSettings->validate())
		{
			$settings = array('protocol' => $emailSettings->protocol);
			$settings['emailAddress'] = $emailSettings->emailAddress;
			$settings['senderName'] = $emailSettings->senderName;
			/* BLOCKSPRO ONLY */
			$settings['template'] = blx()->request->getPost('template');
			/* end BLOCKSPRO ONLY */

			switch ($emailSettings->protocol)
			{
				case EmailerType::Smtp:
				{
					if ($emailSettings->smtpAuth)
					{
						$settings['smtpAuth'] = 1;
						$settings['username'] = $emailSettings->username;
						$settings['password'] = $emailSettings->password;
					}

					$settings['smtpSecureTransportType'] = $emailSettings->smtpSecureTransportType;

					$settings['port'] = $emailSettings->port;
					$settings['host'] = $emailSettings->host;
					$settings['timeout'] = $emailSettings->timeout;

					if ($emailSettings->smtpKeepAlive)
					{
						$settings['smtpKeepAlive'] = 1;
					}

					break;
				}

				case EmailerType::Pop:
				{
					$settings['port'] = $emailSettings->port;
					$settings['host'] = $emailSettings->host;
					$settings['username'] = $emailSettings->username;
					$settings['password'] = $emailSettings->password;
					$settings['timeout'] = $emailSettings->timeout;

					break;
				}

				case EmailerType::GmailSmtp:
				{
					$settings['host'] = $gMailSmtp;
					$settings['smtpAuth'] = 1;
					$settings['smtpSecureTransportType'] = 'tls';
					$settings['username'] = $emailSettings->username;
					$settings['password'] = $emailSettings->password;
					$settings['port'] = $emailSettings->smtpSecureTransportType == 'tls' ? '587' : '465';
					$settings['timeout'] = $emailSettings->timeout;
					break;
				}
			}

			if (blx()->email->saveSettings($settings))
			{
				blx()->user->setNotice(Blocks::t('Email settings saved.'));
				$this->redirectToPostedUrl();
			}
			else
			{
				blx()->user->setError(Blocks::t('Couldn’t save email settings.'));
			}
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save email settings.'));
		}

		$this->renderRequestedTemplate(array('settings' => $emailSettings));
	}

	/**
	 * Saves the language settings.
	 */
	public function actionSaveLanguageSettings()
	{
		$this->requirePostRequest();

		$languages = blx()->request->getPost('languages', array());
		sort($languages);

		if (blx()->settings->saveSettings('systemsettings', $languages, 'languages', true))
		{
			blx()->user->setNotice(Blocks::t('Language settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save language settings.'));
			$this->renderRequestedTemplate(array('selectedLanguages' => $languages));
		}
	}

	/**
	 * Saves the advanced settings.
	 */
	public function actionSaveAdvancedSettings()
	{
		$this->requirePostRequest();

		$settings = array();

		$checkboxes = array('showDebugInfo', 'useUncompressedJs', 'disablePlugins');
		foreach ($checkboxes as $key)
		{
			if (blx()->request->getPost($key))
				$settings[$key] = true;
		}

		if (blx()->settings->saveSettings('systemsettings', $settings, 'advanced', true))
		{
			blx()->user->setNotice(Blocks::t('Advanced settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save advanced settings.'));
			$this->renderRequestedTemplate(array('settings' => $settings));
		}
	}
}

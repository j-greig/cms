<?php
namespace Blocks;

/**
 *
 */
class DashboardService extends \CApplicationComponent
{
	private $_allWidgets;

	/**
	 * Returns all installed widgets.
	 *
	 * @return array
	 */
	public function getAllWidgets()
	{
		if (!isset($this->_allWidgets))
			$this->_allWidgets = ComponentHelper::getComponents('widgets', 'Widget');

		return $this->_allWidgets;
	}

	/**
	 * Returns a widget by its class.
	 *
	 * @param string $class
	 * @return mixed
	 */
	public function getWidgetByClass($class)
	{
		$class = __NAMESPACE__.'\\'.$class.'Widget';
		if (class_exists($class))
			return new $class;
		else
			return null;
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $widgetId
	 * @return Widget
	 */
	public function getWidgetById($widgetId)
	{
		$record = Widget::model()->findByAttributes(array(
			'id' => $widgetId,
			'userId' => blx()->accounts->getCurrentUser()->id
		));

		if ($record)
		{
			$widget = $this->getWidgetByClass($record->class);
			if ($widget)
			{
				$widget->record = $record;
				$widget->init();
				return $widget;
			}
		}

		return null;
	}

	/**
	 * Returns the dashboard widgets for the current user.
	 *
	 * @return array
	 */
	public function getUserWidgets()
	{
		$records = Widget::model()->findAllByAttributes(array(
			'userId' => blx()->accounts->getCurrentUser()->id
		), array(
			'order' => 'sortOrder'
		));

		$widgets = array();

		foreach ($records as $record)
		{
			$widget = $this->getWidgetByClass($record->class);
			if ($widget)
			{
				$widget->record = $record;
				$widget->init();
				$widgets[] = $widget;
			}
		}

		return $widgets;
	}

	/**
	 * Assign the default widgets to a user.
	 *
	 * @param int $userId
	 */
	public function assignDefaultUserWidgets($userId = null)
	{
		if ($userId === null)
			$userId = 1;

		// Add the default dashboard widgets
		$widgets = array('RecentActivity', 'Feed');
		foreach ($widgets as $i => $widgetClass)
		{
			$widget = new Widget();
			$widget->userId = $userId;
			$widget->class = $widgetClass;
			$widget->sortOrder = ($i + 1);
			$widget->save();
		}
	}

	/**
	 * Saves the user's dashboard settings
	 *
	 * @param array $settings
	 * @throws \Exception
	 * @throws Exception
	 * @return bool
	 */
	public function saveSettings($settings)
	{
		// Get the current user
		$user = blx()->accounts->getCurrentUser();
		if (!$user)
			throw new Exception(Blocks::t('There is no current user.'));

		$transaction = blx()->db->beginTransaction();
		try
		{
			if (isset($settings['order']))
				$widgetIds = $settings['order'];
			else
			{
				$widgetIds = array_keys($settings);
				if (($deleteIndex = array_search('delete', $widgetIds)) !== false)
					array_splice($widgetIds, $deleteIndex, 1);
			}

			foreach ($widgetIds as $order => $widgetId)
			{
				$widgetData = $settings[$widgetId];

				$widget = new Widget();
				$isNewWidget = true;

				if (strncmp($widgetId, 'new', 3) != 0)
					$widget = $this->getWidgetById($widgetId);

				if (empty($widget))
					$widget = new Widget();

				$widget->userId = $user->id;
				$widget->class = $widgetData['class'];
				$widget->sortOrder = $order + 1;
				$widget->save();

				if (!empty($widgetData['settings']))
					$widget->setSettings($widgetData['settings']);
			}

			if (isset($settings['delete']))
			{
				foreach ($settings['delete'] as $widgetId)
				{
					blx()->db->createCommand()->delete('widgetsettings', array('widgetId'=>$widgetId));
					blx()->db->createCommand()->delete('widgets',        array('id'=>$widgetId));
				}
			}

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}
}

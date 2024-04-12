<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class logManagerLevel {
	public const LOGLEVEL = array(100 => 'debug', 200 => 'info', 300 => 'warning', 400 => 'error');

	public static function all() {
		return self::LOGLEVEL;
	}

	public static function getId($loglevel) {
		foreach (self::LOGLEVEL as $key => $value) {
			if ($value == $loglevel) return $key;
		}
	}
}

class logmanager extends eqLogic {

	public static $_widgetPossibility = array(
		'custom' => array(
			'visibility' => true,
			'displayName' => true,
			'displayObjectName' => true,
			'optionalParameters' => false,
			'background-color' => true,
			'background-opacity' => true,
			'text-color' => true,
			'border-radius' => true,
			'border' => true
		)
	);

	private function throwExceptionIfInvalidLogname() {
		try {
			plugin::byId($this->getName());
		} catch (\Throwable $th) {
			return;
		}
		throw new Exception("Le nom de l'équipement choisi ({$this->getName()}) n'est pas autorisé car il correspond à un plugin installé sur votre Jeedom.");
	}


	public function preInsert() {
		$this->throwExceptionIfInvalidLogname();

		$this->setConfiguration('loglevel', '100');
		$this->setIsEnable(1);
	}

	public function preSave() {
		$this->throwExceptionIfInvalidLogname();

		$replaceChars = array(
			'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a',
			'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
			'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
			'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o',
			'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u'
		);

		$name = ltrim($this->getName(), '_');
		$name = strtr($name, $replaceChars);
		$name = preg_replace("/[^a-zA-Z_]/", "", $name);
		$this->setName($name);

		$nbrLines = $this->getConfiguration('nbrLinesWidget');
		if (!is_numeric($nbrLines) || $nbrLines < 1 || $nbrLines > 3000) {
			$nbrLines = '';
		} else {
			$nbrLines = round($nbrLines);
		}
		$this->setConfiguration('nbrLinesWidget', $nbrLines);
	}

	public function postSave() {
		$order = 0;

		foreach (logManagerLevel::all() as $loglevel) {
			$cmd = $this->getCmd(null, $loglevel);
			if (!is_object($cmd)) {
				$cmd = new logmanagerCmd();
				$cmd->setLogicalId($loglevel);
				$cmd->setIsVisible(1);
				$cmd->setOrder($order++);
				$cmd->setName(ucfirst($loglevel));
				$cmd->setType('action');
				$cmd->setSubType('message');
				$cmd->setEqLogic_id($this->getId());
				$cmd->setDisplay('title_disable', 1);
				$cmd->save();
			}
		}

		$cmd = $this->getCmd(null, 'clear');
		if (!is_object($cmd)) {
			$cmd = new logmanagerCmd();
			$cmd->setLogicalId('clear');
			$cmd->setIsVisible(1);
			$cmd->setOrder(4);
			$cmd->setName(__('Vider', __FILE__));
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
		}
		$cmd = $this->getCmd(null, 'remove');
		if (!is_object($cmd)) {
			$cmd = new logmanagerCmd();
			$cmd->setLogicalId('remove');
			$cmd->setIsVisible(1);
			$cmd->setOrder(5);
			$cmd->setName(__('Supprimer', __FILE__));
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($this->getId());
			$cmd->save();
		}

		$logConfig = array($this->getConfiguration('loglevel', '100') => '1', 'default' => '0');
		config::save('log::level::' . $this->getName(), $logConfig);

		$this->refreshWidget();
	}

	public function postRemove() {
		try {
			plugin::byId($this->getName());
		} catch (\Throwable $th) {
			config::remove('log::level::' . $this->getName());
			log::remove($this->getName());
		}
	}

	public function toHtml($_version = 'dashboard') {
		if ($this->getConfiguration('displayContentWidget', 0) == 0) {
			return parent::toHtml($_version);
		}

		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);

		switch ($this->getDisplay('layout::' . $version)) {
			case 'table':
				$replace['#eqLogic_class#'] = 'eqLogic_layout_table';
				$table = self::generateHtmlTable($this->getDisplay('layout::' . $version . '::table::nbLine', 1), $this->getDisplay('layout::' . $version . '::table::nbColumn', 1), $this->getDisplay('layout::' . $version . '::table::parameters'));
				$br_before = 0;
				foreach ($this->getCmd(null, null, true) as $cmd) {
					if (isset($replace['#refresh_id#']) && $cmd->getId() == $replace['#refresh_id#']) {
						continue;
					}
					$tag = '#cmd::' . $this->getDisplay('layout::' . $version . '::table::cmd::' . $cmd->getId() . '::line', 1) . '::' . $this->getDisplay('layout::' . $version . '::table::cmd::' . $cmd->getId() . '::column', 1) . '#';
					if ($br_before == 0 && $cmd->getDisplay('forceReturnLineBefore', 0) == 1) {
						$table['tag'][$tag] .= '<br/>';
					}
					$table['tag'][$tag] .= $cmd->toHtml($_version, '', $replace['#cmd-background-color#']);
					$br_before = 0;
					if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) {
						$table['tag'][$tag] .= '<br/>';
						$br_before = 1;
					}
				}
				$replace['#cmd#'] = template_replace($table['tag'], $table['html']);
				break;
			default:
				$replace['#eqLogic_class#'] = 'eqLogic_layout_default';
				$cmd_html = '';
				$br_before = 0;
				foreach ($this->getCmd(null, null, true) as $cmd) {
					if (isset($replace['#refresh_id#']) && $cmd->getId() == $replace['#refresh_id#']) {
						continue;
					}
					if ($br_before == 0 && $cmd->getDisplay('forceReturnLineBefore', 0) == 1) {
						$cmd_html .= '<br/>';
					}
					$cmd_html .= $cmd->toHtml($_version, '', $replace['#cmd-background-color#']);
					$br_before = 0;
					if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) {
						$cmd_html .= '<br/>';
						$br_before = 1;
					}
					if ($cmd->getLogicalId() == 'clear') {
						$replace['#clear_id#'] = $cmd->getId();
					}
				}
				$replace['#cmd#'] = $cmd_html;
				break;
		}

		$content = '';
		$maxLines = $this->getConfiguration('nbrLinesWidget', 1000);
		$topToBottom = $this->getConfiguration('topToBottom', 0) == 1 ? true : false;
		$linesDisplayed = 0;
		foreach (log::get($this->getName(), 0, 3000) as $line) {

			if ($topToBottom) {
				$content = $line . '<br/>' . $content;
			} else {
				$content .= $line . '<br/>';
			}
			if (++$linesDisplayed == $maxLines) break;
		}
		$search = array();              $replaceLog = array();
		$search[] = '[DEBUG]';          $replaceLog[] = '<span class="label label-xs label-success">&nbsp;D<&>EBUG&nbsp;</span>';
		$search[] = '[INFO]';           $replaceLog[] = '<span class="label label-xs label-info">&nbsp;I<&>NFO&nbsp;</span>';
		$search[] = '[WARNING]';        $replaceLog[] = '<span class="label label-xs label-warning">W<&>ARNING</span>';
		$search[] = '[ERROR]';          $replaceLog[] = '<span class="label label-xs label-danger">&nbsp;E<&>RROR&nbsp;</span>';
		$content = str_replace($search, $replaceLog, $content);
		$content = str_replace('<&>', '', $content);
		$replace['#logContent#'] = $content;

		return template_replace($replace, getTemplate('core', $version, 'logmanager', __CLASS__));
	}

	public function checkAndRefreshWidget() {
		if ($this->getConfiguration('displayContentWidget', 0) == 1) {
			$this->refreshWidget();
		}
	}

	public function addLog($logLevel, $message) {
		$this->throwExceptionIfInvalidLogname();

		$logName = $this->getName();
		$logLevelId = logManagerLevel::getId($logLevel);
		$eventLevel = intval($this->getConfiguration('eventlevel', 9999));
		$logLevelConfig = log::getLogLevel($logName);

		log::add('logmanager', 'debug', "Log new message with level {$logLevel} in {$logName} with config {$logLevelConfig}");
		try {
			log::add($logName, $logLevel, $message);
			$this->checkAndRefreshWidget();
		} catch (\Throwable $th) {
			log::add('logmanager', 'error', "Erreur lors du log du message '{$message} dans le log '{$logName}': {$th->getMessage()}");
		}

		log::add('logmanager', 'debug', "New event? {$logLevelId} >= {$eventLevel}");
		if ($logLevelId >= $eventLevel && $logLevelId >= $logLevelConfig) {
			log::add('logmanager', 'debug', "Sending event for {$logLevel}");
			jeedom::event("lm-{$logLevel}");
		}
	}
}

class logmanagerCmd extends cmd {
	public function dontRemoveCmd() {
		return true;
	}

	public function execute($_options = array()) {
		/** @var logmanager */
		$eqlogic = $this->getEqLogic();
		$logName = $eqlogic->getName();

		switch ($this->getLogicalId()) {
			case 'clear':
				log::add('logmanager', 'debug', "Clear log {$logName}");
				log::clear($logName);
				$eqlogic->checkAndRefreshWidget();
				return;
			case 'remove':
				log::add('logmanager', 'debug', "Remove log {$logName}");
				log::remove($logName);
				$eqlogic->checkAndRefreshWidget();
				return;
		}

		if (!is_array($_options)) {
			log::add('logmanager', 'error', __('Options invalides', __FILE__));
			return;
		}
		if (!isset($_options['message'])) {
			log::add('logmanager', 'info', __('Message absent', __FILE__));
			return;
		}
		$message = trim($_options['message']);
		if ($message == '') {
			log::add('logmanager', 'info', __('Message vide', __FILE__));
			return;
		}

		$eqlogic->addLog($this->getLogicalId(), $message);
	}
}

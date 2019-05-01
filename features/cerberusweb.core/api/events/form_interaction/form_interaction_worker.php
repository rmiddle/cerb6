<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class Event_FormInteractionWorker extends Extension_DevblocksEvent {
	const ID = 'event.form.interaction.worker';
	
	/*
	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('trigger', $trigger);
		/$tpl->display('devblocks:cerberusweb.core::events/.../params.tpl');
	}
	*/
	
	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		$actions = [];
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'actions' => &$actions,
				
				'client_browser' => null,
				'client_browser_version' => null,
				'client_ip' => null,
				'client_platform' => null,
				
				'worker_id' => null,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = [];
		$values = [];
		
		/**
		 * Behavior
		 */
		
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, $trigger, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'behavior_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
			
		/**
		 * Worker
		 */
		
		@$worker_id = $event_model->params['worker_id'];
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'worker_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		
		// Actions
		$values['_actions'] =& $event_model->params['actions'];
		
		// Client
		@$client_browser = $event_model->params['client_browser'];
		@$client_browser_version = $event_model->params['client_browser_version'];
		@$client_ip = $event_model->params['client_ip'];
		@$client_platform = $event_model->params['client_platform'];
		
		$labels['client_browser'] = 'Client Browser';
		$labels['client_browser_version'] = 'Client Browser Version';
		$labels['client_ip'] = 'Client IP';
		$labels['client_platform'] = 'Client Platform';
		
		$values['client_browser'] = $client_browser;
		$values['client_browser_version'] = $client_browser_version;
		$values['client_ip'] = $client_ip;
		$values['client_platform'] = $client_platform;
		
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'worker_id' => array(
				'label' => 'Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		DevblocksPlatform::sortObjects($vals_to_ctx, '[label]');
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
		// Client
		$labels['client_browser'] = 'Client Browser';
		$labels['client_browser_version'] = 'Client Browser Version';
		$labels['client_ip'] = 'Client IP';
		$labels['client_platform'] = 'Client Platform';
		
		$types['client_browser'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_browser_version'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_ip'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_platform'] = Model_CustomField::TYPE_SINGLE_LINE;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			array(
				'create_comment' => array('label' =>'Create comment'),
				'create_notification' => array('label' =>'Create notification'),
				'create_task' => array('label' =>'Create task'),
				'create_ticket' => array('label' =>'Create ticket'),
				'send_email' => array('label' => 'Send email'),
				
				'prompt_captcha' => array('label' => 'Form prompt with CAPTCHA challenge'),
				'prompt_checkboxes' => array('label' => 'Form prompt with multiple choices'),
				'prompt_radios' => array('label' => 'Form prompt with single choice'),
			)
			;
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment($trigger);
				break;

			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification($trigger);
				break;

			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask($trigger);
				break;

			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket($trigger);
				break;
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;
			
			case 'prompt_captcha':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/prompts/action_prompt_captcha.tpl');
				break;
				
			case 'prompt_checkboxes':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/prompts/action_prompt_checkboxes.tpl');
				break;
				
			case 'prompt_radios':
				$tpl->display('devblocks:cerberusweb.core::events/form_interaction/_common/prompts/action_prompt_radios.tpl');
				break;
				
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'worker_id');
				break;

			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'worker_id');
				break;

			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'worker_id');
				break;

			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict, 'worker_id');
				break;
			
			case 'prompt_captcha':
				$out = ">>> Prompting with CAPTCHA challenge\n";
				break;
				
			case 'prompt_checkboxes':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$label = $tpl_builder->build($params['label'], $dict);
				$options = $tpl_builder->build($params['options'], $dict);
				
				$out = sprintf(">>> Prompting with checkboxes\nLabel: %s\nOptions: %s\n",
					$label,
					$options
				);
				break;
				
			case 'prompt_radios':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$label = $tpl_builder->build($params['label'], $dict);
				$options = $tpl_builder->build($params['options'], $dict);
				
				$out = sprintf(">>> Prompting with radio buttons\nLabel: %s\nOptions: %s\n",
					$label,
					$options
				);
				break;
				
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			
		}
		
		return $out;
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'worker_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'worker_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'worker_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict);
				break;
			
			case 'prompt_captcha':
				$actions =& $dict->_actions;
				
				assert($actions);
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$var = $params['var'];
				
				$label = 'Please prove you are not a robot:';
				
				// Generate random code
				$otp_key = $var . '__otp';
				$otp = $dict->get($otp_key);
				
				if(!$otp) {
					$otp = CerberusApplication::generatePassword(4);
					$dict->set($otp_key, $otp);
				}
				
				$actions[] = [
					'_action' => 'prompt.captcha',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'var' => $var,
					],
					'label' => $label,
				];
				break;
				
			case 'prompt_checkboxes':
				$actions =& $dict->_actions;
				
				assert($actions);
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$label = $tpl_builder->build($params['label'], $dict);
				@$options = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['options'], $dict));
				@$var = $params['var'];
				@$var_validate = $params['var_validate'];
				
				$actions[] = [
					'_action' => 'prompt.checkboxes',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'var' => $var,
						'validate' => $var_validate,
					],
					'label' => $label,
					'options' => $options,
				];
				break;
				
			case 'prompt_radios':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$label = $tpl_builder->build($params['label'], $dict);
				@$style = $params['style'];
				@$orientation = $params['orientation'];
				@$options = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['options'], $dict));
				@$default = $tpl_builder->build($params['default'], $dict);
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];
				
				$actions[] = [
					'_action' => 'prompt.radios',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'var' => $var,
						'format' => $var_format,
						'validate' => $var_validate,
					],
					'label' => $label,
					'style' => $style,
					'orientation' => $orientation,
					'options' => $options,
					'default' => $default,
				];
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
			
		}
	}
};
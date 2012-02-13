<?php

	require_once(TOOLKIT . '/fields/field.select.php');

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class FieldTemplateselectbox extends Field {

		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Template Select Box';
			$this->set('show_column', 'no');
			$this->_driver = $this->_engine->ExtensionManager->create('templateselectboxfield');
		}

		function canToggle(){
			return false;
		}

		function allowDatasourceParamOutput(){
			## Grouping follows the same rule as toggling.
			//return $this->canToggle();
			return true;
		}

		function canFilter(){
			return true;
		}

		public function canImport(){
			return false;
		}

		function canPrePopulate(){
			return true;
		}

		function isSortable(){
			return false;
		}

		public function appendFormattedElement(&$wrapper, $data, $encode = false) {

			if (!is_array($data) or empty($data)) return;

			if (!is_array($data['file'])) {
				if($data['file'] == NULL) return;
				$data = array(
					'file' => array($data['file'])
				);
			}

			$item = new XMLElement($this->get('element_name'));

			$path = DOCROOT . $this->get('destination');

			foreach($data['file'] as $index => $file) {
				$item->appendChild(new XMLElement(
					'path', $path . '/' . General::sanitize($file)));
			}

			$wrapper->appendChild($item);		
			
		}
		
		public function buildSummaryBlock($errors = null){
			$span = new XMLElement('span');

			$label = Widget::Input('fields['.$this->get('sortorder').'][label]', 'Page Template', 'hidden');
			if(isset($errors['label'])) $span->appendChild(Widget::wrapFormElementWithError($label, $errors['label']));
			else $span->appendChild($label);
	
			$span->appendChild($this->buildLocationSelect($this->get('location'), 'fields['.$this->get('sortorder').'][location]'));
	
			return $span;
		}
					
		function displaySettingsPanel(&$wrapper, $errors=NULL){

			$wrapper->appendChild(new XMLElement('h4', $this->name()));
			$wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][type]', $this->handle(), 'hidden'));
			if($this->get('id')) $wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][id]', $this->get('id'), 'hidden'));

			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			
			## Destination Folder
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			$directories = General::listDirStructure(WORKSPACE, NULL, 'asc', DOCROOT, $ignore);

			$label = Widget::Label(__('Destination Directory'));

			$options = array();
			$options[] = array('/workspace', false, '/workspace');
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) $options[] = array($d, ($this->get('destination') == $d), $d);
				}
			}
			
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][destination]', $options));

			if(isset($errors['destination'])) $group->appendChild(Widget::wrapFormElementWithError($label, $errors['destination']));
			else $group->appendChild($label);
			
			$group->appendChild($this->buildSummaryBlock($errors));
			
			$wrapper->appendChild($group);

			$this->appendShowColumnCheckbox($wrapper);
			

		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			if(!is_array($data['file'])) $data['file'] = array($data['file']);

			$options = array();
			$states = General::listStructure(DOCROOT . $this->get('destination'), null, false, 'asc', DOCROOT);
			
			if (is_null($states['filelist']) || empty($states['filelist'])) $states['filelist'] = array();
			
			$options[] = array('', '', 'Page Template');
			
			foreach($states['filelist'] as $handle => $v){
				$path_info = pathinfo($v);
				if($path_info['extension'] == 'xsl') {
					$template_name = $this->_driver->readTemplateName(DOCROOT . $this->get('destination') . '/' . $v);
					if($template_name) {
						$options[] = array(General::sanitize($v), in_array($v, $data['file']), $template_name);
					} else {
						$options[] = array(General::sanitize($v), in_array($v, $data['file']), $v);
					}
					
				}
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		function prepareTableValue($data, XMLElement $link=NULL){
			$value = $data['file'];

			if(!is_array($value)) $value = array($value);

			$custom_link = "";

			foreach($value as $file) {
				if($link){
					$link->setValue(basename($file));
					$custom_link[] = $link->generate();
				}
				else{
					$link = Widget::Anchor(basename($file), URL . $this->get('destination') . '/'. $file);
					$custom_link[] = $link->generate();
				}
			}

			return implode(", ", $custom_link);
		}

		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (preg_match('/^mimetype:/', $data[0])) {
				$data[0] = str_replace('mimetype:', '', $data[0]);
				$column = 'mimetype';

			} else if (preg_match('/^size:/', $data[0])) {
				$data[0] = str_replace('size:', '', $data[0]);
				$column = 'size';

			} else {
				$column = 'file';
			}

			if (self::isFilterRegex($data[0])) {
				$this->_key++;
				$pattern = str_replace('regexp:', '', $this->cleanValue($data[0]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.{$column} REGEXP '{$pattern}'
				";

			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND t{$field_id}_{$this->_key}.{$column} = '{$value}'
					";
				}

			} else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.{$column} IN ('{$data}')
				";
			}

			return true;
		}

		function commit(){

			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['destination'] = $this->get('destination');

			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());

		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;

			if(!is_array($data)) return array('file' => General::sanitize($data));

			if(empty($data)) return NULL;

			$result = array('file' => array());

			foreach($data as $file) {
				$result['file'][] = $file;
			}

			return $result;
		}
	

		function createTable(){

			return $this->_engine->Database->query(

				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `file` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				);"

			);
		}

	}


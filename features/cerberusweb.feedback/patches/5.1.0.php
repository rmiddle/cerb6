<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Convert sequences to MySQL AUTO_INCREMENT, make UNSIGNED

// Drop sequence tables
$tables_seq = array(
	'feedback_entry_seq',
);
foreach($tables_seq as $table) {
	if(isset($tables[$table])) {
		$db->ExecuteMaster(sprintf("DROP TABLE IF EXISTS %s", $table));
		unset($tables[$table]);
	}
}

// Convert tables to ID = INT4 UNSIGNED AUTO_INCREMENT
$tables_autoinc = array(
	'feedback_entry',
);
foreach($tables_autoinc as $table) {
	if(!isset($tables[$table]))
		return FALSE;
	
	list($columns, $indexes) = $db->metaTable($table);
	if(isset($columns['id'])
		&& ('int(10) unsigned' != $columns['id']['type']
		|| 'auto_increment' != $columns['id']['extra'])
	) {
		$db->ExecuteMaster(sprintf("ALTER TABLE %s MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT", $table));
	}
}

return TRUE;